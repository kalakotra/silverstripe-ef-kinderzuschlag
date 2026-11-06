<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use SilverStripe\MultiForm\Models\MultiFormStep;
use UncleCheese\DisplayLogic\Forms\Wrapper;

/**
 * KiZ 1 Punkt 1 (Geschlecht, Staatsangehörigkeit, Telefon, Familienstand) and Punkt 2 (Partner(in)),
 * plus the partner column of KiZ 1-AnA Punkt 1 and the partner's Mehrbedarfe (Punkt 4, KiZ 7).
 */
class Form_Family extends MultiFormStep
{
    use StepTrait;

    private static $default_sort_order = 3;

    private static array $conditional_required = [
        'PartnerLastName',
        'PartnerFirstName',
        'PartnerNationality',
        'PartnerBirthDate',
        'PartnerDueDate',
    ];

    public function getFields(): FieldList
    {
        $select = '-- ' . _t(__CLASS__ . '.DEFAULT_SELECT', 'Bitte auswählen') . ' --';

        return FieldList::create(
            HeaderField::create('IntroText', _t(__CLASS__ . '.IntroText', 'Familienstand und Partner(in)'), 4),
            DropdownField::create('Gender', _t(__CLASS__ . '.Gender', 'Geschlecht'))
                ->setSource([
                    'female' => _t(__CLASS__ . '.Gender.female', 'weiblich'),
                    'male' => _t(__CLASS__ . '.Gender.male', 'männlich'),
                    'diverse' => _t(__CLASS__ . '.Gender.diverse', 'divers'),
                    'unspecified' => _t(__CLASS__ . '.Gender.unspecified', 'ohne Angabe'),
                ])
                ->setEmptyString($select),
            TextField::create('Nationality', _t(__CLASS__ . '.Nationality', 'Staatsangehörigkeit'))
                ->setMaxLength(45)
                ->setDescription(_t(__CLASS__ . '.NationalityDescription', 'Bei einer anderen als der deutschen Staatsangehörigkeit oder der eines EU-/EWR-Staates bzw. der Schweiz fügen Sie bitte eine Kopie Ihres Aufenthaltstitels bei.')),
            TextField::create('Phone', _t(__CLASS__ . '.Phone', 'Telefonnummer für Rückfragen tagsüber (optional)'))
                ->setMaxLength(30),
            DropdownField::create('MaritalStatus', _t(__CLASS__ . '.MaritalStatus', 'Familienstand'))
                ->setSource([
                    'single' => _t(__CLASS__ . '.MaritalStatus.single', 'ledig'),
                    'married' => _t(__CLASS__ . '.MaritalStatus.married', 'verheiratet/verpartnert'),
                    'separated' => _t(__CLASS__ . '.MaritalStatus.separated', 'getrennt lebend'),
                    'divorced' => _t(__CLASS__ . '.MaritalStatus.divorced', 'geschieden/Partnerschaft aufgehoben'),
                    'widowed' => _t(__CLASS__ . '.MaritalStatus.widowed', 'verwitwet'),
                ])
                ->setEmptyString($select),
            Wrapper::create(
                DateField::create('MaritalSince', _t(__CLASS__ . '.MaritalSince', 'Familienstand seit (optional)')),
            )
                ->displayIf('MaritalStatus')->isEqualTo('married')
                ->orIf('MaritalStatus')->isEqualTo('separated')
                ->orIf('MaritalStatus')->isEqualTo('divorced')
                ->orIf('MaritalStatus')->isEqualTo('widowed')
                ->end(),
            DropdownField::create('HasPartner', _t(__CLASS__ . '.HasPartner', 'Leben Sie mit einer Partnerin / einem Partner in einem gemeinsamen Haushalt?'))
                ->setSource([
                    'no' => _t(__CLASS__ . '.HasPartner.no', 'Nein'),
                    'yes' => _t(__CLASS__ . '.HasPartner.yes', 'Ja'),
                ])
                ->setEmptyString($select)
                ->setDescription(_t(__CLASS__ . '.HasPartnerDescription', 'Gemeint ist die Person, mit der Sie gemeinsam den Lebensunterhalt Ihrer Familie bestreiten – egal ob verheiratet oder nicht.')),
            Wrapper::create(
                HeaderField::create('PartnerHeader', _t(__CLASS__ . '.PartnerHeader', 'Angaben zu Ihrer Partnerin / Ihrem Partner'), 5),
                TextField::create('PartnerLastName', _t(__CLASS__ . '.PartnerLastName', 'Familienname'))
                    ->setMaxLength(40),
                TextField::create('PartnerFirstName', _t(__CLASS__ . '.PartnerFirstName', 'Vorname'))
                    ->setMaxLength(30),
                TextField::create('PartnerBirthName', _t(__CLASS__ . '.PartnerBirthName', 'Abweichender Geburtsname (optional)'))
                    ->setMaxLength(25),
                TextField::create('PartnerNationality', _t(__CLASS__ . '.PartnerNationality', 'Staatsangehörigkeit'))
                    ->setMaxLength(22),
                DateField::create('PartnerBirthDate', _t(__CLASS__ . '.PartnerBirthDate', 'Geburtsdatum')),
                LiteralField::create('PartnerSituationNotice', '<p>' . _t(__CLASS__ . '.PartnerSituationNotice', 'Mein(e) Partner(in) … (Zutreffendes bitte ankreuzen und nachweisen)') . '</p>'),
                CheckboxField::create('PartnerInTraining', _t(__CLASS__ . '.PartnerInTraining', 'befindet sich derzeit in einer Schul- oder Berufsausbildung oder in einem Studium')),
                CheckboxField::create('PartnerInpatient', _t(__CLASS__ . '.PartnerInpatient', 'befindet sich derzeit in einer stationären Einrichtung')),
                CheckboxField::create('PartnerForeignBenefit', _t(__CLASS__ . '.PartnerForeignBenefit', 'erhält für unser(e) Kind(er) anstatt Kindergeld eine Geldleistung von einer Stelle außerhalb von Deutschland oder hat diese beantragt')),
                LiteralField::create('PartnerNeedNotice', '<p>' . _t(__CLASS__ . '.PartnerNeedNotice', 'Mehrbedarfe Ihrer Partnerin / Ihres Partners (freiwillig, bitte nachweisen):') . '</p>'),
                CheckboxField::create('PartnerNeedPregnancy', _t(__CLASS__ . '.PartnerNeedPregnancy', 'Schwangerschaft')),
                Wrapper::create(
                    DateField::create('PartnerDueDate', _t(__CLASS__ . '.PartnerDueDate', 'Voraussichtlicher Entbindungstermin')),
                )
                    ->displayIf('PartnerNeedPregnancy')->isChecked()
                    ->end(),
                CheckboxField::create('PartnerNeedNutrition', _t(__CLASS__ . '.PartnerNeedNutrition', 'Kostenaufwändige Ernährung (die „Anlage zum Mehrbedarf bei kostenaufwändiger Ernährung“ KiZ 7 wird beigefügt; Ihr(e) Partner(in) unterschreibt Punkt 2 selbst)')),
                CheckboxField::create('PartnerNeedOther', _t(__CLASS__ . '.PartnerNeedOther', 'Anderer Mehrbedarf (z. B. Schwerbehinderung mit Merkzeichen „G“ bei voller Erwerbsminderung, Leistungen zur Teilhabe am Arbeitsleben)')),
            )
                ->displayIf('HasPartner')->isEqualTo('yes')
                ->end(),
        );
    }

    public function getValidator(): RequiredFieldsValidator
    {
        return RequiredFieldsValidator::create([
            'Gender',
            'Nationality',
            'MaritalStatus',
            'HasPartner',
        ]);
    }
}
