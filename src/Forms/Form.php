<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use app\forms\basemodel\BaseMultiForm;
use app\models\FormSubmission;
use Kalakotra\EFormKinderzuschlag\Model\FieldConditions;
use Kalakotra\EFormKinderzuschlag\Model\Submission;
use Kalakotra\EFormKinderzuschlag\Model\SubmissionChild;
use Kalakotra\EFormKinderzuschlag\Model\SubmissionSelfEmployed;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\SingleSelectField;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\MultiForm\Models\MultiFormStep;
use SilverStripe\ORM\DB;

/**
 * Antrag auf Kinderzuschlag (Familienkasse KiZ 1 + Anlagen) or Kurzantrag (KiZ 1-K).
 */
class Form extends BaseMultiForm
{
    private static $start_step = Form_Step1::class;

    public static function getFormName(): string
    {
        return 'Kinderzuschlag (Familienkasse KiZ 1 mit Anlagen / Kurzantrag)';
    }

    public function finish($data, $form)
    {
        if (parent::finish($data, $form) === false) {
            return false;
        }

        $allData = $this->mergeStepData($this->getActiveSteps());
        if ($allData === []) {
            return $this->controller->redirectBack();
        }

        $connection = DB::get_conn();

        try {
            $connection->transactionStart();

            $submission = self::buildSubmission($allData);

            $formSubmission = FormSubmission::create();
            $formSubmission->Email = $submission->ConfirmationEmail;
            $formSubmission->SubmissionID = $submission->ID;
            $formSubmission->Status = 0; // pending
            $formSubmission->ParentPageID = $this->controller->data()->ID;
            $formSubmission->write();

            $connection->transactionEnd();

            $this->controller->getRequest()->getSession()->set('FormSubmissionID', $formSubmission->ID);
        } catch (\Throwable $e) {
            $connection->transactionRollback();
            Injector::inst()->get(LoggerInterface::class)->error(
                'Kinderzuschlag submission failed: ' . $e->getMessage(),
                ['exception' => $e]
            );
            $form->sessionError(_t(__CLASS__ . '.SAVE_ERROR', 'Es gab ein Problem beim Speichern Ihrer Daten. Bitte versuchen Sie es erneut.'));

            return $this->controller->redirectBack();
        }

        return $this->controller->redirect($this->controller->Link('/finished'));
    }

    /**
     * Main submission + one SubmissionChild per child (prefix "Child{N}") + one SubmissionSelfEmployed per
     * self-employed person (prefix "SEApplicant" / "SEPartner"), from the merged data of the ACTIVE steps.
     *
     * @param array<string, mixed> $allData
     */
    public static function buildSubmission(array $allData): Submission
    {
        $main = [];
        foreach ($allData as $field => $value) {
            $field = (string) $field;
            if (preg_match('/^(Child\d|SEApplicant|SEPartner)/', $field)) {
                continue;
            }
            $main[$field] = $value;
        }

        $submission = Submission::create();
        $submission->update($main);
        $submission->write();

        if ($submission->isShortApplication()) {
            return $submission;
        }

        $childCount = min((int) $submission->ChildCount, Form_Bank::MAX_CHILDREN);
        for ($n = 1; $n <= $childCount; $n++) {
            $child = SubmissionChild::fromStepData($n, FieldConditions::unprefix($allData, 'Child' . $n));
            $child->SubmissionID = $submission->ID;
            $child->write();
        }

        $selfEmployed = [
            SubmissionSelfEmployed::PERSON_APPLICANT => (bool) $submission->ApplicantSelfEmployed,
            SubmissionSelfEmployed::PERSON_PARTNER => $submission->HasPartner === 'yes' && (bool) $submission->PartnerSelfEmployed,
        ];
        foreach ($selfEmployed as $person => $active) {
            if (!$active) {
                continue;
            }
            $record = SubmissionSelfEmployed::fromStepData(
                $person,
                FieldConditions::unprefix($allData, SubmissionSelfEmployed::PREFIXES[$person])
            );
            $record->SubmissionID = $submission->ID;
            $record->write();
        }

        return $submission;
    }

    /**
     * Preview only shows steps on the currently active path
     * (e.g. children beyond the chosen number or a removed partner disappear).
     */
    public function PreviewSteps(): ArrayList
    {
        $activeClasses = array_map(
            static fn (MultiFormStep $step): string => (string) $step->ClassName,
            $this->getActiveSteps()
        );

        return parent::PreviewSteps()->filterByCallback(
            static fn ($item): bool => in_array((string) $item->ClassName, $activeClasses, true)
        );
    }

    /**
     * Labels come from the step's own field titles (already translated for the current locale).
     * Required for the repeated steps: they share trait lang keys, so the base lookup
     * "<StepClass>.<Field>" would find nothing. Beiblatt amounts get "<row> – <month>".
     */
    public function resolveFieldLabel(string $stepClass, string $fieldKey): string
    {
        $meta = $this->stepMeta($stepClass);

        return $meta['labels'][$fieldKey] ?? $meta['titles'][$fieldKey] ?? parent::resolveFieldLabel($stepClass, $fieldKey);
    }

    public function resolveStepTitle(MultiFormStep $step): string
    {
        return $this->stepMeta((string) $step->ClassName, $step)['titles']['IntroText']
            ?? parent::resolveStepTitle($step);
    }

    /**
     * Type-aware preview values based on the $db of the model that stores the field
     * (Submission, SubmissionChild for "Child{N}*", SubmissionSelfEmployed for "SE{Applicant|Partner}*").
     */
    public function formatPreviewValue(string $stepClass, string $key, $value, ?MultiFormStep $step = null): string
    {
        $type = self::fieldType($key);

        if ($type === 'Date' && is_string($value) && $value !== '') {
            return $this->DateFormatting($value);
        }

        if ($type === 'Boolean') {
            if ($value === null || $value === '' || !$value) {
                return ''; // unchecked or cleared because hidden -> skipped in preview
            }

            return _t(__CLASS__ . '.BOOLEAN_YES', 'Ja');
        }

        if (is_string($type) && str_starts_with($type, 'Decimal') && is_scalar($value) && trim((string) $value) !== '') {
            $amount = SubmissionSelfEmployed::parseAmount((string) $value);

            return $amount === null ? (string) $value : SubmissionSelfEmployed::formatAmount($amount) . ' €';
        }

        // option label straight from the field source: translated if a lang file exists,
        // German default otherwise (base only resolves options that exist in a lang file)
        if (is_scalar($value) && $value !== '') {
            $label = $this->stepMeta($stepClass, $step)['options'][$key][(string) $value] ?? null;
            if ($label !== null) {
                return $label;
            }
        }

        return parent::formatPreviewValue($stepClass, $key, $value, $step);
    }

    /**
     * $db type of a step field in the model that stores it.
     */
    public static function fieldType(string $key): ?string
    {
        if (preg_match('/^Child\d(.+)$/', $key, $m)) {
            return SubmissionChild::config()->get('db')[$m[1]] ?? null;
        }

        if (preg_match('/^SE(?:Applicant|Partner)(.+)$/', $key, $m)) {
            return SubmissionSelfEmployed::config()->get('db')[$m[1]] ?? null;
        }

        return Submission::config()->get('db')[$key] ?? null;
    }

    /** @var array<string, array{titles: array<string, string>, labels: array<string, string>, options: array<string, array<string, string>>}> */
    private array $stepMetaCache = [];

    /**
     * Field titles, preview labels and dropdown sources of a step class.
     *
     * @return array{titles: array<string, string>, labels: array<string, string>, options: array<string, array<string, string>>}
     */
    private function stepMeta(string $stepClass, ?MultiFormStep $step = null): array
    {
        if (isset($this->stepMetaCache[$stepClass])) {
            return $this->stepMetaCache[$stepClass];
        }

        $meta = ['titles' => [], 'labels' => [], 'options' => []];
        $instance = $step ?? (is_a($stepClass, MultiFormStep::class, true) ? $stepClass::singleton() : null);

        if ($instance) {
            foreach ($instance->getFields()->flattenFields() as $field) {
                if (!$field instanceof FormField) {
                    continue;
                }

                $title = trim(strip_tags((string) $field->Title()));
                if ($title !== '') {
                    $meta['titles'][$field->getName()] = $title;
                }

                if ($field instanceof SingleSelectField) {
                    $meta['options'][$field->getName()] = array_map('strval', $field->getSource());
                }
            }

            // Beiblatt: "<row label> – <month>"
            if (method_exists($instance, 'rowLabel') && defined($stepClass . '::FIELD_PREFIX')) {
                $prefix = (string) constant($stepClass . '::FIELD_PREFIX');
                foreach (SubmissionSelfEmployed::LEDGER_ROWS as $row) {
                    for ($m = 1; $m <= 6; $m++) {
                        $name = $prefix . $row . 'M' . $m;
                        $meta['labels'][$name] = $instance->rowLabel($row) . ' – ' . ($meta['titles'][$name] ?? $m);
                    }
                    $note = $prefix . $row . 'Note';
                    $meta['labels'][$note] = $instance->rowLabel($row) . ' – ' . ($meta['titles'][$note] ?? '');
                }
            }
        }

        return $this->stepMetaCache[$stepClass] = $meta;
    }

    /**
     * Walks the step chain from the start step using each step's getNextStep(),
     * so branches the user abandoned (but which still exist in the session) are excluded.
     *
     * @return array<int, MultiFormStep>
     */
    public function getActiveSteps(): array
    {
        $stepsByClass = [];
        $steps = MultiFormStep::get()->filter('SessionID', $this->getMultiFormSession()->ID);

        foreach ($steps as $step) {
            $step->setForm($this);
            $stepsByClass[$step->ClassName] = $step;
        }

        $active = [];
        $class = $this->config()->get('start_step');

        while (is_string($class) && isset($stepsByClass[$class]) && !isset($active[$class])) {
            $step = $stepsByClass[$class];
            $active[$class] = $step;

            if ($step->isFinalStep()) {
                break;
            }

            $class = $step->getNextStep();
        }

        return array_values($active);
    }
}
