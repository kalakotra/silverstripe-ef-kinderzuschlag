<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use Kalakotra\EFormKinderzuschlag\Model\FieldConditions;
use Kalakotra\EFormKinderzuschlag\Model\Submission;
use Kalakotra\EFormKinderzuschlag\Model\SubmissionChild;
use Kalakotra\EFormKinderzuschlag\Model\SubmissionSelfEmployed;
use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Behaviour shared by all Kinderzuschlag steps (trait instead of an abstract base class,
 * because DbBuild instantiates every DataObject subclass via singleton()).
 *
 * - saveData(): clears values of fields hidden by display-logic
 * - validateStep(): enforces `conditional_required` fields only while they are visible
 * - getNextStep(): one flow table for the whole form (FLOW + isFlowStepActive gates)
 *
 * Dependency rules: Submission::$field_conditions, plus SubmissionChild / SubmissionSelfEmployed rules
 * for steps that declare FIELD_PREFIX + RULE_MODEL (their field names are "<prefix><model field>").
 * Using classes may declare: private static array $conditional_required = ['FieldName', ...];
 */
trait StepTrait
{
    /**
     * Step order of the complete form. Inactive steps (gates in isFlowStepActive()) are skipped.
     */
    private const FLOW = [
        Form_Step1::class,
        Form_Applicant::class,
        Form_Family::class,
        Form_Bank::class,
        Form_Child1::class,
        Form_ChildIncome1::class,
        Form_Child2::class,
        Form_ChildIncome2::class,
        Form_Child3::class,
        Form_ChildIncome3::class,
        Form_Child4::class,
        Form_ChildIncome4::class,
        Form_Child5::class,
        Form_ChildIncome5::class,
        Form_Child6::class,
        Form_ChildIncome6::class,
        Form_Household::class,
        Form_Living::class,
        Form_IncomeApplicant::class,
        Form_IncomePartner::class,
        Form_SelfEmployedApplicant::class,
        Form_LedgerApplicant::class,
        Form_SelfEmployedPartner::class,
        Form_LedgerPartner::class,
        Form_Assets::class,
        Form_Preview::class,
    ];

    /** Child step class => child number. */
    private const CHILD_STEPS = [
        Form_Child1::class => 1, Form_ChildIncome1::class => 1,
        Form_Child2::class => 2, Form_ChildIncome2::class => 2,
        Form_Child3::class => 3, Form_ChildIncome3::class => 3,
        Form_Child4::class => 4, Form_ChildIncome4::class => 4,
        Form_Child5::class => 5, Form_ChildIncome5::class => 5,
        Form_Child6::class => 6, Form_ChildIncome6::class => 6,
    ];

    /**
     * @param array<string, mixed>|mixed $data
     */
    public function saveData($data)
    {
        if (is_array($data)) {
            $data = FieldConditions::apply($data, static::fieldConditionRules(), true);
        }

        return parent::saveData($data);
    }

    /**
     * @param array<string, mixed>|mixed $data
     */
    public function validateStep($data, $form)
    {
        return $this->validateConditionalRequired($data, $form) && parent::validateStep($data, $form);
    }

    public function getNextStep()
    {
        $index = array_search(static::class, self::FLOW, true);
        if ($index === false) {
            return false;
        }

        foreach (array_slice(self::FLOW, $index + 1) as $class) {
            if ($this->isFlowStepActive($class)) {
                return $class;
            }
        }

        return false;
    }

    /**
     * Gates of the flow. Values are read from the step that owns them (own data via loadData(),
     * the cross-step cache of multiform could be stale right after saveData()).
     */
    protected function isFlowStepActive(string $class): bool
    {
        if (in_array($class, [Form_Step1::class, Form_Applicant::class, Form_Preview::class], true)) {
            return true;
        }

        if ($this->flowValue(Form_Step1::class, 'ApplicationType') !== 'full') {
            return false;
        }

        if (isset(self::CHILD_STEPS[$class])) {
            return (int) $this->flowValue(Form_Bank::class, 'ChildCount') >= self::CHILD_STEPS[$class];
        }

        $hasPartner = $this->flowValue(Form_Family::class, 'HasPartner') === 'yes';

        return match ($class) {
            Form_IncomePartner::class => $hasPartner,
            Form_SelfEmployedApplicant::class, Form_LedgerApplicant::class
                => (bool) $this->flowValue(Form_IncomeApplicant::class, 'ApplicantSelfEmployed'),
            Form_SelfEmployedPartner::class, Form_LedgerPartner::class
                => $hasPartner && (bool) $this->flowValue(Form_IncomePartner::class, 'PartnerSelfEmployed'),
            Form_Assets::class => $this->flowValue(Form_Living::class, 'HasAssets') === 'yes',
            default => true,
        };
    }

    protected function flowValue(string $stepClass, string $fieldName): mixed
    {
        if ($stepClass === static::class) {
            $data = $this->loadData();

            return is_array($data) ? ($data[$fieldName] ?? null) : null;
        }

        return $this->valueFromStep($stepClass, $fieldName);
    }

    /**
     * Reads a value saved by another step of the same session.
     * Filters by exact ClassName: a DataList on a step class would also return its subclasses.
     */
    protected function valueFromStep(string $stepClass, string $fieldName): mixed
    {
        if (!$this->SessionID) {
            return null;
        }

        $step = MultiFormStep::get()
            ->filter(['SessionID' => $this->SessionID, 'ClassName' => $stepClass])
            ->first();

        if (!$step) {
            return null;
        }

        $data = $step->loadData();

        return is_array($data) ? ($data[$fieldName] ?? null) : null;
    }

    /**
     * Steps overriding validateStep() call this to keep the conditional-required check.
     *
     * @param array<string, mixed>|mixed $data
     */
    protected function validateConditionalRequired($data, $form): bool
    {
        $required = (array) ($this->config()->get('conditional_required') ?? []);
        if ($required === []) {
            return true;
        }

        // Use the form's normalised data, NOT the raw request ($data): unchecked checkboxes are
        // missing from the request, which made their dependent fields look active (and the
        // resulting error was attached to a hidden field -> silent redirect back to the same step).
        $data = $form->getData();
        $rules = static::fieldConditionRules();
        $missing = [];

        foreach ($required as $fieldName) {
            if (!FieldConditions::isActive($fieldName, $data, $rules)) {
                continue;
            }

            $value = $data[$fieldName] ?? null;
            if (is_scalar($value) && trim((string) $value) !== '') {
                continue;
            }

            $this->addFieldError($form, $fieldName, $missing);
        }

        $this->addRequiredSummary($form, $missing);

        return $missing === [];
    }

    /**
     * Euro amounts ("1.250,00", "1250.5", "-30") - empty is allowed.
     *
     * @param array<int, string> $fieldNames
     */
    protected function validateAmountFields($form, array $fieldNames): bool
    {
        $data = $form->getData();
        $valid = true;

        foreach ($fieldNames as $fieldName) {
            $value = trim((string) ($data[$fieldName] ?? ''));
            if ($value === '' || SubmissionSelfEmployed::parseAmount($value) !== null) {
                continue;
            }

            $valid = false;
            $form->sessionFieldError(
                _t(__TRAIT__ . '.AMOUNT_INVALID', 'Bitte geben Sie einen Betrag in Euro ein, z. B. 1.250,00.'),
                $fieldName
            );
        }

        if (!$valid) {
            $form->sessionMessage(_t(__TRAIT__ . '.AMOUNT_SUMMARY', 'Bitte prüfen Sie die markierten Beträge.'));
        }

        return $valid;
    }

    /**
     * @param array<int, string> $missing
     */
    protected function addFieldError($form, string $fieldName, array &$missing): void
    {
        $field = $form->Fields()->dataFieldByName($fieldName);
        $label = $field ? strip_tags((string) $field->Title()) : $fieldName;
        $missing[] = $label;

        $form->sessionFieldError(
            _t(__TRAIT__ . '.REQUIRED', '"{name}" ist erforderlich.', ['name' => $label]),
            $fieldName
        );
    }

    /**
     * Form-level summary, so a failed validation is never invisible.
     *
     * @param array<int, string> $missing
     */
    protected function addRequiredSummary($form, array $missing): void
    {
        if ($missing === []) {
            return;
        }

        $form->sessionMessage(_t(
            __TRAIT__ . '.REQUIRED_SUMMARY',
            'Bitte füllen Sie die folgenden Pflichtfelder aus: {fields}',
            ['fields' => implode(', ', $missing)]
        ));
    }

    /**
     * Rules of the main Submission plus, for entity steps (FIELD_PREFIX + RULE_MODEL),
     * the entity model rules with prefixed field names.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function fieldConditionRules(): array
    {
        $rules = (array) Submission::config()->get('field_conditions');
        $prefixConstant = static::class . '::FIELD_PREFIX';
        $modelConstant = static::class . '::RULE_MODEL';

        if (!defined($prefixConstant) || !defined($modelConstant)) {
            return $rules;
        }

        $model = constant($modelConstant);
        if (!in_array($model, [SubmissionChild::class, SubmissionSelfEmployed::class], true)) {
            return $rules;
        }

        return array_merge($rules, FieldConditions::prefix(
            (array) $model::config()->get('field_conditions'),
            (string) constant($prefixConstant)
        ));
    }
}
