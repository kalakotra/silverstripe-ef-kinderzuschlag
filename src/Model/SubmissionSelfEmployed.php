<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Model;

use app\forms\basemodel\BaseSubmission;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * KiZ 5a "Anlage zum Einkommen aus selbständiger Tätigkeit" (+ Beiblatt) of the applicant or the partner.
 * Built in Form::finish() from the steps Form_SelfEmployed{Applicant|Partner} / Form_Ledger{Applicant|Partner}
 * (field prefix SEApplicant / SEPartner stripped).
 *
 * Beiblatt: the user enters the leaf rows per month; row 5a (Kfz total = tax + insurance + running costs + repairs
 * - private km), the "Summe" column and both SUMME rows are computed here.
 */
class SubmissionSelfEmployed extends BaseSubmission
{
    private static string $table_name = 'Kalakotra_EFormKinderzuschlag_SubmissionSelfEmployed';

    private static string $default_sort = 'ID ASC';

    /** Rows the user fills in, in Beiblatt order (row 5a is computed and sits between Exp4 and CarTax). */
    public const LEDGER_ROWS = [
        'Inc1', 'Inc2', 'Inc3', 'Inc4', 'Inc5', 'Inc6', 'Inc7',
        'Exp1', 'Exp2', 'Exp3', 'Exp4',
        'CarTax', 'CarInsurance', 'CarRunning', 'CarRepairs', 'CarPrivateKm',
        'Exp5b', 'Exp6', 'Exp7', 'Exp8', 'Exp9', 'Exp10', 'Exp11', 'Exp12', 'Exp13', 'Exp14', 'Exp15', 'Exp16', 'Exp17',
    ];

    public const INCOME_ROWS = ['Inc1', 'Inc2', 'Inc3', 'Inc4', 'Inc5', 'Inc6', 'Inc7'];

    /** Components of row 5a; CarPrivateKm is subtracted. */
    public const CAR_ROWS = ['CarTax', 'CarInsurance', 'CarRunning', 'CarRepairs'];

    /** Expense rows that enter SUMME BETRIEBSAUSGABEN directly (5a enters as its computed total). */
    public const EXPENSE_ROWS = [
        'Exp1', 'Exp2', 'Exp3', 'Exp4', 'Exp5b', 'Exp6', 'Exp7', 'Exp8', 'Exp9', 'Exp10', 'Exp11', 'Exp12', 'Exp13',
        'Exp14', 'Exp15', 'Exp16', 'Exp17',
    ];

    public const PERSON_APPLICANT = 'applicant';

    public const PERSON_PARTNER = 'partner';

    /** Step field prefix per person. */
    public const PREFIXES = [
        self::PERSON_APPLICANT => 'SEApplicant',
        self::PERSON_PARTNER => 'SEPartner',
    ];

    private static array $db = [
        'Person' => 'Varchar(10)',

        // Form_SelfEmployed* (KiZ 5a page 1-2)
        'Activity' => 'Varchar(80)',
        'ActivityStart' => 'Date',
        'ActivityEnd' => 'Date',
        'LegalForm' => 'Varchar(80)',
        'BusinessAddress' => 'Varchar(100)',
        'Grant' => 'Varchar(3)',
        'GrantType' => 'Varchar(70)',
        'GrantPayer' => 'Varchar(70)',
        'GrantMonthly' => 'Varchar(12)',
        'GrantFrom' => 'Date',
        'GrantUntil' => 'Date',
        'GrantOnce' => 'Varchar(12)',
        'GrantOnceDate' => 'Date',
        'HomeOffice' => 'Varchar(3)',
        'HomeArea' => 'Varchar(8)',
        'OfficeRooms' => 'Varchar(4)',
        'OfficeArea' => 'Varchar(8)',
        'TaxPrepayment' => 'Varchar(3)',
        'Prepayment1Date' => 'Date',
        'Prepayment1Amount' => 'Varchar(12)',
        'Prepayment2Date' => 'Date',
        'Prepayment2Amount' => 'Varchar(12)',
        'SmallBusiness' => 'Varchar(3)',

        // Form_Ledger* (Beiblatt): amounts per month M1..M6 of the Bemessungszeitraum + remark
        'Inc1M1' => 'Decimal(12,2)', 'Inc1M2' => 'Decimal(12,2)', 'Inc1M3' => 'Decimal(12,2)',
        'Inc1M4' => 'Decimal(12,2)', 'Inc1M5' => 'Decimal(12,2)', 'Inc1M6' => 'Decimal(12,2)',
        'Inc1Note' => 'Varchar(40)',
        'Inc2M1' => 'Decimal(12,2)', 'Inc2M2' => 'Decimal(12,2)', 'Inc2M3' => 'Decimal(12,2)',
        'Inc2M4' => 'Decimal(12,2)', 'Inc2M5' => 'Decimal(12,2)', 'Inc2M6' => 'Decimal(12,2)',
        'Inc2Note' => 'Varchar(40)',
        'Inc3M1' => 'Decimal(12,2)', 'Inc3M2' => 'Decimal(12,2)', 'Inc3M3' => 'Decimal(12,2)',
        'Inc3M4' => 'Decimal(12,2)', 'Inc3M5' => 'Decimal(12,2)', 'Inc3M6' => 'Decimal(12,2)',
        'Inc3Note' => 'Varchar(40)',
        'Inc4M1' => 'Decimal(12,2)', 'Inc4M2' => 'Decimal(12,2)', 'Inc4M3' => 'Decimal(12,2)',
        'Inc4M4' => 'Decimal(12,2)', 'Inc4M5' => 'Decimal(12,2)', 'Inc4M6' => 'Decimal(12,2)',
        'Inc4Note' => 'Varchar(40)',
        'Inc5M1' => 'Decimal(12,2)', 'Inc5M2' => 'Decimal(12,2)', 'Inc5M3' => 'Decimal(12,2)',
        'Inc5M4' => 'Decimal(12,2)', 'Inc5M5' => 'Decimal(12,2)', 'Inc5M6' => 'Decimal(12,2)',
        'Inc5Note' => 'Varchar(40)',
        'Inc6M1' => 'Decimal(12,2)', 'Inc6M2' => 'Decimal(12,2)', 'Inc6M3' => 'Decimal(12,2)',
        'Inc6M4' => 'Decimal(12,2)', 'Inc6M5' => 'Decimal(12,2)', 'Inc6M6' => 'Decimal(12,2)',
        'Inc6Note' => 'Varchar(40)',
        'Inc7M1' => 'Decimal(12,2)', 'Inc7M2' => 'Decimal(12,2)', 'Inc7M3' => 'Decimal(12,2)',
        'Inc7M4' => 'Decimal(12,2)', 'Inc7M5' => 'Decimal(12,2)', 'Inc7M6' => 'Decimal(12,2)',
        'Inc7Note' => 'Varchar(40)',
        'Exp1M1' => 'Decimal(12,2)', 'Exp1M2' => 'Decimal(12,2)', 'Exp1M3' => 'Decimal(12,2)',
        'Exp1M4' => 'Decimal(12,2)', 'Exp1M5' => 'Decimal(12,2)', 'Exp1M6' => 'Decimal(12,2)',
        'Exp1Note' => 'Varchar(40)',
        'Exp2M1' => 'Decimal(12,2)', 'Exp2M2' => 'Decimal(12,2)', 'Exp2M3' => 'Decimal(12,2)',
        'Exp2M4' => 'Decimal(12,2)', 'Exp2M5' => 'Decimal(12,2)', 'Exp2M6' => 'Decimal(12,2)',
        'Exp2Note' => 'Varchar(40)',
        'Exp3M1' => 'Decimal(12,2)', 'Exp3M2' => 'Decimal(12,2)', 'Exp3M3' => 'Decimal(12,2)',
        'Exp3M4' => 'Decimal(12,2)', 'Exp3M5' => 'Decimal(12,2)', 'Exp3M6' => 'Decimal(12,2)',
        'Exp3Note' => 'Varchar(40)',
        'Exp4M1' => 'Decimal(12,2)', 'Exp4M2' => 'Decimal(12,2)', 'Exp4M3' => 'Decimal(12,2)',
        'Exp4M4' => 'Decimal(12,2)', 'Exp4M5' => 'Decimal(12,2)', 'Exp4M6' => 'Decimal(12,2)',
        'Exp4Note' => 'Varchar(40)',
        'CarTaxM1' => 'Decimal(12,2)', 'CarTaxM2' => 'Decimal(12,2)', 'CarTaxM3' => 'Decimal(12,2)',
        'CarTaxM4' => 'Decimal(12,2)', 'CarTaxM5' => 'Decimal(12,2)', 'CarTaxM6' => 'Decimal(12,2)',
        'CarTaxNote' => 'Varchar(40)',
        'CarInsuranceM1' => 'Decimal(12,2)', 'CarInsuranceM2' => 'Decimal(12,2)', 'CarInsuranceM3' => 'Decimal(12,2)',
        'CarInsuranceM4' => 'Decimal(12,2)', 'CarInsuranceM5' => 'Decimal(12,2)', 'CarInsuranceM6' => 'Decimal(12,2)',
        'CarInsuranceNote' => 'Varchar(40)',
        'CarRunningM1' => 'Decimal(12,2)', 'CarRunningM2' => 'Decimal(12,2)', 'CarRunningM3' => 'Decimal(12,2)',
        'CarRunningM4' => 'Decimal(12,2)', 'CarRunningM5' => 'Decimal(12,2)', 'CarRunningM6' => 'Decimal(12,2)',
        'CarRunningNote' => 'Varchar(40)',
        'CarRepairsM1' => 'Decimal(12,2)', 'CarRepairsM2' => 'Decimal(12,2)', 'CarRepairsM3' => 'Decimal(12,2)',
        'CarRepairsM4' => 'Decimal(12,2)', 'CarRepairsM5' => 'Decimal(12,2)', 'CarRepairsM6' => 'Decimal(12,2)',
        'CarRepairsNote' => 'Varchar(40)',
        'CarPrivateKmM1' => 'Decimal(12,2)', 'CarPrivateKmM2' => 'Decimal(12,2)', 'CarPrivateKmM3' => 'Decimal(12,2)',
        'CarPrivateKmM4' => 'Decimal(12,2)', 'CarPrivateKmM5' => 'Decimal(12,2)', 'CarPrivateKmM6' => 'Decimal(12,2)',
        'CarPrivateKmNote' => 'Varchar(40)',
        'Exp5bM1' => 'Decimal(12,2)', 'Exp5bM2' => 'Decimal(12,2)', 'Exp5bM3' => 'Decimal(12,2)',
        'Exp5bM4' => 'Decimal(12,2)', 'Exp5bM5' => 'Decimal(12,2)', 'Exp5bM6' => 'Decimal(12,2)',
        'Exp5bNote' => 'Varchar(40)',
        'Exp6M1' => 'Decimal(12,2)', 'Exp6M2' => 'Decimal(12,2)', 'Exp6M3' => 'Decimal(12,2)',
        'Exp6M4' => 'Decimal(12,2)', 'Exp6M5' => 'Decimal(12,2)', 'Exp6M6' => 'Decimal(12,2)',
        'Exp6Note' => 'Varchar(40)',
        'Exp7M1' => 'Decimal(12,2)', 'Exp7M2' => 'Decimal(12,2)', 'Exp7M3' => 'Decimal(12,2)',
        'Exp7M4' => 'Decimal(12,2)', 'Exp7M5' => 'Decimal(12,2)', 'Exp7M6' => 'Decimal(12,2)',
        'Exp7Note' => 'Varchar(40)',
        'Exp8M1' => 'Decimal(12,2)', 'Exp8M2' => 'Decimal(12,2)', 'Exp8M3' => 'Decimal(12,2)',
        'Exp8M4' => 'Decimal(12,2)', 'Exp8M5' => 'Decimal(12,2)', 'Exp8M6' => 'Decimal(12,2)',
        'Exp8Note' => 'Varchar(40)',
        'Exp9M1' => 'Decimal(12,2)', 'Exp9M2' => 'Decimal(12,2)', 'Exp9M3' => 'Decimal(12,2)',
        'Exp9M4' => 'Decimal(12,2)', 'Exp9M5' => 'Decimal(12,2)', 'Exp9M6' => 'Decimal(12,2)',
        'Exp9Note' => 'Varchar(40)',
        'Exp10M1' => 'Decimal(12,2)', 'Exp10M2' => 'Decimal(12,2)', 'Exp10M3' => 'Decimal(12,2)',
        'Exp10M4' => 'Decimal(12,2)', 'Exp10M5' => 'Decimal(12,2)', 'Exp10M6' => 'Decimal(12,2)',
        'Exp10Note' => 'Varchar(40)',
        'Exp11M1' => 'Decimal(12,2)', 'Exp11M2' => 'Decimal(12,2)', 'Exp11M3' => 'Decimal(12,2)',
        'Exp11M4' => 'Decimal(12,2)', 'Exp11M5' => 'Decimal(12,2)', 'Exp11M6' => 'Decimal(12,2)',
        'Exp11Note' => 'Varchar(40)',
        'Exp12M1' => 'Decimal(12,2)', 'Exp12M2' => 'Decimal(12,2)', 'Exp12M3' => 'Decimal(12,2)',
        'Exp12M4' => 'Decimal(12,2)', 'Exp12M5' => 'Decimal(12,2)', 'Exp12M6' => 'Decimal(12,2)',
        'Exp12Note' => 'Varchar(40)',
        'Exp13M1' => 'Decimal(12,2)', 'Exp13M2' => 'Decimal(12,2)', 'Exp13M3' => 'Decimal(12,2)',
        'Exp13M4' => 'Decimal(12,2)', 'Exp13M5' => 'Decimal(12,2)', 'Exp13M6' => 'Decimal(12,2)',
        'Exp13Note' => 'Varchar(40)',
        'Exp14M1' => 'Decimal(12,2)', 'Exp14M2' => 'Decimal(12,2)', 'Exp14M3' => 'Decimal(12,2)',
        'Exp14M4' => 'Decimal(12,2)', 'Exp14M5' => 'Decimal(12,2)', 'Exp14M6' => 'Decimal(12,2)',
        'Exp14Note' => 'Varchar(40)',
        'Exp15M1' => 'Decimal(12,2)', 'Exp15M2' => 'Decimal(12,2)', 'Exp15M3' => 'Decimal(12,2)',
        'Exp15M4' => 'Decimal(12,2)', 'Exp15M5' => 'Decimal(12,2)', 'Exp15M6' => 'Decimal(12,2)',
        'Exp15Note' => 'Varchar(40)',
        'Exp16M1' => 'Decimal(12,2)', 'Exp16M2' => 'Decimal(12,2)', 'Exp16M3' => 'Decimal(12,2)',
        'Exp16M4' => 'Decimal(12,2)', 'Exp16M5' => 'Decimal(12,2)', 'Exp16M6' => 'Decimal(12,2)',
        'Exp16Note' => 'Varchar(40)',
        'Exp17M1' => 'Decimal(12,2)', 'Exp17M2' => 'Decimal(12,2)', 'Exp17M3' => 'Decimal(12,2)',
        'Exp17M4' => 'Decimal(12,2)', 'Exp17M5' => 'Decimal(12,2)', 'Exp17M6' => 'Decimal(12,2)',
        'Exp17Note' => 'Varchar(40)',
    ];

    private static array $has_one = [
        'Submission' => Submission::class,
    ];

    private static array $field_conditions = [
        [
            ['GrantType', 'GrantPayer', 'GrantMonthly', 'GrantFrom', 'GrantUntil', 'GrantOnce', 'GrantOnceDate'],
            ['Grant' => 'yes'],
        ],
        [['HomeArea', 'OfficeRooms', 'OfficeArea'], ['HomeOffice' => 'yes']],
        [['Prepayment1Date', 'Prepayment1Amount', 'Prepayment2Date', 'Prepayment2Amount'], ['TaxPrepayment' => 'yes']],
    ];

    /**
     * KiZ 5a (Familienkasse 01.24). Fields named "Betriebssitz[0]" / "qm-insgesamt[n]" are mislabelled in the PDF:
     * the mapping follows their position (verified with a rendered test fill).
     */
    private static array $field_mapping = [
        // Page 1
        'topmostSubform[0].Page1[0].Kopfzeile[0].Name_Vorname_KGB[0]' => 'ParentText:Person:HeaderName',
        'topmostSubform[0].Page1[0].Kopfzeile[0].KG-Nr[0]' => 'ParentText:Person:KindergeldNumber',
        'topmostSubform[0].Page1[0].Kopfzeile[0].DateField1[0]' => 'ParentText:Person:PeriodFrom',
        'topmostSubform[0].Page1[0].Kopfzeile[0].DateField2[0]' => 'ParentText:Person:PeriodTo',
        'topmostSubform[0].Page1[0].Punkt-1[0].Name-Selbständiger[0]' => 'PersonText:Person:Name',
        'topmostSubform[0].Page1[0].Punkt-1[0].Gebdatum-Selbständ[0]' => 'PersonText:Person:BirthDate',
        'topmostSubform[0].Page1[0].Punkt-1[0].Tätigkeit[0]' => 'Activity',
        'topmostSubform[0].Page1[0].Punkt-1[0].Tätigkeit-Beginn[0]' => 'DateFormating:ActivityStart:1',
        'topmostSubform[0].Page1[0].Punkt-1[0].Betriebssitz[0]' => 'LegalForm',
        'topmostSubform[0].Page1[0].Punkt-1[0].Betriebssitz[1]' => 'BusinessAddress',
        'topmostSubform[0].Page1[0].Punkt-1[0].Tätigkeit-Ende[0]' => 'DateFormating:ActivityEnd:1',
        'topmostSubform[0].Page1[0].#area[3].Zuschuss-ja[0]' => 'StateValue:Grant:topmostSubform[0].Page1[0].#area[3].Zuschuss-ja[0]',
        'topmostSubform[0].Page1[0].#area[3].Zuschuss-nein[0]' => 'StateValue:Grant:topmostSubform[0].Page1[0].#area[3].Zuschuss-nein[0]',
        'topmostSubform[0].Page1[0].#area[3].Zuwendung-Art[0]' => 'GrantType',
        'topmostSubform[0].Page1[0].#area[3].von-AA[0]' => 'GrantPayer',
        'topmostSubform[0].Page1[0].#area[3].Zuschuss-Euro[0]' => 'Amount:GrantMonthly:x',
        'topmostSubform[0].Page1[0].#area[3].Zuschuss-Euro[1]' => 'Amount:GrantOnce:x',
        'topmostSubform[0].Page1[0].#area[3].qm-insgesamt[0]' => 'DateFormating:GrantOnceDate:1',
        'topmostSubform[0].Page1[0].#area[3].qm-insgesamt[1]' => 'DateFormating:GrantFrom:1',
        'topmostSubform[0].Page1[0].#area[3].qm-insgesamt[2]' => 'DateFormating:GrantUntil:1',
        'topmostSubform[0].Page1[0].#area[4].gewerblich-ja[0]' => 'StateValue:HomeOffice:topmostSubform[0].Page1[0].#area[4].gewerblich-ja[0]',
        'topmostSubform[0].Page1[0].#area[4].gewerblich-nein[0]' => 'StateValue:HomeOffice:topmostSubform[0].Page1[0].#area[4].gewerblich-nein[0]',
        'topmostSubform[0].Page1[0].#area[4].Anzahl-Räume[0]' => 'OfficeRooms',
        'topmostSubform[0].Page1[0].#area[4].qm-insgesamt[3]' => 'OfficeArea',
        'topmostSubform[0].Page1[0].#area[4].qm-insgesamt[4]' => 'HomeArea',

        // Page 2: Erklärung (1 = antragstellende Person, 2 = Person mit Einkünften aus selbständiger Tätigkeit)
        'topmostSubform[0].Page2[0].#area[0].Datum\.Antrag\.1[0]' => 'ParentText:Person:Date',
        'topmostSubform[0].Page2[0].#area[0].Datum\.Antrag\.2[0]' => 'PersonText:Person:OwnSignatureDate',
        'topmostSubform[0].Page2[0].Punkt-5[0].#area[4].Vorauszahl-ja[0]' => 'StateValue:TaxPrepayment:topmostSubform[0].Page2[0].Punkt-5[0].#area[4].Vorauszahl-ja[0]',
        'topmostSubform[0].Page2[0].Punkt-5[0].#area[4].Vorauszahl-nein[0]' => 'StateValue:TaxPrepayment:topmostSubform[0].Page2[0].Punkt-5[0].#area[4].Vorauszahl-nein[0]',
        'topmostSubform[0].Page2[0].Punkt-5[0].#area[5].Vorauszahl-am-1[0]' => 'DateFormating:Prepayment1Date:1',
        'topmostSubform[0].Page2[0].Punkt-5[0].#area[5].Vorauszahl-Euro-1[0]' => 'Amount:Prepayment1Amount:x',
        'topmostSubform[0].Page2[0].Punkt-5[0].#area[6].Vorauszahl-am-2[0]' => 'DateFormating:Prepayment2Date:1',
        'topmostSubform[0].Page2[0].Punkt-5[0].#area[6].Vorauszahl-Euro-2[0]' => 'Amount:Prepayment2Amount:x',

        // Page 3: Beiblatt
        'topmostSubform[0].Page3[0].Name_Vorname_KGB[0]' => 'ParentText:Person:HeaderName',
        'topmostSubform[0].Page3[0].KG-Nr[0]' => 'ParentText:Person:KindergeldNumber',
        'topmostSubform[0].Page3[0].Kleinunternehmer-ja[0]' => 'StateValue:SmallBusiness:topmostSubform[0].Page3[0].Kleinunternehmer-ja[0]',
        'topmostSubform[0].Page3[0].Kleinunternehmer-nein[0]' => 'StateValue:SmallBusiness:topmostSubform[0].Page3[0].Kleinunternehmer-nein[0]',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile1[0].Zelle2[0]' => 'MonthName:Person:1',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile1[0].#field[1]' => 'MonthName:Person:2',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile1[0].Zelle3[0]' => 'MonthName:Person:3',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile1[0].#field[3]' => 'MonthName:Person:4',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile1[0].#field[4]' => 'MonthName:Person:5',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile1[0].#field[5]' => 'MonthName:Person:6',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[0].Zelle2[0]' => 'LedgerAmount:Inc1M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[0].#field[1]' => 'LedgerAmount:Inc1M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[0].Zelle3[0]' => 'LedgerAmount:Inc1M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[0].#field[3]' => 'LedgerAmount:Inc1M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[0].#field[4]' => 'LedgerAmount:Inc1M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[0].#field[5]' => 'LedgerAmount:Inc1M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[0].#field[6]' => 'LedgerSum:Inc1M1:Inc1',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[0].Zelle4[0]' => 'Inc1Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[1].#field[0]' => 'LedgerAmount:Inc2M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[1].#field[1]' => 'LedgerAmount:Inc2M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[1].#field[2]' => 'LedgerAmount:Inc2M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[1].#field[3]' => 'LedgerAmount:Inc2M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[1].#field[4]' => 'LedgerAmount:Inc2M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[1].#field[5]' => 'LedgerAmount:Inc2M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[1].#field[6]' => 'LedgerSum:Inc2M1:Inc2',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[1].#field[7]' => 'Inc2Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[2].#field[0]' => 'LedgerAmount:Inc3M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[2].#field[1]' => 'LedgerAmount:Inc3M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[2].#field[2]' => 'LedgerAmount:Inc3M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[2].#field[3]' => 'LedgerAmount:Inc3M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[2].#field[4]' => 'LedgerAmount:Inc3M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[2].#field[5]' => 'LedgerAmount:Inc3M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[2].#field[6]' => 'LedgerSum:Inc3M1:Inc3',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[2].#field[7]' => 'Inc3Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[3].#field[0]' => 'LedgerAmount:Inc4M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[3].#field[1]' => 'LedgerAmount:Inc4M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[3].#field[2]' => 'LedgerAmount:Inc4M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[3].#field[3]' => 'LedgerAmount:Inc4M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[3].#field[4]' => 'LedgerAmount:Inc4M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[3].#field[5]' => 'LedgerAmount:Inc4M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[3].#field[6]' => 'LedgerSum:Inc4M1:Inc4',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[3].#field[7]' => 'Inc4Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[4].#field[0]' => 'LedgerAmount:Inc5M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[4].#field[1]' => 'LedgerAmount:Inc5M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[4].#field[2]' => 'LedgerAmount:Inc5M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[4].#field[3]' => 'LedgerAmount:Inc5M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[4].#field[4]' => 'LedgerAmount:Inc5M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[4].#field[5]' => 'LedgerAmount:Inc5M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[4].#field[6]' => 'LedgerSum:Inc5M1:Inc5',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[4].#field[7]' => 'Inc5Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[5].#field[0]' => 'LedgerAmount:Inc6M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[5].#field[1]' => 'LedgerAmount:Inc6M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[5].#field[2]' => 'LedgerAmount:Inc6M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[5].#field[3]' => 'LedgerAmount:Inc6M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[5].#field[4]' => 'LedgerAmount:Inc6M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[5].#field[5]' => 'LedgerAmount:Inc6M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[5].#field[6]' => 'LedgerSum:Inc6M1:Inc6',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[5].#field[7]' => 'Inc6Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[6].#field[0]' => 'LedgerAmount:Inc7M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[6].#field[1]' => 'LedgerAmount:Inc7M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[6].#field[2]' => 'LedgerAmount:Inc7M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[6].#field[3]' => 'LedgerAmount:Inc7M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[6].#field[4]' => 'LedgerAmount:Inc7M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[6].#field[5]' => 'LedgerAmount:Inc7M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[6].#field[6]' => 'LedgerSum:Inc7M1:Inc7',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[6].#field[7]' => 'Inc7Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[7].#field[0]' => 'LedgerTotal:Person:IncTotal1',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[7].#field[1]' => 'LedgerTotal:Person:IncTotal2',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[7].#field[2]' => 'LedgerTotal:Person:IncTotal3',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[7].#field[3]' => 'LedgerTotal:Person:IncTotal4',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[7].#field[4]' => 'LedgerTotal:Person:IncTotal5',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[7].#field[5]' => 'LedgerTotal:Person:IncTotal6',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[7].#field[6]' => 'LedgerTotal:Person:IncTotalSum',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[10].#field[0]' => 'LedgerAmount:Exp1M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[10].#field[1]' => 'LedgerAmount:Exp1M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[10].#field[2]' => 'LedgerAmount:Exp1M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[10].#field[3]' => 'LedgerAmount:Exp1M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[10].#field[4]' => 'LedgerAmount:Exp1M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[10].#field[5]' => 'LedgerAmount:Exp1M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[10].#field[6]' => 'LedgerSum:Exp1M1:Exp1',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[10].#field[7]' => 'Exp1Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[11].#field[0]' => 'LedgerAmount:Exp2M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[11].#field[1]' => 'LedgerAmount:Exp2M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[11].#field[2]' => 'LedgerAmount:Exp2M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[11].#field[3]' => 'LedgerAmount:Exp2M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[11].#field[4]' => 'LedgerAmount:Exp2M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[11].#field[5]' => 'LedgerAmount:Exp2M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[11].#field[6]' => 'LedgerSum:Exp2M1:Exp2',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[11].#field[7]' => 'Exp2Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[12].#field[0]' => 'LedgerAmount:Exp3M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[12].#field[1]' => 'LedgerAmount:Exp3M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[12].#field[2]' => 'LedgerAmount:Exp3M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[12].#field[3]' => 'LedgerAmount:Exp3M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[12].#field[4]' => 'LedgerAmount:Exp3M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[12].#field[5]' => 'LedgerAmount:Exp3M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[12].#field[6]' => 'LedgerSum:Exp3M1:Exp3',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[12].#field[7]' => 'Exp3Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[13].#field[0]' => 'LedgerAmount:Exp4M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[13].#field[1]' => 'LedgerAmount:Exp4M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[13].#field[2]' => 'LedgerAmount:Exp4M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[13].#field[3]' => 'LedgerAmount:Exp4M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[13].#field[4]' => 'LedgerAmount:Exp4M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[13].#field[5]' => 'LedgerAmount:Exp4M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[13].#field[6]' => 'LedgerSum:Exp4M1:Exp4',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[13].#field[7]' => 'Exp4Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[14].#field[0]' => 'LedgerCar:Person:1',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[14].#field[1]' => 'LedgerCar:Person:2',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[14].#field[2]' => 'LedgerCar:Person:3',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[14].#field[3]' => 'LedgerCar:Person:4',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[14].#field[4]' => 'LedgerCar:Person:5',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[14].#field[5]' => 'LedgerCar:Person:6',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[14].#field[6]' => 'LedgerCar:Person:Sum',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[15].#field[0]' => 'LedgerAmount:CarTaxM1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[15].#field[1]' => 'LedgerAmount:CarTaxM2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[15].#field[2]' => 'LedgerAmount:CarTaxM3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[15].#field[3]' => 'LedgerAmount:CarTaxM4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[15].#field[4]' => 'LedgerAmount:CarTaxM5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[15].#field[5]' => 'LedgerAmount:CarTaxM6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[15].#field[6]' => 'LedgerSum:CarTaxM1:CarTax',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[15].#field[7]' => 'CarTaxNote',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[16].#field[0]' => 'LedgerAmount:CarInsuranceM1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[16].#field[1]' => 'LedgerAmount:CarInsuranceM2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[16].#field[2]' => 'LedgerAmount:CarInsuranceM3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[16].#field[3]' => 'LedgerAmount:CarInsuranceM4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[16].#field[4]' => 'LedgerAmount:CarInsuranceM5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[16].#field[5]' => 'LedgerAmount:CarInsuranceM6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[16].#field[6]' => 'LedgerSum:CarInsuranceM1:CarInsurance',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[16].#field[7]' => 'CarInsuranceNote',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[17].#field[0]' => 'LedgerAmount:CarRunningM1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[17].#field[1]' => 'LedgerAmount:CarRunningM2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[17].#field[2]' => 'LedgerAmount:CarRunningM3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[17].#field[3]' => 'LedgerAmount:CarRunningM4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[17].#field[4]' => 'LedgerAmount:CarRunningM5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[17].#field[5]' => 'LedgerAmount:CarRunningM6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[17].#field[6]' => 'LedgerSum:CarRunningM1:CarRunning',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[17].#field[7]' => 'CarRunningNote',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[18].#field[0]' => 'LedgerAmount:CarRepairsM1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[18].#field[1]' => 'LedgerAmount:CarRepairsM2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[18].#field[2]' => 'LedgerAmount:CarRepairsM3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[18].#field[3]' => 'LedgerAmount:CarRepairsM4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[18].#field[4]' => 'LedgerAmount:CarRepairsM5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[18].#field[5]' => 'LedgerAmount:CarRepairsM6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[18].#field[6]' => 'LedgerSum:CarRepairsM1:CarRepairs',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[18].#field[7]' => 'CarRepairsNote',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[19].#field[0]' => 'LedgerAmount:CarPrivateKmM1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[19].#field[1]' => 'LedgerAmount:CarPrivateKmM2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[19].#field[2]' => 'LedgerAmount:CarPrivateKmM3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[19].#field[3]' => 'LedgerAmount:CarPrivateKmM4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[19].#field[4]' => 'LedgerAmount:CarPrivateKmM5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[19].#field[5]' => 'LedgerAmount:CarPrivateKmM6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[19].#field[6]' => 'LedgerSum:CarPrivateKmM1:CarPrivateKm',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[19].#field[7]' => 'CarPrivateKmNote',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[20].#field[0]' => 'LedgerAmount:Exp5bM1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[20].#field[1]' => 'LedgerAmount:Exp5bM2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[20].#field[2]' => 'LedgerAmount:Exp5bM3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[20].#field[3]' => 'LedgerAmount:Exp5bM4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[20].#field[4]' => 'LedgerAmount:Exp5bM5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[20].#field[5]' => 'LedgerAmount:Exp5bM6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[20].#field[6]' => 'LedgerSum:Exp5bM1:Exp5b',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[20].#field[7]' => 'Exp5bNote',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[21].#field[0]' => 'LedgerAmount:Exp6M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[21].#field[1]' => 'LedgerAmount:Exp6M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[21].#field[2]' => 'LedgerAmount:Exp6M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[21].#field[3]' => 'LedgerAmount:Exp6M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[21].#field[4]' => 'LedgerAmount:Exp6M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[21].#field[5]' => 'LedgerAmount:Exp6M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[21].#field[6]' => 'LedgerSum:Exp6M1:Exp6',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[21].#field[7]' => 'Exp6Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[22].#field[0]' => 'LedgerAmount:Exp7M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[22].#field[1]' => 'LedgerAmount:Exp7M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[22].#field[2]' => 'LedgerAmount:Exp7M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[22].#field[3]' => 'LedgerAmount:Exp7M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[22].#field[4]' => 'LedgerAmount:Exp7M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[22].#field[5]' => 'LedgerAmount:Exp7M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[22].#field[6]' => 'LedgerSum:Exp7M1:Exp7',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[22].#field[7]' => 'Exp7Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[23].#field[0]' => 'LedgerAmount:Exp8M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[23].#field[1]' => 'LedgerAmount:Exp8M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[23].#field[2]' => 'LedgerAmount:Exp8M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[23].#field[3]' => 'LedgerAmount:Exp8M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[23].#field[4]' => 'LedgerAmount:Exp8M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[23].#field[5]' => 'LedgerAmount:Exp8M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[23].#field[6]' => 'LedgerSum:Exp8M1:Exp8',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[23].#field[7]' => 'Exp8Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[24].#field[0]' => 'LedgerAmount:Exp9M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[24].#field[1]' => 'LedgerAmount:Exp9M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[24].#field[2]' => 'LedgerAmount:Exp9M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[24].#field[3]' => 'LedgerAmount:Exp9M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[24].#field[4]' => 'LedgerAmount:Exp9M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[24].#field[5]' => 'LedgerAmount:Exp9M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[24].#field[6]' => 'LedgerSum:Exp9M1:Exp9',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[24].#field[7]' => 'Exp9Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[25].#field[0]' => 'LedgerAmount:Exp10M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[25].#field[1]' => 'LedgerAmount:Exp10M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[25].#field[2]' => 'LedgerAmount:Exp10M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[25].#field[3]' => 'LedgerAmount:Exp10M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[25].#field[4]' => 'LedgerAmount:Exp10M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[25].#field[5]' => 'LedgerAmount:Exp10M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[25].#field[6]' => 'LedgerSum:Exp10M1:Exp10',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[25].#field[7]' => 'Exp10Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[26].#field[0]' => 'LedgerAmount:Exp11M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[26].#field[1]' => 'LedgerAmount:Exp11M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[26].#field[2]' => 'LedgerAmount:Exp11M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[26].#field[3]' => 'LedgerAmount:Exp11M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[26].#field[4]' => 'LedgerAmount:Exp11M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[26].#field[5]' => 'LedgerAmount:Exp11M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[26].#field[6]' => 'LedgerSum:Exp11M1:Exp11',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[26].#field[7]' => 'Exp11Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[27].#field[0]' => 'LedgerAmount:Exp12M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[27].#field[1]' => 'LedgerAmount:Exp12M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[27].#field[2]' => 'LedgerAmount:Exp12M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[27].#field[3]' => 'LedgerAmount:Exp12M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[27].#field[4]' => 'LedgerAmount:Exp12M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[27].#field[5]' => 'LedgerAmount:Exp12M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[27].#field[6]' => 'LedgerSum:Exp12M1:Exp12',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[27].#field[7]' => 'Exp12Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[28].#field[0]' => 'LedgerAmount:Exp13M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[28].#field[1]' => 'LedgerAmount:Exp13M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[28].#field[2]' => 'LedgerAmount:Exp13M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[28].#field[3]' => 'LedgerAmount:Exp13M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[28].#field[4]' => 'LedgerAmount:Exp13M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[28].#field[5]' => 'LedgerAmount:Exp13M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[28].#field[6]' => 'LedgerSum:Exp13M1:Exp13',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[28].#field[7]' => 'Exp13Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[29].#field[0]' => 'LedgerAmount:Exp14M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[29].#field[1]' => 'LedgerAmount:Exp14M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[29].#field[2]' => 'LedgerAmount:Exp14M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[29].#field[3]' => 'LedgerAmount:Exp14M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[29].#field[4]' => 'LedgerAmount:Exp14M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[29].#field[5]' => 'LedgerAmount:Exp14M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[29].#field[6]' => 'LedgerSum:Exp14M1:Exp14',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[29].#field[7]' => 'Exp14Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[30].#field[0]' => 'LedgerAmount:Exp15M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[30].#field[1]' => 'LedgerAmount:Exp15M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[30].#field[2]' => 'LedgerAmount:Exp15M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[30].#field[3]' => 'LedgerAmount:Exp15M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[30].#field[4]' => 'LedgerAmount:Exp15M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[30].#field[5]' => 'LedgerAmount:Exp15M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[30].#field[6]' => 'LedgerSum:Exp15M1:Exp15',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[30].#field[7]' => 'Exp15Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[31].#field[0]' => 'LedgerAmount:Exp16M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[31].#field[1]' => 'LedgerAmount:Exp16M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[31].#field[2]' => 'LedgerAmount:Exp16M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[31].#field[3]' => 'LedgerAmount:Exp16M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[31].#field[4]' => 'LedgerAmount:Exp16M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[31].#field[5]' => 'LedgerAmount:Exp16M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[31].#field[6]' => 'LedgerSum:Exp16M1:Exp16',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[31].#field[7]' => 'Exp16Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[32].#field[0]' => 'LedgerAmount:Exp17M1:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[32].#field[1]' => 'LedgerAmount:Exp17M2:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[32].#field[2]' => 'LedgerAmount:Exp17M3:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[32].#field[3]' => 'LedgerAmount:Exp17M4:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[32].#field[4]' => 'LedgerAmount:Exp17M5:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[32].#field[5]' => 'LedgerAmount:Exp17M6:x',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[32].#field[6]' => 'LedgerSum:Exp17M1:Exp17',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[32].#field[7]' => 'Exp17Note',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[33].#field[0]' => 'LedgerTotal:Person:ExpTotal1',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[33].#field[1]' => 'LedgerTotal:Person:ExpTotal2',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[33].#field[2]' => 'LedgerTotal:Person:ExpTotal3',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[33].#field[3]' => 'LedgerTotal:Person:ExpTotal4',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[33].#field[4]' => 'LedgerTotal:Person:ExpTotal5',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[33].#field[5]' => 'LedgerTotal:Person:ExpTotal6',
        'topmostSubform[0].Page3[0].Tabelle-Einnahmen-Ausgaben[0].Zeile3[33].#field[6]' => 'LedgerTotal:Person:ExpTotalSum',
        // Not filled on purpose: "Bemerkungen" of row 5a and of both SUMME rows.
    ];

    /**
     * PDF field => [stored value => on-state]. Every checkbox of the Familienkasse PDFs has the on-state "1".
     */
    private static array $state_map = [
        'topmostSubform[0].Page1[0].#area[3].Zuschuss-ja[0]' => ['yes' => '1'],
        'topmostSubform[0].Page1[0].#area[3].Zuschuss-nein[0]' => ['no' => '1'],
        'topmostSubform[0].Page1[0].#area[4].gewerblich-ja[0]' => ['yes' => '1'],
        'topmostSubform[0].Page1[0].#area[4].gewerblich-nein[0]' => ['no' => '1'],
        'topmostSubform[0].Page2[0].Punkt-5[0].#area[4].Vorauszahl-ja[0]' => ['yes' => '1'],
        'topmostSubform[0].Page2[0].Punkt-5[0].#area[4].Vorauszahl-nein[0]' => ['no' => '1'],
        'topmostSubform[0].Page3[0].Kleinunternehmer-ja[0]' => ['yes' => '1'],
        'topmostSubform[0].Page3[0].Kleinunternehmer-nein[0]' => ['no' => '1'],
    ];

    /**
     * Step data (prefix already stripped) -> record. Amounts arrive as entered ("1.250,00") and are normalised.
     *
     * @param array<string, mixed> $data
     */
    public static function fromStepData(string $person, array $data): self
    {
        $record = self::create();
        $db = (array) self::config()->get('db');

        foreach ($data as $field => $value) {
            if (!array_key_exists($field, $db) || $field === 'Person') {
                continue;
            }
            if (str_starts_with((string) $db[$field], 'Decimal')) {
                $value = is_scalar($value) && trim((string) $value) !== '' ? self::parseAmount((string) $value) : null;
            }
            $record->setField($field, $value);
        }

        $record->Person = $person;

        return $record;
    }

    /**
     * "1.250,50", "1250,5", "1,250.50", "1250.50", "-30" -> float; null when not a valid amount.
     */
    public static function parseAmount(string $value): ?float
    {
        $value = str_replace([' ', "\u{00A0}", '€', 'EUR'], '', trim($value));
        if ($value === '') {
            return null;
        }

        if (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d{1,2})?$/', $value) || preg_match('/^-?\d+(,\d{1,2})?$/', $value)) {
            return (float) str_replace(['.', ','], ['', '.'], $value);
        }

        if (preg_match('/^-?\d{1,3}(,\d{3})+(\.\d{1,2})?$/', $value) || preg_match('/^-?\d+\.\d{1,2}$/', $value)) {
            return (float) str_replace(',', '', $value);
        }

        return null;
    }

    public static function formatAmount(?float $value): string
    {
        return $value === null ? '' : number_format($value, 2, ',', '.');
    }

    /**
     * The six months before the application month (Bemessungszeitraum) as "MM/YYYY".
     *
     * @return array<int, string>
     */
    public static function periodMonths(DateTimeInterface $applicationDate): array
    {
        $first = DateTimeImmutable::createFromInterface($applicationDate)->modify('first day of this month')->modify('-6 months');
        $months = [];
        for ($i = 0; $i < 6; $i++) {
            $months[] = $first->modify('+' . $i . ' months')->format('m/Y');
        }

        return $months;
    }

    /**
     * Safety net: values of inactive (hidden) fields are never stored, whatever the steps delivered.
     */
    protected function onBeforeWrite(): void
    {
        parent::onBeforeWrite();
        FieldConditions::cleanRecord($this, (array) $this->config()->get('field_conditions'));
    }

    /**
     * Annex records have no FormSubmission / holder page of their own: the CMS custom field mapping of
     * PDFMultiFormPage belongs to the main document only, so the mapping is resolved without it.
     *
     * @return array<string, mixed>
     */
    public function getArrayDataForPDF(): array
    {
        return MappingResolver::resolve($this, (array) $this->config()->get('field_mapping'));
    }

    // ---------------------------------------------------------------------------------------------
    // Mapping helpers (signature fixed by BaseSubmission::getArrayDataForPDF: method($field, $arg))
    // ---------------------------------------------------------------------------------------------

    public function StateValue(string $fieldName, string $pdfField): string
    {
        return FieldConditions::stateValue($this, $fieldName, (array) ($this->config()->get('state_map')[$pdfField] ?? []));
    }

    /** Value provided by the main submission (header, dates, Bemessungszeitraum). */
    public function ParentText(string $fieldName, string $key): string
    {
        $parent = $this->parentSubmission();

        return $parent ? $parent->textFor($key) : '';
    }

    /** Name / birth date of the self-employed person; own signature date only for the applicant. */
    public function PersonText(string $fieldName, string $key): string
    {
        $parent = $this->parentSubmission();
        if (!$parent) {
            return '';
        }

        if ($key === 'OwnSignatureDate') {
            return $this->Person === self::PERSON_APPLICANT ? $parent->textFor('Date') : '';
        }

        $person = $parent->personData($this->Person === self::PERSON_PARTNER ? 'partner' : 'applicant');

        return (string) ($person[$key === 'Name' ? 'name' : 'birthDate'] ?? '');
    }

    public function Amount(string $fieldName, string $unused = ''): string
    {
        $value = trim((string) $this->getField($fieldName));
        $amount = $value === '' ? null : self::parseAmount($value);

        return $amount === null ? $value : self::formatAmount($amount);
    }

    public function MonthName(string $fieldName, string $month): string
    {
        $parent = $this->parentSubmission();
        $months = self::periodMonths($parent ? $parent->applicationDate() : new DateTimeImmutable());

        return $months[(int) $month - 1] ?? '';
    }

    public function LedgerAmount(string $fieldName, string $unused = ''): string
    {
        return self::formatAmount($this->cell($fieldName));
    }

    /** "Summe" column of a leaf row. $fieldName = "<Row>M1", $row = row key. */
    public function LedgerSum(string $fieldName, string $row): string
    {
        $values = array_map(fn (int $m): ?float => $this->cell($row . 'M' . $m), range(1, 6));

        return self::formatAmount($this->sumOrNull($values));
    }

    /** Row 5a: Kfz-Kosten betriebliches Kfz (net), per month ("1".."6") or "Sum". */
    public function LedgerCar(string $fieldName, string $month): string
    {
        return self::formatAmount($this->carTotal($month));
    }

    /** SUMME rows: "IncTotal1".."IncTotal6", "IncTotalSum", "ExpTotal1".."ExpTotalSum". */
    public function LedgerTotal(string $fieldName, string $key): string
    {
        if (!preg_match('/^(IncTotal|ExpTotal)(\d|Sum)$/', $key, $m)) {
            return '';
        }

        $months = $m[2] === 'Sum' ? range(1, 6) : [(int) $m[2]];
        $values = [];
        foreach ($months as $month) {
            if ($m[1] === 'IncTotal') {
                foreach (self::INCOME_ROWS as $row) {
                    $values[] = $this->cell($row . 'M' . $month);
                }
                continue;
            }
            foreach (self::EXPENSE_ROWS as $row) {
                $values[] = $this->cell($row . 'M' . $month);
            }
            $values[] = $this->carTotal((string) $month);
        }

        return self::formatAmount($this->sumOrNull($values));
    }

    /** Grand totals for the preview / plausibility (income, expenses). */
    public function totals(): array
    {
        return [
            'income' => self::parseAmount($this->LedgerTotal('Person', 'IncTotalSum')) ?? 0.0,
            'expenses' => self::parseAmount($this->LedgerTotal('Person', 'ExpTotalSum')) ?? 0.0,
        ];
    }

    private function carTotal(string $month): ?float
    {
        $months = $month === 'Sum' ? range(1, 6) : [(int) $month];
        $values = [];
        foreach ($months as $m) {
            foreach (self::CAR_ROWS as $row) {
                $values[] = $this->cell($row . 'M' . $m);
            }
            $private = $this->cell('CarPrivateKmM' . $m);
            $values[] = $private === null ? null : -$private;
        }

        return $this->sumOrNull($values);
    }

    private function cell(string $fieldName): ?float
    {
        $value = $this->getField($fieldName);
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : self::parseAmount((string) $value);
    }

    /**
     * Sum, or null when every value is empty (keeps untouched rows blank in the PDF).
     *
     * @param array<int, float|null> $values
     */
    private function sumOrNull(array $values): ?float
    {
        $present = array_filter($values, static fn (?float $value): bool => $value !== null);

        return $present === [] ? null : round(array_sum($present), 2);
    }

    protected function parentSubmission(): ?Submission
    {
        $parent = $this->Submission();

        return $parent && $parent->exists() ? $parent : null;
    }
}
