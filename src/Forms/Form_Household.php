<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

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
 * KiZ 1 Punkt 6 (other children under 25 without Kindergeld, not permanently in the household)
 * and Punkt 7 (further persons living permanently in the household).
 */
class Form_Household extends MultiFormStep
{
    use StepTrait;

    private static $default_sort_order = 30;

    private static array $conditional_required = [
        'OtherChild1Name', 'OtherChild1BirthDate', 'OtherChild1Presence',
        'OtherChild2Name', 'OtherChild2BirthDate', 'OtherChild2Presence',
        'OtherChild3Name', 'OtherChild3BirthDate', 'OtherChild3Presence',
        'HouseholdPerson1Name', 'HouseholdPerson1BirthDate', 'HouseholdPerson1RelationApplicant',
        'HouseholdPerson2Name', 'HouseholdPerson2BirthDate', 'HouseholdPerson2RelationApplicant',
        'HouseholdPerson3Name', 'HouseholdPerson3BirthDate', 'HouseholdPerson3RelationApplicant',
        'HouseholdPerson4Name', 'HouseholdPerson4BirthDate', 'HouseholdPerson4RelationApplicant',
        'HouseholdPerson5Name', 'HouseholdPerson5BirthDate', 'HouseholdPerson5RelationApplicant',
    ];

    public function getFields(): FieldList
    {
        $select = '-- ' . _t(__CLASS__ . '.DEFAULT_SELECT', 'Bitte auswählen') . ' --';

        return FieldList::create(
            HeaderField::create('IntroText', _t(__CLASS__ . '.IntroText', 'Weitere Personen im Haushalt'), 4),
            HeaderField::create('OtherChildrenHeader', _t(__CLASS__ . '.OtherChildrenHeader', 'Weitere Kinder unter 25 Jahren ohne Kindergeld, die sich nicht ständig in Ihrem Haushalt aufhalten'), 5),
            LiteralField::create('OtherChildrenNotice', '<p class="small">' . _t(__CLASS__ . '.OtherChildrenNotice', 'Neben den Kindern, für die Sie Kinderzuschlag beantragen: Kinder unter 25 Jahren, für die Sie und Ihr(e) Partner(in) kein Kindergeld erhalten und die sich zeitweise bei Ihnen aufhalten. Bei etwa gleichen Teilen genügt die Angabe „13-17 Tage“, sonst die genaue Anzahl der Tage. Die Familienkasse benötigt für diese Kinder ebenfalls eine „Anlage Kind“.') . '</p>'),
            DropdownField::create('OtherChildrenCount', _t(__CLASS__ . '.OtherChildrenCount', 'Anzahl dieser Kinder'))
                ->setSource(['0' => '0', '1' => '1', '2' => '2', '3' => '3'])
                ->setEmptyString($select),
            Wrapper::create(
                TextField::create('OtherChild1Name', _t(__CLASS__ . '.OtherChild1Name', 'Kind 1: Familienname, Vorname'))->setMaxLength(45),
                DateField::create('OtherChild1BirthDate', _t(__CLASS__ . '.OtherChild1BirthDate', 'Kind 1: Geburtsdatum')),
                TextField::create('OtherChild1Presence', _t(__CLASS__ . '.OtherChild1Presence', 'Kind 1: Grund und Dauer der Anwesenheit'))->setMaxLength(40),
            )
                ->displayIf('OtherChildrenCount')->isEqualTo('1')
                ->orIf('OtherChildrenCount')->isEqualTo('2')
                ->orIf('OtherChildrenCount')->isEqualTo('3')
                ->end(),
            Wrapper::create(
                TextField::create('OtherChild2Name', _t(__CLASS__ . '.OtherChild2Name', 'Kind 2: Familienname, Vorname'))->setMaxLength(45),
                DateField::create('OtherChild2BirthDate', _t(__CLASS__ . '.OtherChild2BirthDate', 'Kind 2: Geburtsdatum')),
                TextField::create('OtherChild2Presence', _t(__CLASS__ . '.OtherChild2Presence', 'Kind 2: Grund und Dauer der Anwesenheit'))->setMaxLength(40),
            )
                ->displayIf('OtherChildrenCount')->isEqualTo('2')
                ->orIf('OtherChildrenCount')->isEqualTo('3')
                ->end(),
            Wrapper::create(
                TextField::create('OtherChild3Name', _t(__CLASS__ . '.OtherChild3Name', 'Kind 3: Familienname, Vorname'))->setMaxLength(45),
                DateField::create('OtherChild3BirthDate', _t(__CLASS__ . '.OtherChild3BirthDate', 'Kind 3: Geburtsdatum')),
                TextField::create('OtherChild3Presence', _t(__CLASS__ . '.OtherChild3Presence', 'Kind 3: Grund und Dauer der Anwesenheit'))->setMaxLength(40),
            )
                ->displayIf('OtherChildrenCount')->isEqualTo('3')
                ->end(),
            HeaderField::create('HouseholdPersonsHeader', _t(__CLASS__ . '.HouseholdPersonsHeader', 'Weitere Personen, die ständig in Ihrem Haushalt wohnen'), 5),
            LiteralField::create('HouseholdPersonsNotice', '<p class="small">' . _t(__CLASS__ . '.HouseholdPersonsNotice', 'Neben Ihnen, Ihrer Partnerin / Ihrem Partner und den Kindern, für die Sie Kinderzuschlag beantragen – z. B. eigene unter 25 Jahre alte unverheiratete Kinder ohne Kindergeldanspruch, eigene über 25 Jahre alte oder verheiratete Kinder, Stief-, Pflege- oder Enkelkinder, Eltern, Geschwister.') . '</p>'),
            DropdownField::create('HouseholdPersonsCount', _t(__CLASS__ . '.HouseholdPersonsCount', 'Anzahl dieser Personen'))
                ->setSource(['0' => '0', '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5'])
                ->setEmptyString($select),
            Wrapper::create(
                TextField::create('HouseholdPerson1Name', _t(__CLASS__ . '.HouseholdPerson1Name', 'Person 1: Familienname, Vorname'))->setMaxLength(45),
                DateField::create('HouseholdPerson1BirthDate', _t(__CLASS__ . '.HouseholdPerson1BirthDate', 'Person 1: Geburtsdatum')),
                TextField::create('HouseholdPerson1RelationApplicant', _t(__CLASS__ . '.HouseholdPerson1RelationApplicant', 'Person 1: Verwandtschaftsverhältnis zu mir'))->setMaxLength(20),
                TextField::create('HouseholdPerson1RelationPartner', _t(__CLASS__ . '.HouseholdPerson1RelationPartner', 'Person 1: Verwandtschaftsverhältnis zum/zur Partner(in) (optional)'))->setMaxLength(20),
            )
                ->displayIf('HouseholdPersonsCount')->isEqualTo('1')
                ->orIf('HouseholdPersonsCount')->isEqualTo('2')
                ->orIf('HouseholdPersonsCount')->isEqualTo('3')
                ->orIf('HouseholdPersonsCount')->isEqualTo('4')
                ->orIf('HouseholdPersonsCount')->isEqualTo('5')
                ->end(),
            Wrapper::create(
                TextField::create('HouseholdPerson2Name', _t(__CLASS__ . '.HouseholdPerson2Name', 'Person 2: Familienname, Vorname'))->setMaxLength(45),
                DateField::create('HouseholdPerson2BirthDate', _t(__CLASS__ . '.HouseholdPerson2BirthDate', 'Person 2: Geburtsdatum')),
                TextField::create('HouseholdPerson2RelationApplicant', _t(__CLASS__ . '.HouseholdPerson2RelationApplicant', 'Person 2: Verwandtschaftsverhältnis zu mir'))->setMaxLength(20),
                TextField::create('HouseholdPerson2RelationPartner', _t(__CLASS__ . '.HouseholdPerson2RelationPartner', 'Person 2: Verwandtschaftsverhältnis zum/zur Partner(in) (optional)'))->setMaxLength(20),
            )
                ->displayIf('HouseholdPersonsCount')->isEqualTo('2')
                ->orIf('HouseholdPersonsCount')->isEqualTo('3')
                ->orIf('HouseholdPersonsCount')->isEqualTo('4')
                ->orIf('HouseholdPersonsCount')->isEqualTo('5')
                ->end(),
            Wrapper::create(
                TextField::create('HouseholdPerson3Name', _t(__CLASS__ . '.HouseholdPerson3Name', 'Person 3: Familienname, Vorname'))->setMaxLength(45),
                DateField::create('HouseholdPerson3BirthDate', _t(__CLASS__ . '.HouseholdPerson3BirthDate', 'Person 3: Geburtsdatum')),
                TextField::create('HouseholdPerson3RelationApplicant', _t(__CLASS__ . '.HouseholdPerson3RelationApplicant', 'Person 3: Verwandtschaftsverhältnis zu mir'))->setMaxLength(20),
                TextField::create('HouseholdPerson3RelationPartner', _t(__CLASS__ . '.HouseholdPerson3RelationPartner', 'Person 3: Verwandtschaftsverhältnis zum/zur Partner(in) (optional)'))->setMaxLength(20),
            )
                ->displayIf('HouseholdPersonsCount')->isEqualTo('3')
                ->orIf('HouseholdPersonsCount')->isEqualTo('4')
                ->orIf('HouseholdPersonsCount')->isEqualTo('5')
                ->end(),
            Wrapper::create(
                TextField::create('HouseholdPerson4Name', _t(__CLASS__ . '.HouseholdPerson4Name', 'Person 4: Familienname, Vorname'))->setMaxLength(45),
                DateField::create('HouseholdPerson4BirthDate', _t(__CLASS__ . '.HouseholdPerson4BirthDate', 'Person 4: Geburtsdatum')),
                TextField::create('HouseholdPerson4RelationApplicant', _t(__CLASS__ . '.HouseholdPerson4RelationApplicant', 'Person 4: Verwandtschaftsverhältnis zu mir'))->setMaxLength(20),
                TextField::create('HouseholdPerson4RelationPartner', _t(__CLASS__ . '.HouseholdPerson4RelationPartner', 'Person 4: Verwandtschaftsverhältnis zum/zur Partner(in) (optional)'))->setMaxLength(20),
            )
                ->displayIf('HouseholdPersonsCount')->isEqualTo('4')
                ->orIf('HouseholdPersonsCount')->isEqualTo('5')
                ->end(),
            Wrapper::create(
                TextField::create('HouseholdPerson5Name', _t(__CLASS__ . '.HouseholdPerson5Name', 'Person 5: Familienname, Vorname'))->setMaxLength(45),
                DateField::create('HouseholdPerson5BirthDate', _t(__CLASS__ . '.HouseholdPerson5BirthDate', 'Person 5: Geburtsdatum')),
                TextField::create('HouseholdPerson5RelationApplicant', _t(__CLASS__ . '.HouseholdPerson5RelationApplicant', 'Person 5: Verwandtschaftsverhältnis zu mir'))->setMaxLength(20),
                TextField::create('HouseholdPerson5RelationPartner', _t(__CLASS__ . '.HouseholdPerson5RelationPartner', 'Person 5: Verwandtschaftsverhältnis zum/zur Partner(in) (optional)'))->setMaxLength(20),
            )
                ->displayIf('HouseholdPersonsCount')->isEqualTo('5')
                ->end(),
        );
    }

    public function getValidator(): RequiredFieldsValidator
    {
        return RequiredFieldsValidator::create([
            'OtherChildrenCount',
            'HouseholdPersonsCount',
        ]);
    }
}
