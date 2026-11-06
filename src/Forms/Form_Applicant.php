<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use SilverStripe\Forms\DateField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * KiZ 1 Punkt 1 (Name, Geburtsdatum, Anschrift) - used by the full Antrag and the Kurzantrag.
 */
class Form_Applicant extends MultiFormStep
{
    use StepTrait;

    private static $default_sort_order = 2;

    public function getFields(): FieldList
    {
        return FieldList::create(
            HeaderField::create('IntroText', _t(__CLASS__ . '.IntroText', 'Angaben zu Ihrer Person'), 4),
            TextField::create('LastName', _t(__CLASS__ . '.LastName', 'Familienname'))
                ->setMaxLength(60),
            TextField::create('FirstName', _t(__CLASS__ . '.FirstName', 'Vorname'))
                ->setMaxLength(60),
            DateField::create('BirthDate', _t(__CLASS__ . '.BirthDate', 'Geburtsdatum')),
            TextField::create('BirthName', _t(__CLASS__ . '.BirthName', 'Abweichender Geburtsname und/oder Familienname aus früherer Ehe/Lebenspartnerschaft (optional)'))
                ->setMaxLength(60),
            TextField::create('Title', _t(__CLASS__ . '.Title', 'Titel (optional)'))
                ->setMaxLength(25),
            TextField::create('Street', _t(__CLASS__ . '.Street', 'Straße, Hausnummer'))
                ->setMaxLength(60),
            TextField::create('PostalCode', _t(__CLASS__ . '.PostalCode', 'Postleitzahl'))
                ->setMaxLength(10),
            TextField::create('City', _t(__CLASS__ . '.City', 'Wohnort'))
                ->setMaxLength(40),
        );
    }

    public function getValidator(): RequiredFieldsValidator
    {
        return RequiredFieldsValidator::create([
            'LastName',
            'FirstName',
            'BirthDate',
            'Street',
            'PostalCode',
            'City',
        ]);
    }
}
