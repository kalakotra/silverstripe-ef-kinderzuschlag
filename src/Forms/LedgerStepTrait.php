<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use Kalakotra\EFormKinderzuschlag\Model\SubmissionSelfEmployed;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;

/**
 * KiZ 5a Beiblatt: income and expenses per month of the Bemessungszeitraum (six months before the application month).
 * One row = six amounts "<FIELD_PREFIX><Row>M1..M6" + remark "<FIELD_PREFIX><Row>Note". Row 5a (Kfz total), the
 * "Summe" column and the SUMME rows are computed by SubmissionSelfEmployed and never asked.
 *
 * Using classes define: FIELD_PREFIX, RULE_MODEL, $default_sort_order, introText().
 */
trait LedgerStepTrait
{
    abstract protected function introText(): string;

    public function getFields(): FieldList
    {
        $p = static::FIELD_PREFIX;
        $months = SubmissionSelfEmployed::periodMonths(new \DateTimeImmutable());

        $fields = FieldList::create(
            HeaderField::create('IntroText', $this->introText(), 4),
            LiteralField::create($p . 'LedgerNotice', '<div class="alert alert-info">' . _t(__TRAIT__ . '.LedgerNotice', 'Bitte tragen Sie die tatsächlich zugeflossenen Einnahmen und tatsächlich geleisteten Ausgaben je Monat in Euro ein (z. B. 1.250,00). Leere Felder zählen als 0. Summen werden automatisch berechnet. Nicht als Betriebsausgaben gelten u. a. Steuern auf das Einkommen, Pflichtbeiträge zur Sozialversicherung, private Versicherungen und Fahrten zwischen Wohnung und Arbeitsstätte.') . '</div>'),
            LiteralField::create($p . 'LedgerStyle', '<style>.kiz-ledger-row{display:flex;flex-wrap:wrap;gap:.5rem;align-items:flex-end;margin-bottom:.75rem}.kiz-ledger-row .field{flex:1 1 7rem;margin:0}.kiz-ledger-row .field.kiz-note{flex-basis:14rem}</style>'),
            HeaderField::create($p . 'IncomeHeader', _t(__TRAIT__ . '.IncomeHeader', 'Einnahmen'), 5),
        );

        foreach (SubmissionSelfEmployed::LEDGER_ROWS as $row) {
            if ($row === 'Exp1') {
                $fields->push(HeaderField::create($p . 'ExpensesHeader', _t(__TRAIT__ . '.ExpensesHeader', 'Ausgaben'), 5));
            }

            $label = $this->rowLabel($row);
            $inputs = [LiteralField::create($p . $row . 'Label', '<p class="kiz-ledger-label"><strong>' . htmlspecialchars($label, ENT_QUOTES) . '</strong></p>')];
            foreach ($months as $index => $month) {
                $inputs[] = TextField::create($p . $row . 'M' . ($index + 1), $month)
                    ->setMaxLength(14);
            }
            $inputs[] = TextField::create($p . $row . 'Note', _t(__TRAIT__ . '.Note', 'Bemerkungen (optional)'))
                ->setMaxLength(40)
                ->addExtraClass('kiz-note');

            $fields->push(CompositeField::create(...$inputs)->addExtraClass('kiz-ledger-row'));
        }

        return $fields;
    }

    public function validateStep($data, $form)
    {
        $fieldNames = [];
        foreach (SubmissionSelfEmployed::LEDGER_ROWS as $row) {
            for ($m = 1; $m <= 6; $m++) {
                $fieldNames[] = static::FIELD_PREFIX . $row . 'M' . $m;
            }
        }

        return $this->validateAmountFields($form, $fieldNames) && parent::validateStep($data, $form);
    }

    /** Row label as printed on the Beiblatt (also used for the preview labels). */
    public function rowLabel(string $row): string
    {
        return match ($row) {
            'Inc1' => _t(__TRAIT__ . '.Inc1', '1. Betriebseinnahmen'),
            'Inc2' => _t(__TRAIT__ . '.Inc2', '2. Privatentnahmen von Waren'),
            'Inc3' => _t(__TRAIT__ . '.Inc3', '3. Sonstige betriebliche Einnahmen'),
            'Inc4' => _t(__TRAIT__ . '.Inc4', '4. Zuwendung von Dritten'),
            'Inc5' => _t(__TRAIT__ . '.Inc5', '5. Vereinnahmte/zu vereinnahmende Umsatzsteuer'),
            'Inc6' => _t(__TRAIT__ . '.Inc6', '6. Umsatzsteuer auf Privatentnahme von Waren'),
            'Inc7' => _t(__TRAIT__ . '.Inc7', '7. Vom Finanzamt erstattete/zu erstattende Umsatzsteuer'),
            'Exp1' => _t(__TRAIT__ . '.Exp1', '1. Wareneinkauf'),
            'Exp2' => _t(__TRAIT__ . '.Exp2', '2. Personalkosten (einschl. SV-Beiträge)'),
            'Exp3' => _t(__TRAIT__ . '.Exp3', '3. Raumkosten (einschl. Neben- und Energiekosten)'),
            'Exp4' => _t(__TRAIT__ . '.Exp4', '4. Betriebliche Versicherungen/Beiträge'),
            'CarTax' => _t(__TRAIT__ . '.CarTax', '5a. Kfz-Kosten betriebliches Kfz: Steuern'),
            'CarInsurance' => _t(__TRAIT__ . '.CarInsurance', '5a. Kfz-Kosten betriebliches Kfz: Versicherung'),
            'CarRunning' => _t(__TRAIT__ . '.CarRunning', '5a. Kfz-Kosten betriebliches Kfz: laufende Betriebskosten'),
            'CarRepairs' => _t(__TRAIT__ . '.CarRepairs', '5a. Kfz-Kosten betriebliches Kfz: Reparaturen'),
            'CarPrivateKm' => _t(__TRAIT__ . '.CarPrivateKm', '5a. Abzüglich private Fahrten mit dem betrieblichen Kfz (0,10 € je gefahrenem km, als positiver Betrag)'),
            'Exp5b' => _t(__TRAIT__ . '.Exp5b', '5b. Privates Kfz – betriebliche Fahrten gemäß Fahrtenbuch (0,10 € je gefahrenem km)'),
            'Exp6' => _t(__TRAIT__ . '.Exp6', '6. Werbungskosten, Repräsentation etc.'),
            'Exp7' => _t(__TRAIT__ . '.Exp7', '7. Reisekosten'),
            'Exp8' => _t(__TRAIT__ . '.Exp8', '8. Büromaterial'),
            'Exp9' => _t(__TRAIT__ . '.Exp9', '9. Telefon/Internet (abzüglich privater Nutzung)'),
            'Exp10' => _t(__TRAIT__ . '.Exp10', '10. Beratungskosten (z. B. Steuerberater/Anwalt)'),
            'Exp11' => _t(__TRAIT__ . '.Exp11', '11. Fortbildungskosten'),
            'Exp12' => _t(__TRAIT__ . '.Exp12', '12. Sonstige Betriebsausgaben'),
            'Exp13' => _t(__TRAIT__ . '.Exp13', '13. Investitionen aus Zuwendungen Dritter'),
            'Exp14' => _t(__TRAIT__ . '.Exp14', '14. Schuldzinsen (Anlagevermögen)'),
            'Exp15' => _t(__TRAIT__ . '.Exp15', '15. Tilgung bestehender Darlehen'),
            'Exp16' => _t(__TRAIT__ . '.Exp16', '16. Abziehbare Vorsteuer'),
            'Exp17' => _t(__TRAIT__ . '.Exp17', '17. An das Finanzamt gezahlte/zu zahlende Umsatzsteuer'),
            default => $row,
        };
    }
}
