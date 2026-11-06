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
use UncleCheese\DisplayLogic\Forms\Wrapper;

/**
 * KiZ 5a page 1-2 (Anlage zum Einkommen aus selbständiger Tätigkeit) for the applicant or the partner.
 * Field names "<FIELD_PREFIX><SubmissionSelfEmployed field>" (SEApplicant / SEPartner); labels via __TRAIT__ keys.
 *
 * Using classes define: FIELD_PREFIX, RULE_MODEL, $default_sort_order, $conditional_required, introText().
 */
trait SelfEmployedStepTrait
{
    abstract protected function introText(): string;

    public function getFields(): FieldList
    {
        $p = static::FIELD_PREFIX;
        $select = '-- ' . _t(__TRAIT__ . '.DEFAULT_SELECT', 'Bitte auswählen') . ' --';
        $noYes = [
            'no' => _t(__TRAIT__ . '.Answer.no', 'Nein'),
            'yes' => _t(__TRAIT__ . '.Answer.yes', 'Ja'),
        ];

        return FieldList::create(
            HeaderField::create('IntroText', $this->introText(), 4),
            LiteralField::create($p . 'Notice', '<div class="alert alert-info">' . _t(__TRAIT__ . '.Notice', 'Grundlage sind die tatsächlichen Einnahmen und Ausgaben in den letzten sechs Monaten vor dem Antragsmonat. Steuerrechtliche Regelungen (z. B. Abschreibungen, Pauschalen) finden keine Anwendung. Gründungszuschüsse sind keine Betriebseinnahmen.') . '</div>'),
            TextField::create($p . 'Activity', _t(__TRAIT__ . '.Activity', 'Gewerbeart bzw. Tätigkeit'))
                ->setMaxLength(80),
            DateField::create($p . 'ActivityStart', _t(__TRAIT__ . '.ActivityStart', 'Beginn der Tätigkeit')),
            DateField::create($p . 'ActivityEnd', _t(__TRAIT__ . '.ActivityEnd', 'Ggf. Ende der Tätigkeit (optional)')),
            TextField::create($p . 'LegalForm', _t(__TRAIT__ . '.LegalForm', 'Rechtsform des Unternehmens (z. B. Einzelunternehmen, GmbH, Ein-Mann-GmbH, KG, Ltd.)'))
                ->setMaxLength(80),
            TextField::create($p . 'BusinessAddress', _t(__TRAIT__ . '.BusinessAddress', 'Betriebssitz (Straße/Platz, Hausnummer, Postleitzahl, Ort)'))
                ->setMaxLength(100),
            DropdownField::create($p . 'Grant', _t(__TRAIT__ . '.Grant', 'Beziehen Sie einen Gründungszuschuss oder andere staatliche Hilfen?'))
                ->setSource($noYes)
                ->setEmptyString($select),
            Wrapper::create(
                TextField::create($p . 'GrantType', _t(__TRAIT__ . '.GrantType', 'Art der Zuwendung'))
                    ->setMaxLength(70),
                TextField::create($p . 'GrantPayer', _t(__TRAIT__ . '.GrantPayer', 'Die Zuwendung wird gezahlt von'))
                    ->setMaxLength(70),
                TextField::create($p . 'GrantMonthly', _t(__TRAIT__ . '.GrantMonthly', 'In Höhe von monatlich (Euro, optional)'))
                    ->setMaxLength(12),
                DateField::create($p . 'GrantFrom', _t(__TRAIT__ . '.GrantFrom', 'Monatlich von (optional)')),
                DateField::create($p . 'GrantUntil', _t(__TRAIT__ . '.GrantUntil', 'Monatlich bis (optional)')),
                TextField::create($p . 'GrantOnce', _t(__TRAIT__ . '.GrantOnce', 'Einmal in Höhe von (Euro, optional)'))
                    ->setMaxLength(12),
                DateField::create($p . 'GrantOnceDate', _t(__TRAIT__ . '.GrantOnceDate', 'Einmalzahlung ausgezahlt am (optional)')),
            )
                ->displayIf($p . 'Grant')->isEqualTo('yes')
                ->end(),
            DropdownField::create($p . 'HomeOffice', _t(__TRAIT__ . '.HomeOffice', 'Wurde die Miet-/Eigentumswohnung bzw. das Eigenheim (auch nur teilweise) für die selbständige Tätigkeit gewerblich genutzt?'))
                ->setSource($noYes)
                ->setEmptyString($select),
            Wrapper::create(
                TextField::create($p . 'HomeArea', _t(__TRAIT__ . '.HomeArea', 'Größe der Wohnung / des Eigenheims (qm)'))
                    ->setMaxLength(8),
                TextField::create($p . 'OfficeRooms', _t(__TRAIT__ . '.OfficeRooms', 'Anzahl der gewerblich genutzten Räume'))
                    ->setMaxLength(4),
                TextField::create($p . 'OfficeArea', _t(__TRAIT__ . '.OfficeArea', 'Gewerblich genutzte Fläche insgesamt (qm)'))
                    ->setMaxLength(8),
            )
                ->displayIf($p . 'HomeOffice')->isEqualTo('yes')
                ->end(),
            DropdownField::create($p . 'TaxPrepayment', _t(__TRAIT__ . '.TaxPrepayment', 'Haben Sie im Bemessungszeitraum Einkommensteuervorauszahlungen geleistet? (keine Betriebsausgaben)'))
                ->setSource($noYes)
                ->setEmptyString($select),
            Wrapper::create(
                DateField::create($p . 'Prepayment1Date', _t(__TRAIT__ . '.Prepayment1Date', 'Vorauszahlung 1 am')),
                TextField::create($p . 'Prepayment1Amount', _t(__TRAIT__ . '.Prepayment1Amount', 'Vorauszahlung 1 in Höhe von (Euro)'))
                    ->setMaxLength(12),
                DateField::create($p . 'Prepayment2Date', _t(__TRAIT__ . '.Prepayment2Date', 'Vorauszahlung 2 am (optional)')),
                TextField::create($p . 'Prepayment2Amount', _t(__TRAIT__ . '.Prepayment2Amount', 'Vorauszahlung 2 in Höhe von (Euro, optional)'))
                    ->setMaxLength(12),
            )
                ->displayIf($p . 'TaxPrepayment')->isEqualTo('yes')
                ->end(),
            DropdownField::create($p . 'SmallBusiness', _t(__TRAIT__ . '.SmallBusiness', 'Kleinunternehmer nach § 19 UStG?'))
                ->setSource($noYes)
                ->setEmptyString($select),
        );
    }

    public function validateStep($data, $form)
    {
        $p = static::FIELD_PREFIX;
        $required = $this->validateConditionalRequired($data, $form);
        $amounts = $this->validateAmountFields($form, [
            $p . 'GrantMonthly',
            $p . 'GrantOnce',
            $p . 'Prepayment1Amount',
            $p . 'Prepayment2Amount',
        ]);

        return $required && $amounts && parent::validateStep($data, $form);
    }

    public function getValidator(): RequiredFieldsValidator
    {
        $p = static::FIELD_PREFIX;

        return RequiredFieldsValidator::create([
            $p . 'Activity',
            $p . 'ActivityStart',
            $p . 'LegalForm',
            $p . 'BusinessAddress',
            $p . 'Grant',
            $p . 'HomeOffice',
            $p . 'TaxPrepayment',
            $p . 'SmallBusiness',
        ]);
    }
}
