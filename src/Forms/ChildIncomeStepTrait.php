<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use UncleCheese\DisplayLogic\Forms\Wrapper;

/**
 * Child N, part 2: KiZ 1-AnK Punkt 3/4 (Einnahmen/Ausgaben of the last six months), the maintenance situation
 * and KiZ 5c (Anlage zu Unterhalt und Unterhaltsvorschuss) when the child gets less than the minimum or nothing.
 * Field names "Child{N}<SubmissionChild field>", labels via __TRAIT__ keys.
 *
 * Using classes define: CHILD, FIELD_PREFIX, RULE_MODEL, $default_sort_order, $conditional_required.
 */
trait ChildIncomeStepTrait
{
    public function getFields(): FieldList
    {
        $p = static::FIELD_PREFIX;
        $select = '-- ' . _t(__TRAIT__ . '.DEFAULT_SELECT', 'Bitte auswählen') . ' --';

        return FieldList::create(
            HeaderField::create('IntroText', _t(__TRAIT__ . '.IntroText', 'Kind {number}: Einnahmen, Ausgaben und Unterhalt', ['number' => static::CHILD]), 4),
            LiteralField::create($p . 'IncomeNotice', '<div class="alert alert-info">' . _t(__TRAIT__ . '.IncomeNotice', 'Einnahmen und Ausgaben des Kindes in den letzten sechs Monaten vor dem Antragsmonat. Bitte fügen Sie die jeweiligen Nachweise in Kopie bei.') . '</div>'),
            HeaderField::create($p . 'IncomeHeader', _t(__TRAIT__ . '.IncomeHeader', 'Einnahmen des Kindes'), 5),
            CheckboxField::create($p . 'Wage', _t(__TRAIT__ . '.Wage', 'Ausbildungsvergütung / Arbeitslohn / Gehalt (auch Mini- oder Nebenjob)')),
            Wrapper::create(
                DropdownField::create($p . 'WageEvidence', _t(__TRAIT__ . '.WageEvidence', 'Als Nachweis füge ich bei'))
                    ->setSource([
                        'payslips' => _t(__TRAIT__ . '.WageEvidence.payslips', 'Lohn-/Gehaltsabrechnungen'),
                        'certificate' => _t(__TRAIT__ . '.WageEvidence.certificate', 'Vordruck „Verdienstbescheinigung des Arbeitgebers“ (KiZ 5, wird vorausgefüllt beigefügt)'),
                    ])
                    ->setEmptyString($select),
                Wrapper::create(
                    DropdownField::create($p . 'CertTraining', _t(__TRAIT__ . '.CertTraining', 'Befindet sich das Kind in Ausbildung? (Angabe für die Verdienstbescheinigung)'))
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
                TextField::create($p . 'HolidayJobPeriod', _t(__TRAIT__ . '.HolidayJobPeriod', 'Nur bei Schülerinnen/Schülern mit Ferienjob: Zeitraum des Ferienjobs (optional)'))
                    ->setMaxLength(30),
            )
                ->displayIf($p . 'Wage')->isChecked()
                ->end(),
            CheckboxField::create($p . 'SelfEmployed', _t(__TRAIT__ . '.SelfEmployed', 'Einkommen aus selbständiger Tätigkeit')),
            Wrapper::create(
                LiteralField::create($p . 'SelfEmployedNotice', '<div class="alert alert-warning">' . _t(__TRAIT__ . '.SelfEmployedNotice', 'Bitte füllen Sie für das Kind zusätzlich die „Anlage zum Einkommen aus selbständiger Tätigkeit“ (KiZ 5a) aus und legen Sie sie dem Antrag bei.') . '</div>'),
            )
                ->displayIf($p . 'SelfEmployed')->isChecked()
                ->end(),
            CheckboxField::create($p . 'VoluntaryService', _t(__TRAIT__ . '.VoluntaryService', 'Einkommen aus Jugend-/Bundesfreiwilligendienst oder anderer gemeinnütziger/ehrenamtlicher Tätigkeit')),
            LiteralField::create($p . 'BenefitsNotice', '<p>' . _t(__TRAIT__ . '.BenefitsNotice', 'Anderes Einkommen – Bescheid über:') . '</p>'),
            CheckboxField::create($p . 'BasicIncome', _t(__TRAIT__ . '.BasicIncome', 'Grundsicherungsgeld / Sozialhilfe / Leistungen für Asylbewerber')),
            CheckboxField::create($p . 'Unemployment', _t(__TRAIT__ . '.Unemployment', 'Arbeitslosengeld')),
            CheckboxField::create($p . 'SickPay', _t(__TRAIT__ . '.SickPay', 'Krankengeld / Verletztengeld / Übergangsgeld')),
            CheckboxField::create($p . 'Pension', _t(__TRAIT__ . '.Pension', 'Rente / Halbwaisenrente')),
            CheckboxField::create($p . 'Bafoeg', _t(__TRAIT__ . '.Bafoeg', 'BAföG / Stipendium / Berufsausbildungsbeihilfe')),
            CheckboxField::create($p . 'OtherBenefits', _t(__TRAIT__ . '.OtherBenefits', 'sonstige staatliche Leistungen')),
            CheckboxField::create($p . 'OtherIncome', _t(__TRAIT__ . '.OtherIncome', 'Sonstige Einnahmen (z. B. Zinserträge, Steuerrückerstattungen, Abfindungen, Trinkgelder)')),
            HeaderField::create($p . 'MaintenanceHeader', _t(__TRAIT__ . '.MaintenanceHeader', 'Unterhalt / Unterhaltsvorschuss'), 5),
            DropdownField::create($p . 'Maintenance', _t(__TRAIT__ . '.Maintenance', 'Unterhalt für dieses Kind in den letzten sechs Monaten'))
                ->setSource([
                    'not_applicable' => _t(__TRAIT__ . '.Maintenance.not_applicable', 'Nicht zutreffend: der andere Elternteil lebt mit uns im Haushalt (oder ist verstorben)'),
                    'full' => _t(__TRAIT__ . '.Maintenance.full', 'Ich erhalte Unterhalt mindestens in Höhe von 227 € (0-5 Jahre) / 299 € (6-11 Jahre) / 394 € (12-17 Jahre) bzw. das Kind ist volljährig und erhält Unterhalt'),
                    'uvg' => _t(__TRAIT__ . '.Maintenance.uvg', 'Ich erhalte Unterhaltsvorschuss (ggf. ergänzend zu Unterhalt)'),
                    'partial' => _t(__TRAIT__ . '.Maintenance.partial', 'Ich erhalte weniger Unterhalt als diese Beträge und keinen Unterhaltsvorschuss'),
                    'none' => _t(__TRAIT__ . '.Maintenance.none', 'Ich erhalte weder Unterhalt noch Unterhaltsvorschuss'),
                ])
                ->setEmptyString($select)
                ->setDescription(_t(__TRAIT__ . '.MaintenanceDescription', 'Bei „weniger“ oder „keinem“ Unterhalt wird die „Anlage zu Unterhalt und Unterhaltsvorschuss“ (KiZ 5c) beigefügt. Bitte weisen Sie erhaltene Zahlungen (Kontoauszüge) bzw. den Bewilligungsbescheid nach.')),
            Wrapper::create(
                LiteralField::create($p . 'MNoneNotice', '<p><strong>' . _t(__TRAIT__ . '.MNoneNotice', 'Ich habe in den letzten sechs Monaten vor dem Antragsmonat keinen Unterhalt vom anderen Elternteil erhalten, weil:') . '</strong></p>'),
                CheckboxField::create($p . 'MNoneNotCapable', _t(__TRAIT__ . '.MNoneNotCapable', 'er/sie nicht leistungsfähig ist')),
                CheckboxField::create($p . 'MNoneAddressUnknown', _t(__TRAIT__ . '.MNoneAddressUnknown', 'mir die aktuelle Adresse nicht bekannt ist')),
                Wrapper::create(
                    TextareaField::create($p . 'MNoneAddressSteps', _t(__TRAIT__ . '.MNoneAddressSteps', 'Zur Ermittlung der Adresse habe ich Folgendes unternommen'))
                        ->setRows(2),
                )
                    ->displayIf($p . 'MNoneAddressUnknown')->isChecked()
                    ->end(),
                CheckboxField::create($p . 'MNonePaternity', _t(__TRAIT__ . '.MNonePaternity', 'die Vaterschaft noch nicht anerkannt bzw. festgestellt ist')),
                Wrapper::create(
                    TextareaField::create($p . 'MNonePaternitySteps', _t(__TRAIT__ . '.MNonePaternitySteps', 'Zur Vaterschaftsanerkennung bzw. -feststellung habe ich Folgendes unternommen'))
                        ->setRows(2),
                )
                    ->displayIf($p . 'MNonePaternity')->isChecked()
                    ->end(),
                CheckboxField::create($p . 'MNoneEqualCare', _t(__TRAIT__ . '.MNoneEqualCare', 'sich das Kind zu gleichen oder annähernd gleichen Betreuungsteilen (13-17 Tage) jeweils bei mir und beim anderen Elternteil aufhält und eine private Vereinbarung vorliegt, dass kein Barunterhalt gezahlt wird (bitte Vereinbarung vorlegen)')),
                CheckboxField::create($p . 'MNoneSoloMother', _t(__TRAIT__ . '.MNoneSoloMother', 'es sich um eine Solomutterschaft aufgrund registrierter Samenspende oder Alleinadoption handelt')),
                CheckboxField::create($p . 'MNoneOther', _t(__TRAIT__ . '.MNoneOther', 'sonstige Gründe vorliegen (z. B. mangelnde Bereitschaft)')),
                Wrapper::create(
                    TextareaField::create($p . 'MNoneOtherText', _t(__TRAIT__ . '.MNoneOtherText', 'Bitte erläutern'))
                        ->setRows(2),
                )
                    ->displayIf($p . 'MNoneOther')->isChecked()
                    ->end(),
            )
                ->displayIf($p . 'Maintenance')->isEqualTo('none')
                ->end(),
            Wrapper::create(
                LiteralField::create($p . 'MPartialNotice', '<p><strong>' . _t(__TRAIT__ . '.MPartialNotice', 'Ich habe in den letzten sechs Monaten vor dem Antragsmonat weniger Unterhalt erhalten als den Mindestbetrag, weil:') . '</strong></p>'),
                CheckboxField::create($p . 'MPartialNotCapable', _t(__TRAIT__ . '.MPartialNotCapable', 'er/sie nicht mehr leisten konnte')),
                CheckboxField::create($p . 'MPartialOther', _t(__TRAIT__ . '.MPartialOther', 'sonstige Gründe vorliegen (z. B. mangelnde Bereitschaft)')),
                Wrapper::create(
                    TextareaField::create($p . 'MPartialOtherText', _t(__TRAIT__ . '.MPartialOtherText', 'Bitte erläutern'))
                        ->setRows(2),
                )
                    ->displayIf($p . 'MPartialOther')->isChecked()
                    ->end(),
            )
                ->displayIf($p . 'Maintenance')->isEqualTo('partial')
                ->end(),
            Wrapper::create(
                LiteralField::create($p . 'MActionNotice', '<p><strong>' . _t(__TRAIT__ . '.MActionNotice', 'Um (mehr) Unterhalt zu erhalten, habe ich Folgendes beantragt (bitte nachweisen):') . '</strong></p>'),
                CheckboxField::create($p . 'MLawyer', _t(__TRAIT__ . '.MLawyer', 'Unterhalt über Rechtsanwalt/Rechtsanwältin')),
                Wrapper::create(
                    DateField::create($p . 'MLawyerDate', _t(__TRAIT__ . '.MLawyerDate', 'am')),
                )
                    ->displayIf($p . 'MLawyer')->isChecked()
                    ->end(),
                CheckboxField::create($p . 'MYouthOffice', _t(__TRAIT__ . '.MYouthOffice', 'eine Beistandschaft beim Jugendamt')),
                Wrapper::create(
                    TextField::create($p . 'MYouthOfficePlace', _t(__TRAIT__ . '.MYouthOfficePlace', 'Jugendamt in'))
                        ->setMaxLength(35),
                    DateField::create($p . 'MYouthOfficeDate', _t(__TRAIT__ . '.MYouthOfficeDate', 'am')),
                )
                    ->displayIf($p . 'MYouthOffice')->isChecked()
                    ->end(),
                CheckboxField::create($p . 'MCourt', _t(__TRAIT__ . '.MCourt', 'Antrag auf Unterhaltsfestsetzung beim Familiengericht')),
                Wrapper::create(
                    DateField::create($p . 'MCourtDate', _t(__TRAIT__ . '.MCourtDate', 'am')),
                )
                    ->displayIf($p . 'MCourt')->isChecked()
                    ->end(),
                CheckboxField::create($p . 'MEnforcement', _t(__TRAIT__ . '.MEnforcement', 'Betreibung der Zwangsvollstreckung aus einem vollstreckbaren Unterhaltstitel')),
                Wrapper::create(
                    DateField::create($p . 'MEnforcementDate', _t(__TRAIT__ . '.MEnforcementDate', 'am')),
                )
                    ->displayIf($p . 'MEnforcement')->isChecked()
                    ->end(),
                CheckboxField::create($p . 'MOtherAction', _t(__TRAIT__ . '.MOtherAction', 'Sonstiges')),
                Wrapper::create(
                    TextareaField::create($p . 'MOtherActionText', _t(__TRAIT__ . '.MOtherActionText', 'Bitte erläutern'))
                        ->setRows(2),
                )
                    ->displayIf($p . 'MOtherAction')->isChecked()
                    ->end(),
                DropdownField::create($p . 'MUvgReason', _t(__TRAIT__ . '.MUvgReason', 'Ich habe in den letzten sechs Monaten keinen Unterhaltsvorschuss erhalten, weil'))
                    ->setSource([
                        'rejected' => _t(__TRAIT__ . '.MUvgReason.rejected', 'mein Antrag abgelehnt wurde (bitte Ablehnungsbescheid vorlegen)'),
                        'pending' => _t(__TRAIT__ . '.MUvgReason.pending', 'über meinen Antrag noch nicht entschieden wurde'),
                        'not_eligible' => _t(__TRAIT__ . '.MUvgReason.not_eligible', 'ich keinen Antrag gestellt habe, da die Voraussetzungen nicht erfüllt sind'),
                        'other' => _t(__TRAIT__ . '.MUvgReason.other', 'aus sonstigen Gründen'),
                    ])
                    ->setEmptyString($select),
                Wrapper::create(
                    TextareaField::create($p . 'MUvgReasonText', _t(__TRAIT__ . '.MUvgReasonText', 'Bitte begründen (z. B. Kind ist 18 Jahre alt, Sie sind mit Ihrem/Ihrer im Haushalt lebenden Partner(in) verheiratet, Solomutterschaft)'))
                        ->setRows(2),
                )
                    ->displayIf($p . 'MUvgReason')->isEqualTo('not_eligible')
                    ->orIf($p . 'MUvgReason')->isEqualTo('other')
                    ->end(),
            )
                ->displayIf($p . 'Maintenance')->isEqualTo('partial')
                ->orIf($p . 'Maintenance')->isEqualTo('none')
                ->end(),
            HeaderField::create($p . 'ExpensesHeader', _t(__TRAIT__ . '.ExpensesHeader', 'Ausgaben des Kindes (bitte nachweisen)'), 5),
            CheckboxField::create($p . 'PublicTransport', _t(__TRAIT__ . '.PublicTransport', 'Fahrtkosten zur Arbeitsstätte: Fahrkarten für öffentliche Verkehrsmittel')),
            TextField::create($p . 'CarKm', _t(__TRAIT__ . '.CarKm', 'Bei Kfz-Nutzung: einfache Wegstrecke zur Arbeitsstätte in km (optional)'))
                ->setMaxLength(6),
            TextField::create($p . 'CarDays', _t(__TRAIT__ . '.CarDays', 'Bei Kfz-Nutzung: Arbeitstage pro Woche (optional)'))
                ->setMaxLength(3),
            CheckboxField::create($p . 'DoubleHousehold', _t(__TRAIT__ . '.DoubleHousehold', 'Aufwendungen bei doppelter Haushaltsführung')),
            CheckboxField::create($p . 'Meals', _t(__TRAIT__ . '.Meals', 'Verpflegungsmehraufwendungen')),
            CheckboxField::create($p . 'OtherWorkExpenses', _t(__TRAIT__ . '.OtherWorkExpenses', 'Sonstige Werbungskosten (z. B. Gewerkschaftsbeitrag)')),
            CheckboxField::create($p . 'CarInsurance', _t(__TRAIT__ . '.CarInsurance', 'Kfz-Haftpflichtversicherung (ohne Voll-/Teilkasko)')),
            CheckboxField::create($p . 'PensionProvision', _t(__TRAIT__ . '.PensionProvision', 'Altersvorsorgebeiträge (z. B. Riester-Rente)')),
            CheckboxField::create($p . 'HealthInsurance', _t(__TRAIT__ . '.HealthInsurance', 'Beiträge zur Kranken-/Pflegeversicherung; Altersvorsorge, wenn nicht gesetzlich pflichtversichert')),
            CheckboxField::create($p . 'MaintenancePayments', _t(__TRAIT__ . '.MaintenancePayments', 'Unterhaltszahlungen (Unterhaltstitel und Kontoauszüge)')),
        );
    }

    public function getValidator(): RequiredFieldsValidator
    {
        return RequiredFieldsValidator::create([static::FIELD_PREFIX . 'Maintenance']);
    }
}
