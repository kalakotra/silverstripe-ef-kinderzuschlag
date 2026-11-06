<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use SilverStripe\MultiForm\Models\MultiFormStep;
use UncleCheese\DisplayLogic\Forms\Wrapper;

/**
 * Antragsart (vollständiger Antrag KiZ 1 or Kurzantrag KiZ 1-K) and Kindergeld numbers.
 */
class Form_Step1 extends MultiFormStep
{
    use StepTrait;

    private static $default_sort_order = 1;

    /** Kurzantrag checks: every answer must be "no". */
    public const SHORT_CHECKS = [
        'ShortChangedHousehold',
        'ShortChangedAssets',
        'ShortChangedIncome',
        'ShortChangedHousing',
        'ShortLastWasShort',
    ];

    private static array $conditional_required = self::SHORT_CHECKS;

    public function getFields(): FieldList
    {
        $select = '-- ' . _t(__CLASS__ . '.DEFAULT_SELECT', 'Bitte auswählen') . ' --';
        $noYes = [
            'no' => _t(__CLASS__ . '.Answer.no', 'Nein'),
            'yes' => _t(__CLASS__ . '.Answer.yes', 'Ja'),
        ];

        return FieldList::create(
            HeaderField::create('IntroText', _t(__CLASS__ . '.IntroText', 'Antrag auf Kinderzuschlag'), 4),
            LiteralField::create('GeneralNotice', '<div class="alert alert-info">' . _t(__CLASS__ . '.GeneralNotice', 'Den Kinderzuschlag erhalten Eltern, die genug für sich selbst verdienen, deren Einkommen aber nicht oder nur knapp für die ganze Familie reicht. Er wird ab dem Monat der Antragstellung für sechs Monate bewilligt. Antragsteller(in) ist der Elternteil, der das Kindergeld erhält.') . '</div>'),
            DropdownField::create('ApplicationType', _t(__CLASS__ . '.ApplicationType', 'Welchen Antrag möchten Sie stellen?'))
                ->setSource([
                    'full' => _t(__CLASS__ . '.ApplicationType.full', 'Antrag auf Kinderzuschlag (vollständiger Antrag mit Anlagen)'),
                    'short' => _t(__CLASS__ . '.ApplicationType.short', 'Kurzantrag bei unveränderten Verhältnissen (Weiterbewilligung)'),
                ])
                ->setEmptyString($select),
            Wrapper::create(
                LiteralField::create('ShortNotice', '<div class="alert alert-warning">' . _t(__CLASS__ . '.ShortNotice', 'Den Kurzantrag können Sie nur verwenden, wenn Sie bereits Kinderzuschlag erhalten und jede der folgenden Fragen mit „Nein“ beantworten können. In den letzten 6 Monaten haben sich meine Verhältnisse wesentlich geändert, weil …') . '</div>'),
                DropdownField::create('ShortChangedHousehold', _t(__CLASS__ . '.ShortChangedHousehold', '… sich die Größe meiner Familie (Bedarfsgemeinschaft) geändert hat'))
                    ->setSource($noYes)->setEmptyString($select),
                DropdownField::create('ShortChangedAssets', _t(__CLASS__ . '.ShortChangedAssets', '… sich die Höhe des Vermögens so verändert hat, dass erhebliches Vermögen vorliegt bzw. nicht mehr vorliegt'))
                    ->setSource($noYes)->setEmptyString($select),
                DropdownField::create('ShortChangedIncome', _t(__CLASS__ . '.ShortChangedIncome', '… sich das Gesamtnettoeinkommen meiner Familie (Eltern und Kinder, auch z. B. Arbeitslosengeld oder Krankengeld) um mehr als 15 % erhöht oder verringert hat'))
                    ->setSource($noYes)->setEmptyString($select),
                DropdownField::create('ShortChangedHousing', _t(__CLASS__ . '.ShortChangedHousing', '… sich die Wohnkosten meiner Familie um mehr als 15 % erhöht oder verringert haben'))
                    ->setSource($noYes)->setEmptyString($select),
                DropdownField::create('ShortLastWasShort', _t(__CLASS__ . '.ShortLastWasShort', 'War Ihr letzter Antrag auf Kinderzuschlag ein Kurzantrag?'))
                    ->setSource($noYes)->setEmptyString($select),
            )
                ->displayIf('ApplicationType')->isEqualTo('short')
                ->end(),
            TextField::create('KindergeldNumber', _t(__CLASS__ . '.KindergeldNumber', 'Kindergeldnummer (optional)'))
                ->setMaxLength(11)
                ->setDescription(_t(__CLASS__ . '.KindergeldNumberDescription', 'Drei Ziffern, die Buchstaben FK und sechs Ziffern, z. B. 123FK456789 (siehe Kindergeldbescheid).')),
            TextField::create('SecondApplicantKGNumber', _t(__CLASS__ . '.SecondApplicantKGNumber', 'Kindergeldnummer der zweiten antragstellenden Person (optional)'))
                ->setMaxLength(11)
                ->setDescription(_t(__CLASS__ . '.SecondApplicantKGNumberDescription', 'Nur wenn das Kindergeld für die Kinder teils an Sie und teils an Ihre(n) Partner(in) gezahlt wird. Ihr(e) Partner(in) unterschreibt den Antrag dann zusätzlich selbst.')),
        );
    }

    public function getValidator(): RequiredFieldsValidator
    {
        return RequiredFieldsValidator::create(['ApplicationType']);
    }

    /**
     * The Kurzantrag is only allowed when every check is answered with "no".
     */
    public function validateStep($data, $form)
    {
        if (!$this->validateConditionalRequired($data, $form)) {
            return false;
        }

        $values = $form->getData();
        if (($values['ApplicationType'] ?? null) !== 'short') {
            return parent::validateStep($data, $form);
        }

        foreach (self::SHORT_CHECKS as $fieldName) {
            if (($values[$fieldName] ?? null) === 'yes') {
                $form->sessionFieldError(
                    _t(__CLASS__ . '.ShortNotAllowed', 'Mit dieser Antwort ist ein Kurzantrag nicht möglich. Bitte wählen Sie den vollständigen Antrag.'),
                    $fieldName
                );
                $form->sessionMessage(_t(__CLASS__ . '.ShortNotAllowed', 'Mit dieser Antwort ist ein Kurzantrag nicht möglich. Bitte wählen Sie den vollständigen Antrag.'));

                return false;
            }
        }

        return parent::validateStep($data, $form);
    }
}
