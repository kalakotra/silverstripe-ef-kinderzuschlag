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
use UncleCheese\DisplayLogic\Forms\Wrapper;

/**
 * KiZ 1-AnA Punkt 5 (Einnahmen) and Punkt 6 (Ausgaben) for one column ("für mich" / "Partner(in)").
 * Field names "<PERSON_PREFIX><Field>" (Applicant / Partner), stored on the main Submission; labels via __TRAIT__ keys.
 *
 * Using classes define: PERSON_PREFIX, $default_sort_order, $conditional_required, introText().
 */
trait IncomeStepTrait
{
    abstract protected function introText(): string;

    public function getFields(): FieldList
    {
        $p = static::PERSON_PREFIX;
        $select = '-- ' . _t(__TRAIT__ . '.DEFAULT_SELECT', 'Bitte auswählen') . ' --';

        return FieldList::create(
            HeaderField::create('IntroText', $this->introText(), 4),
            LiteralField::create($p . 'IncomeNotice', '<div class="alert alert-info">' . _t(__TRAIT__ . '.IncomeNotice', 'Einnahmen und Ausgaben in den letzten sechs Monaten vor dem Antragsmonat (Bemessungszeitraum). Kreuzen Sie an, was zutrifft – die Nachweise fügen Sie in Kopie bei.') . '</div>'),
            HeaderField::create($p . 'IncomeHeader', _t(__TRAIT__ . '.IncomeHeader', 'Einnahmen'), 5),
            CheckboxField::create($p . 'Wage', _t(__TRAIT__ . '.Wage', 'Arbeitslohn / Gehalt (ggf. Ausbildungsvergütung, Zuschuss des Arbeitgebers zum Mutterschaftsgeld, auch Mini-/Nebenjob)')),
            Wrapper::create(
                DropdownField::create($p . 'WageEvidence', _t(__TRAIT__ . '.WageEvidence', 'Als Nachweis füge ich bei'))
                    ->setSource([
                        'payslips' => _t(__TRAIT__ . '.WageEvidence.payslips', 'Lohn-/Gehaltsabrechnungen'),
                        'certificate' => _t(__TRAIT__ . '.WageEvidence.certificate', 'Vordruck „Verdienstbescheinigung des Arbeitgebers“ (KiZ 5, wird vorausgefüllt beigefügt)'),
                    ])
                    ->setEmptyString($select),
                Wrapper::create(
                    DropdownField::create($p . 'CertTraining', _t(__TRAIT__ . '.CertTraining', 'Befinden Sie sich in Ausbildung? (Angabe für die Verdienstbescheinigung)'))
                        ->setSource([
                            'no' => _t(__TRAIT__ . '.CertTraining.no', 'Nein'),
                            'yes' => _t(__TRAIT__ . '.CertTraining.yes', 'Ja'),
                        ])
                        ->setEmptyString($select),
                    Wrapper::create(
                        DateField::create($p . 'CertTrainingUntil', _t(__TRAIT__ . '.CertTrainingUntil', 'Ausbildung bis (optional)')),
                    )
                        ->displayIf($p . 'CertTraining')->isEqualTo('yes')
                        ->end(),
                )
                    ->displayIf($p . 'WageEvidence')->isEqualTo('certificate')
                    ->end(),
            )
                ->displayIf($p . 'Wage')->isChecked()
                ->end(),
            CheckboxField::create($p . 'SelfEmployed', _t(__TRAIT__ . '.SelfEmployed', 'Einkommen aus selbständiger Arbeit (die „Anlage zum Einkommen aus selbständiger Tätigkeit“ KiZ 5a mit Beiblatt wird in den nächsten Schritten ausgefüllt)')),
            Wrapper::create(
                CheckboxField::create($p . 'SelfEmployedOtherEvidence', _t(__TRAIT__ . '.SelfEmployedOtherEvidence', 'Zusätzlich füge ich andere Nachweise über die Betriebseinnahmen und -ausgaben bei (z. B. betriebswirtschaftliche Auswertungen, Einnahme-Überschuss-Berechnungen)')),
            )
                ->displayIf($p . 'SelfEmployed')->isChecked()
                ->end(),
            CheckboxField::create($p . 'VoluntaryService', _t(__TRAIT__ . '.VoluntaryService', 'Einkommen aus Jugend-/Bundesfreiwilligendienst oder anderer gemeinnütziger/ehrenamtlicher Tätigkeit')),
            LiteralField::create($p . 'BenefitsNotice', '<p>' . _t(__TRAIT__ . '.BenefitsNotice', 'Anderes Einkommen – Bescheid über:') . '</p>'),
            CheckboxField::create($p . 'BasicIncome', _t(__TRAIT__ . '.BasicIncome', 'Grundsicherungsgeld / Sozialhilfe / Leistungen für Asylbewerber')),
            CheckboxField::create($p . 'Unemployment', _t(__TRAIT__ . '.Unemployment', 'Arbeitslosengeld')),
            CheckboxField::create($p . 'SickPay', _t(__TRAIT__ . '.SickPay', '(Kinder-)Krankengeld / Verletztengeld / Übergangsgeld')),
            CheckboxField::create($p . 'Pension', _t(__TRAIT__ . '.Pension', 'Rente / Halbwaisenrente')),
            CheckboxField::create($p . 'ParentalAllowance', _t(__TRAIT__ . '.ParentalAllowance', 'Elterngeld')),
            CheckboxField::create($p . 'Bafoeg', _t(__TRAIT__ . '.Bafoeg', 'BAföG / Stipendium / Berufsausbildungsbeihilfe')),
            CheckboxField::create($p . 'OtherBenefits', _t(__TRAIT__ . '.OtherBenefits', 'sonstige staatliche Leistungen')),
            CheckboxField::create($p . 'Maintenance', _t(__TRAIT__ . '.Maintenance', 'Unterhalt (ohne die Unterhaltsleistungen für Ihre Kinder) – Kontoauszüge')),
            CheckboxField::create($p . 'Rental', _t(__TRAIT__ . '.Rental', 'Vermietung / Verpachtung – Miet-/Pachtvertrag und Nachweise für nicht umlagefähige Aufwendungen')),
            CheckboxField::create($p . 'OtherIncome', _t(__TRAIT__ . '.OtherIncome', 'Sonstige Einnahmen (z. B. Zinserträge, Steuerrückerstattungen, Abfindungen, Trinkgelder)')),
            HeaderField::create($p . 'ExpensesHeader', _t(__TRAIT__ . '.ExpensesHeader', 'Ausgaben'), 5),
            CheckboxField::create($p . 'PublicTransport', _t(__TRAIT__ . '.PublicTransport', 'Fahrtkosten zur Arbeitsstätte: Fahrkarten für öffentliche Verkehrsmittel')),
            TextField::create($p . 'CarKm', _t(__TRAIT__ . '.CarKm', 'Bei Kfz-Nutzung: einfache Wegstrecke (Hinfahrt) zur Arbeitsstätte in km (optional)'))
                ->setMaxLength(6),
            TextField::create($p . 'CarDays', _t(__TRAIT__ . '.CarDays', 'Bei Kfz-Nutzung: Arbeitstage pro Woche (optional)'))
                ->setMaxLength(3),
            CheckboxField::create($p . 'DoubleHousehold', _t(__TRAIT__ . '.DoubleHousehold', 'Aufwendungen bei doppelter Haushaltsführung')),
            CheckboxField::create($p . 'Meals', _t(__TRAIT__ . '.Meals', 'Verpflegungsmehraufwendungen')),
            CheckboxField::create($p . 'OtherWorkExpenses', _t(__TRAIT__ . '.OtherWorkExpenses', 'Sonstige Werbungskosten (z. B. Gewerkschaftsbeitrag)')),
            CheckboxField::create($p . 'CarInsurance', _t(__TRAIT__ . '.CarInsurance', 'Kfz-Haftpflichtversicherung (ohne Voll-/Teilkasko)')),
            CheckboxField::create($p . 'Riester', _t(__TRAIT__ . '.Riester', 'Altersvorsorgebeiträge (z. B. Riester-Rente)')),
            CheckboxField::create($p . 'HealthInsurance', _t(__TRAIT__ . '.HealthInsurance', 'Beiträge zur Kranken-/Pflegeversicherung; Altersvorsorge, wenn nicht gesetzlich pflichtversichert')),
            CheckboxField::create($p . 'MaintenancePayments', _t(__TRAIT__ . '.MaintenancePayments', 'Unterhaltszahlungen (Unterhaltstitel und Kontoauszüge)')),
            CheckboxField::create($p . 'Childcare', _t(__TRAIT__ . '.Childcare', 'Kinderbetreuungskosten (Vertrag / Gebührenbescheid)')),
        );
    }
}
