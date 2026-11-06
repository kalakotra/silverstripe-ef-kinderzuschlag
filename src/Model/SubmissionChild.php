<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Model;

use app\forms\basemodel\BaseSubmission;

/**
 * One child of the Kinderzuschlag application: KiZ 1-AnK "Anlage Kind" ($field_mapping) and, when the child gets
 * less maintenance than the minimum or none at all, KiZ 5c "Anlage zu Unterhalt und Unterhaltsvorschuss"
 * ($maintenance_field_mapping). Built in Form::finish() from Form_Child{N} / Form_ChildIncome{N} (prefix "Child{N}").
 */
class SubmissionChild extends BaseSubmission
{
    private static string $table_name = 'Kalakotra_EFormKinderzuschlag_SubmissionChild';

    private static string $default_sort = 'Number ASC';

    /** Maintenance values that require KiZ 5c. */
    public const MAINTENANCE_ANNEX = ['partial', 'none'];

    private static array $db = [
        'Number' => 'Int',

        // Form_Child{N}
        'LastName' => 'Varchar(40)',
        'FirstName' => 'Varchar(25)',
        'BirthDate' => 'Date',
        'Nationality' => 'Varchar(30)',
        'ApplicantRelation' => 'Varchar(4)',
        'PartnerRelation' => 'Varchar(4)',
        'Absent' => 'Boolean',
        'AbsenceReason' => 'Varchar(70)',
        'Married' => 'Boolean',
        'HasOwnChild' => 'Boolean',
        'LivesWithPartner' => 'Boolean',
        'VocationalSchool' => 'Boolean',
        'Boarding' => 'Boolean',
        'Inpatient' => 'Boolean',
        'NeedPregnancy' => 'Boolean',
        'DueDate' => 'Date',
        'NeedNutrition' => 'Boolean',
        'NutritionConsent' => 'Boolean',
        'NeedOther' => 'Boolean',

        // Form_ChildIncome{N}: income
        'Wage' => 'Boolean',
        'WageEvidence' => 'Varchar(12)',
        'CertTraining' => 'Varchar(3)',
        'CertTrainingUntil' => 'Date',
        'HolidayJobPeriod' => 'Varchar(30)',
        'SelfEmployed' => 'Boolean',
        'VoluntaryService' => 'Boolean',
        'BasicIncome' => 'Boolean',
        'Unemployment' => 'Boolean',
        'SickPay' => 'Boolean',
        'Pension' => 'Boolean',
        'Bafoeg' => 'Boolean',
        'OtherBenefits' => 'Boolean',
        'OtherIncome' => 'Boolean',

        // maintenance + KiZ 5c
        'Maintenance' => 'Varchar(16)',
        'MNoneNotCapable' => 'Boolean',
        'MNoneAddressUnknown' => 'Boolean',
        'MNoneAddressSteps' => 'Text',
        'MNonePaternity' => 'Boolean',
        'MNonePaternitySteps' => 'Text',
        'MNoneEqualCare' => 'Boolean',
        'MNoneSoloMother' => 'Boolean',
        'MNoneOther' => 'Boolean',
        'MNoneOtherText' => 'Text',
        'MPartialNotCapable' => 'Boolean',
        'MPartialOther' => 'Boolean',
        'MPartialOtherText' => 'Text',
        'MLawyer' => 'Boolean',
        'MLawyerDate' => 'Date',
        'MYouthOffice' => 'Boolean',
        'MYouthOfficePlace' => 'Varchar(35)',
        'MYouthOfficeDate' => 'Date',
        'MCourt' => 'Boolean',
        'MCourtDate' => 'Date',
        'MEnforcement' => 'Boolean',
        'MEnforcementDate' => 'Date',
        'MOtherAction' => 'Boolean',
        'MOtherActionText' => 'Text',
        'MUvgReason' => 'Varchar(16)',
        'MUvgReasonText' => 'Text',

        // expenses
        'PublicTransport' => 'Boolean',
        'CarKm' => 'Varchar(6)',
        'CarDays' => 'Varchar(3)',
        'DoubleHousehold' => 'Boolean',
        'Meals' => 'Boolean',
        'OtherWorkExpenses' => 'Boolean',
        'CarInsurance' => 'Boolean',
        'PensionProvision' => 'Boolean',
        'HealthInsurance' => 'Boolean',
        'MaintenancePayments' => 'Boolean',
    ];

    private static array $has_one = [
        'Submission' => Submission::class,
    ];

    /**
     * Dependency rules (unprefixed; the child steps use them with the "Child{N}" prefix).
     * Order matters: parents before children.
     */
    private static array $field_conditions = [
        [['AbsenceReason'], ['Absent' => '1']],
        [['DueDate'], ['NeedPregnancy' => '1']],
        [['NutritionConsent'], ['NeedNutrition' => '1']],
        [['WageEvidence', 'HolidayJobPeriod'], ['Wage' => '1']],
        [['CertTraining'], ['WageEvidence' => 'certificate']],
        [['CertTrainingUntil'], ['CertTraining' => 'yes']],
        [
            ['MNoneNotCapable', 'MNoneAddressUnknown', 'MNonePaternity', 'MNoneEqualCare', 'MNoneSoloMother', 'MNoneOther'],
            ['Maintenance' => 'none'],
        ],
        [['MNoneAddressSteps'], ['MNoneAddressUnknown' => '1']],
        [['MNonePaternitySteps'], ['MNonePaternity' => '1']],
        [['MNoneOtherText'], ['MNoneOther' => '1']],
        [['MPartialNotCapable', 'MPartialOther'], ['Maintenance' => 'partial']],
        [['MPartialOtherText'], ['MPartialOther' => '1']],
        [
            ['MLawyer', 'MYouthOffice', 'MCourt', 'MEnforcement', 'MOtherAction', 'MUvgReason'],
            ['Maintenance' => ['partial', 'none']],
        ],
        [['MLawyerDate'], ['MLawyer' => '1']],
        [['MYouthOfficePlace', 'MYouthOfficeDate'], ['MYouthOffice' => '1']],
        [['MCourtDate'], ['MCourt' => '1']],
        [['MEnforcementDate'], ['MEnforcement' => '1']],
        [['MOtherActionText'], ['MOtherAction' => '1']],
        [['MUvgReasonText'], ['MUvgReason' => ['not_eligible', 'other']]],
    ];

    /**
     * KiZ 1-AnK "Anlage Kind" (Familienkasse 01.26). Note: in this PDF "Alg-2-Kind" is the Grundsicherung row and
     * "Alg-1-Kind" the Arbeitslosengeld row (verified with a rendered test fill).
     */
    private static array $field_mapping = [
        'topmostSubform[0].Page1[0].Kopfzeile[0].Name_Vorname_KGB[0]' => 'ParentText:Number:HeaderName',
        'topmostSubform[0].Page1[0].Kopfzeile[0].KG-Nr[0]' => 'ParentText:Number:KindergeldNumber',
        'topmostSubform[0].Page1[0].Kopfzeile[0].Antragsdatum[0]' => 'ParentText:Number:Date',
        'topmostSubform[0].Page1[0].Punkt-1[0].Angaben-Kind[0].Name_Vorname_Kind[0]' => 'NameLine:LastName:x',
        'topmostSubform[0].Page1[0].Punkt-1[0].Angaben-Kind[0].Staatsangehörigkeit[0]' => 'Nationality',
        'topmostSubform[0].Page1[0].Punkt-1[0].Angaben-Kind[0].Geburtsdatum[0]' => 'DateFormating:BirthDate:1',
        'topmostSubform[0].Page1[0].Punkt-1[0].Verwandschaftsverhältnis[0].eigenes-A[0]' => 'StateValue:ApplicantRelation:topmostSubform[0].Page1[0].Punkt-1[0].Verwandschaftsverhältnis[0].eigenes-A[0]',
        'topmostSubform[0].Page1[0].Punkt-1[0].Verwandschaftsverhältnis[0].eigenes-P[0]' => 'StateValue:PartnerRelation:topmostSubform[0].Page1[0].Punkt-1[0].Verwandschaftsverhältnis[0].eigenes-P[0]',
        'topmostSubform[0].Page1[0].Punkt-1[0].Verwandschaftsverhältnis[0].Stief-A[0]' => 'StateValue:ApplicantRelation:topmostSubform[0].Page1[0].Punkt-1[0].Verwandschaftsverhältnis[0].Stief-A[0]',
        'topmostSubform[0].Page1[0].Punkt-1[0].Verwandschaftsverhältnis[0].Stief-P[0]' => 'StateValue:PartnerRelation:topmostSubform[0].Page1[0].Punkt-1[0].Verwandschaftsverhältnis[0].Stief-P[0]',
        'topmostSubform[0].Page1[0].Punkt-1[0].Verhältnisse[0].verheiratet[0]' => 'StateValue:Married:topmostSubform[0].Page1[0].Punkt-1[0].Verhältnisse[0].verheiratet[0]',
        'topmostSubform[0].Page1[0].Punkt-1[0].Verhältnisse[0].eigenes-Kind[0]' => 'StateValue:HasOwnChild:topmostSubform[0].Page1[0].Punkt-1[0].Verhältnisse[0].eigenes-Kind[0]',
        'topmostSubform[0].Page1[0].Punkt-1[0].Verhältnisse[0].zusammenlebend[0]' => 'StateValue:LivesWithPartner:topmostSubform[0].Page1[0].Punkt-1[0].Verhältnisse[0].zusammenlebend[0]',
        'topmostSubform[0].Page1[0].Punkt-1[0].Verhältnisse[0].Schule[0]' => 'StateValue:VocationalSchool:topmostSubform[0].Page1[0].Punkt-1[0].Verhältnisse[0].Schule[0]',
        'topmostSubform[0].Page1[0].Punkt-1[0].Verhältnisse[0].Wohnheim[0]' => 'StateValue:Boarding:topmostSubform[0].Page1[0].Punkt-1[0].Verhältnisse[0].Wohnheim[0]',
        'topmostSubform[0].Page1[0].Punkt-1[0].Verhältnisse[0].stationäre-Unterbring[0]' => 'StateValue:Inpatient:topmostSubform[0].Page1[0].Punkt-1[0].Verhältnisse[0].stationäre-Unterbring[0]',
        'topmostSubform[0].Page1[0].Punkt-1[0].Verhältnisse[0].keine-Angabe[0]' => 'NoSituation:Married:x',
        'topmostSubform[0].Page1[0].Punkt-2[0].Mehrbedarf-ja[0]' => 'AnyNeed:NeedPregnancy:x',
        'topmostSubform[0].Page1[0].Punkt-2[0].Entbindungstermin[0]' => 'DateFormating:DueDate:1',
        'topmostSubform[0].Page1[0].Punkt-3[0].Zeile-Lohn[0].Lohn-Kind[0]' => 'StateValue:WageEvidence:topmostSubform[0].Page1[0].Punkt-3[0].Zeile-Lohn[0].Lohn-Kind[0]',
        'topmostSubform[0].Page1[0].Punkt-3[0].Zeile-Lohn[0].Verdienstbesch-Kind[0]' => 'StateValue:WageEvidence:topmostSubform[0].Page1[0].Punkt-3[0].Zeile-Lohn[0].Verdienstbesch-Kind[0]',
        'topmostSubform[0].Page1[0].Punkt-3[0].Zeile-Lohn[0].Ferienjob[0]' => 'HolidayJobPeriod',
        'topmostSubform[0].Page1[0].Punkt-3[0].Zeile-Selbständige[0].KiZ5a-Kind[0]' => 'StateValue:SelfEmployed:topmostSubform[0].Page1[0].Punkt-3[0].Zeile-Selbständige[0].KiZ5a-Kind[0]',
        'topmostSubform[0].Page1[0].Punkt-3[0].Zeile-FWD[0].FWD-Kind[0]' => 'StateValue:VoluntaryService:topmostSubform[0].Page1[0].Punkt-3[0].Zeile-FWD[0].FWD-Kind[0]',
        'topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-and-Einkommen[0].Alg-2-Kind[0]' => 'StateValue:BasicIncome:topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-and-Einkommen[0].Alg-2-Kind[0]',
        'topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-and-Einkommen[0].Alg-1-Kind[0]' => 'StateValue:Unemployment:topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-and-Einkommen[0].Alg-1-Kind[0]',
        'topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-and-Einkommen[0].Krankengeld-Kind[0]' => 'StateValue:SickPay:topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-and-Einkommen[0].Krankengeld-Kind[0]',
        'topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-and-Einkommen[0].Rente-Kind[0]' => 'StateValue:Pension:topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-and-Einkommen[0].Rente-Kind[0]',
        'topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-and-Einkommen[0].Bafög-Kind[0]' => 'StateValue:Bafoeg:topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-and-Einkommen[0].Bafög-Kind[0]',
        'topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-and-Einkommen[0].staatl-Leist-Kind[0]' => 'StateValue:OtherBenefits:topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-and-Einkommen[0].staatl-Leist-Kind[0]',
        'topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-Unterhalt[0].UH-Kind[0]' => 'StateValue:Maintenance:topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-Unterhalt[0].UH-Kind[0]',
        'topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-Unterhalt[0].Unterhaltsvorschuss-Kind[0]' => 'StateValue:Maintenance:topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-Unterhalt[0].Unterhaltsvorschuss-Kind[0]',
        'topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-Unterhalt[0].KiZ5c-Kind[0]' => 'StateValue:Maintenance:topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-Unterhalt[0].KiZ5c-Kind[0]',
        'topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-sonst-Einnahmen[0].sonst-EK-Kind[0]' => 'StateValue:OtherIncome:topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-sonst-Einnahmen[0].sonst-EK-Kind[0]',
        'topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Werbungskosten[0].Fahrtkosten[0].FK-Öffis[0]' => 'StateValue:PublicTransport:topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Werbungskosten[0].Fahrtkosten[0].FK-Öffis[0]',
        'topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Werbungskosten[0].Fahrtkosten[0].FK-KfZ-km-A[0]' => 'CarKm',
        'topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Werbungskosten[0].Fahrtkosten[0].FK-Tage-A[0]' => 'CarDays',
        'topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Werbungskosten[0].#area[11].dopp-HH-KInd[0]' => 'StateValue:DoubleHousehold:topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Werbungskosten[0].#area[11].dopp-HH-KInd[0]',
        'topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Werbungskosten[0].#area[12].Verpfleg-Kind[0]' => 'StateValue:Meals:topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Werbungskosten[0].#area[12].Verpfleg-Kind[0]',
        'topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Werbungskosten[0].#area[13].sonst-WK-Kind[0]' => 'StateValue:OtherWorkExpenses:topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Werbungskosten[0].#area[13].sonst-WK-Kind[0]',
        'topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Versicherungen[0].Kfz-Haftpflicht-Kind[0]' => 'StateValue:CarInsurance:topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Versicherungen[0].Kfz-Haftpflicht-Kind[0]',
        'topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Versicherungen[0].Altersvorsorge-Kind[0]' => 'StateValue:PensionProvision:topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Versicherungen[0].Altersvorsorge-Kind[0]',
        'topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Versicherungen[0].sonst-Versich-Kind[0]' => 'StateValue:HealthInsurance:topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Versicherungen[0].sonst-Versich-Kind[0]',
        'topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Unterhaltszahlungen[0].Unterhaltszahl-Kind[0]' => 'StateValue:MaintenancePayments:topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Unterhaltszahlungen[0].Unterhaltszahl-Kind[0]',
        'topmostSubform[0].Page2[0].Erklärung[0].Unterschriftenzeile[0].Datum\.Antrag\.0[0]' => 'ParentText:Number:Date',
    ];

    /** KiZ 5c (Familienkasse 01.26). */
    private static array $maintenance_field_mapping = [
        'Formular1[0].Seite-1[0].#area[0].Name_Vorname_KGB_Kopf[0]' => 'ParentText:Number:HeaderName',
        'Formular1[0].Seite-1[0].#area[0].KG-Nr[0]' => 'ParentText:Number:KindergeldNumber',
        'Formular1[0].Seite-1[0].Punkt-1[0].Name_Vorname_Kind[0]' => 'NameLine:LastName:x',
        'Formular1[0].Seite-1[0].Punkt-1[0].Geburtsdatum[0]' => 'DateFormating:BirthDate:1',
        'Formular1[0].Seite-1[0].Punkt-2[0].nicht-leistungsfähig[0]' => 'StateValue:MNoneNotCapable:Formular1[0].Seite-1[0].Punkt-2[0].nicht-leistungsfähig[0]',
        'Formular1[0].Seite-1[0].Punkt-2[0].Adresse-unbekannt[0]' => 'StateValue:MNoneAddressUnknown:Formular1[0].Seite-1[0].Punkt-2[0].Adresse-unbekannt[0]',
        'Formular1[0].Seite-1[0].Punkt-2[0].Textfeld-Adresse[0]' => 'MNoneAddressSteps',
        'Formular1[0].Seite-1[0].Punkt-2[0].Vaterschaft[0]' => 'StateValue:MNonePaternity:Formular1[0].Seite-1[0].Punkt-2[0].Vaterschaft[0]',
        'Formular1[0].Seite-1[0].Punkt-2[0].Textfeld-Vaterschaft[0]' => 'MNonePaternitySteps',
        'Formular1[0].Seite-1[0].Punkt-2[0].private-Vereinbarung[0]' => 'StateValue:MNoneEqualCare:Formular1[0].Seite-1[0].Punkt-2[0].private-Vereinbarung[0]',
        'Formular1[0].Seite-1[0].Punkt-2[0].Solomutterschaft[0]' => 'StateValue:MNoneSoloMother:Formular1[0].Seite-1[0].Punkt-2[0].Solomutterschaft[0]',
        'Formular1[0].Seite-1[0].Punkt-2[0].#area[7].sonstige-Gründe[0]' => 'StateValue:MNoneOther:Formular1[0].Seite-1[0].Punkt-2[0].#area[7].sonstige-Gründe[0]',
        'Formular1[0].Seite-1[0].Punkt-2[0].#area[7].Textfeld-sonst-Gründe[0]' => 'MNoneOtherText',
        'Formular1[0].#subform[1].Punkt-3[0].nicht-leistbar[0]' => 'StateValue:MPartialNotCapable:Formular1[0].#subform[1].Punkt-3[0].nicht-leistbar[0]',
        'Formular1[0].#subform[1].Punkt-3[0].mangelnde-Bereitschaft[0]' => 'StateValue:MPartialOther:Formular1[0].#subform[1].Punkt-3[0].mangelnde-Bereitschaft[0]',
        'Formular1[0].#subform[1].Punkt-3[0].Textfeld-Erläuterung[0]' => 'MPartialOtherText',
        'Formular1[0].#subform[1].Punkt-4[0].#area[2].UH-über-RA[0]' => 'StateValue:MLawyer:Formular1[0].#subform[1].Punkt-4[0].#area[2].UH-über-RA[0]',
        'Formular1[0].#subform[1].Punkt-4[0].#area[2].Datum-4\.1[0]' => 'DateFormating:MLawyerDate:1',
        'Formular1[0].#subform[1].Punkt-4[0].#area[3].Jugendamt[0]' => 'StateValue:MYouthOffice:Formular1[0].#subform[1].Punkt-4[0].#area[3].Jugendamt[0]',
        'Formular1[0].#subform[1].Punkt-4[0].#area[3].Jugendamt-in[0]' => 'MYouthOfficePlace',
        'Formular1[0].#subform[1].Punkt-4[0].#area[3].Datum-4\.2[0]' => 'DateFormating:MYouthOfficeDate:1',
        'Formular1[0].#subform[1].Punkt-4[0].#area[4].Familiengericht[0]' => 'StateValue:MCourt:Formular1[0].#subform[1].Punkt-4[0].#area[4].Familiengericht[0]',
        'Formular1[0].#subform[1].Punkt-4[0].#area[4].Datum-4\.3[0]' => 'DateFormating:MCourtDate:1',
        'Formular1[0].#subform[1].Punkt-4[0].#area[5].Zwangsvollstreckung[0]' => 'StateValue:MEnforcement:Formular1[0].#subform[1].Punkt-4[0].#area[5].Zwangsvollstreckung[0]',
        'Formular1[0].#subform[1].Punkt-4[0].#area[5].Datum-4\.4[0]' => 'DateFormating:MEnforcementDate:1',
        'Formular1[0].#subform[1].Punkt-4[0].#area[6].Sonstiges[0]' => 'StateValue:MOtherAction:Formular1[0].#subform[1].Punkt-4[0].#area[6].Sonstiges[0]',
        'Formular1[0].#subform[1].Punkt-4[0].#area[6].Sonstiges-Erläuterungen[0]' => 'MOtherActionText',
        'Formular1[0].#subform[1].Punkt-5[0].Antrag-abgelehnt[0]' => 'StateValue:MUvgReason:Formular1[0].#subform[1].Punkt-5[0].Antrag-abgelehnt[0]',
        'Formular1[0].#subform[1].Punkt-5[0].noch-nicht-entschieden[0]' => 'StateValue:MUvgReason:Formular1[0].#subform[1].Punkt-5[0].noch-nicht-entschieden[0]',
        'Formular1[0].#subform[1].Punkt-5[0].kein-Antrag-gestellt[0]' => 'StateValue:MUvgReason:Formular1[0].#subform[1].Punkt-5[0].kein-Antrag-gestellt[0]',
        'Formular1[0].#subform[1].Punkt-5[0].Erläuterung-kein-Antrag[0]' => 'UvgText:MUvgReasonText:not_eligible',
        'Formular1[0].#subform[1].Punkt-5[0].#area[8].sonst-Gründe[0]' => 'StateValue:MUvgReason:Formular1[0].#subform[1].Punkt-5[0].#area[8].sonst-Gründe[0]',
        'Formular1[0].#subform[1].Punkt-5[0].#area[8].Erläuterung-sonst-Gründe[0]' => 'UvgText:MUvgReasonText:other',
        'Formular1[0].#subform[1].#area[9].Datum\.Antrag\.0[0]' => 'ParentText:Number:Date',
    ];

    /**
     * PDF field => [stored value => on-state "1"] for KiZ 1-AnK and KiZ 5c (field names are unique across both PDFs).
     */
    private static array $state_map = [
        // KiZ 1-AnK
        'topmostSubform[0].Page1[0].Punkt-1[0].Verwandschaftsverhältnis[0].eigenes-A[0]' => ['own' => '1'],
        'topmostSubform[0].Page1[0].Punkt-1[0].Verwandschaftsverhältnis[0].eigenes-P[0]' => ['own' => '1'],
        'topmostSubform[0].Page1[0].Punkt-1[0].Verwandschaftsverhältnis[0].Stief-A[0]' => ['step' => '1'],
        'topmostSubform[0].Page1[0].Punkt-1[0].Verwandschaftsverhältnis[0].Stief-P[0]' => ['step' => '1'],
        'topmostSubform[0].Page1[0].Punkt-1[0].Verhältnisse[0].verheiratet[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Punkt-1[0].Verhältnisse[0].eigenes-Kind[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Punkt-1[0].Verhältnisse[0].zusammenlebend[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Punkt-1[0].Verhältnisse[0].Schule[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Punkt-1[0].Verhältnisse[0].Wohnheim[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Punkt-1[0].Verhältnisse[0].stationäre-Unterbring[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Punkt-3[0].Zeile-Lohn[0].Lohn-Kind[0]' => ['payslips' => '1'],
        'topmostSubform[0].Page1[0].Punkt-3[0].Zeile-Lohn[0].Verdienstbesch-Kind[0]' => ['certificate' => '1'],
        'topmostSubform[0].Page1[0].Punkt-3[0].Zeile-Selbständige[0].KiZ5a-Kind[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Punkt-3[0].Zeile-FWD[0].FWD-Kind[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-and-Einkommen[0].Alg-2-Kind[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-and-Einkommen[0].Alg-1-Kind[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-and-Einkommen[0].Krankengeld-Kind[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-and-Einkommen[0].Rente-Kind[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-and-Einkommen[0].Bafög-Kind[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-and-Einkommen[0].staatl-Leist-Kind[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-Unterhalt[0].UH-Kind[0]' => ['full' => '1', 'partial' => '1'],
        'topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-Unterhalt[0].Unterhaltsvorschuss-Kind[0]' => ['uvg' => '1'],
        'topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-Unterhalt[0].KiZ5c-Kind[0]' => ['partial' => '1', 'none' => '1'],
        'topmostSubform[0].Page2[0].Punkt-3-Fortsetzung[0].Zeile-sonst-Einnahmen[0].sonst-EK-Kind[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Werbungskosten[0].Fahrtkosten[0].FK-Öffis[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Werbungskosten[0].#area[11].dopp-HH-KInd[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Werbungskosten[0].#area[12].Verpfleg-Kind[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Werbungskosten[0].#area[13].sonst-WK-Kind[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Versicherungen[0].Kfz-Haftpflicht-Kind[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Versicherungen[0].Altersvorsorge-Kind[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Versicherungen[0].sonst-Versich-Kind[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Punkt-4[0].Zeile-Unterhaltszahlungen[0].Unterhaltszahl-Kind[0]' => ['1' => '1'],

        // KiZ 5c
        'Formular1[0].Seite-1[0].Punkt-2[0].nicht-leistungsfähig[0]' => ['1' => '1'],
        'Formular1[0].Seite-1[0].Punkt-2[0].Adresse-unbekannt[0]' => ['1' => '1'],
        'Formular1[0].Seite-1[0].Punkt-2[0].Vaterschaft[0]' => ['1' => '1'],
        'Formular1[0].Seite-1[0].Punkt-2[0].private-Vereinbarung[0]' => ['1' => '1'],
        'Formular1[0].Seite-1[0].Punkt-2[0].Solomutterschaft[0]' => ['1' => '1'],
        'Formular1[0].Seite-1[0].Punkt-2[0].#area[7].sonstige-Gründe[0]' => ['1' => '1'],
        'Formular1[0].#subform[1].Punkt-3[0].nicht-leistbar[0]' => ['1' => '1'],
        'Formular1[0].#subform[1].Punkt-3[0].mangelnde-Bereitschaft[0]' => ['1' => '1'],
        'Formular1[0].#subform[1].Punkt-4[0].#area[2].UH-über-RA[0]' => ['1' => '1'],
        'Formular1[0].#subform[1].Punkt-4[0].#area[3].Jugendamt[0]' => ['1' => '1'],
        'Formular1[0].#subform[1].Punkt-4[0].#area[4].Familiengericht[0]' => ['1' => '1'],
        'Formular1[0].#subform[1].Punkt-4[0].#area[5].Zwangsvollstreckung[0]' => ['1' => '1'],
        'Formular1[0].#subform[1].Punkt-4[0].#area[6].Sonstiges[0]' => ['1' => '1'],
        'Formular1[0].#subform[1].Punkt-5[0].Antrag-abgelehnt[0]' => ['rejected' => '1'],
        'Formular1[0].#subform[1].Punkt-5[0].noch-nicht-entschieden[0]' => ['pending' => '1'],
        'Formular1[0].#subform[1].Punkt-5[0].kein-Antrag-gestellt[0]' => ['not_eligible' => '1'],
        'Formular1[0].#subform[1].Punkt-5[0].#area[8].sonst-Gründe[0]' => ['other' => '1'],
    ];

    /** Situation boxes of AnK Punkt 1 ("keine der Angaben trifft zu" when none is checked). */
    private const SITUATION_FIELDS = ['Married', 'HasOwnChild', 'LivesWithPartner', 'VocationalSchool', 'Boarding', 'Inpatient'];

    /**
     * Step data (prefix "Child{N}" already stripped) -> record.
     *
     * @param array<string, mixed> $data
     */
    public static function fromStepData(int $number, array $data): self
    {
        $record = self::create();
        $db = (array) self::config()->get('db');

        foreach ($data as $field => $value) {
            if (array_key_exists($field, $db) && $field !== 'Number') {
                $record->setField($field, $value);
            }
        }

        $record->Number = $number;

        return $record;
    }

    public function needsMaintenanceAnnex(): bool
    {
        return in_array((string) $this->Maintenance, self::MAINTENANCE_ANNEX, true);
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

    /**
     * KiZ 5c field data.
     *
     * @return array<string, mixed>
     */
    public function getMaintenanceDataForPDF(): array
    {
        return MappingResolver::resolve($this, (array) $this->config()->get('maintenance_field_mapping'));
    }

    // ---------------------------------------------------------------------------------------------
    // Mapping helpers (signature fixed by BaseSubmission::getArrayDataForPDF: method($field, $arg))
    // ---------------------------------------------------------------------------------------------

    public function StateValue(string $fieldName, string $pdfField): string
    {
        return FieldConditions::stateValue($this, $fieldName, (array) ($this->config()->get('state_map')[$pdfField] ?? []));
    }

    /** Value provided by the main submission (header, application date). */
    public function ParentText(string $fieldName, string $key): string
    {
        $parent = $this->parentSubmission();

        return $parent ? $parent->textFor($key) : '';
    }

    /** "Familienname, Vorname". */
    public function NameLine(string $fieldName, string $unused = ''): string
    {
        return implode(', ', array_filter([trim((string) $this->LastName), trim((string) $this->FirstName)], 'strlen'));
    }

    /** AnK Punkt 1 "keine der Angaben trifft zu". */
    public function NoSituation(string $fieldName, string $unused = ''): string
    {
        foreach (self::SITUATION_FIELDS as $field) {
            if ($this->getField($field)) {
                return '';
            }
        }

        return '1';
    }

    /** AnK Punkt 2 "Mehrbedarf: Ja". */
    public function AnyNeed(string $fieldName, string $unused = ''): string
    {
        return $this->NeedPregnancy || $this->NeedNutrition || $this->NeedOther ? '1' : '';
    }

    /** KiZ 5c Punkt 5: the explanation goes next to the selected reason. */
    public function UvgText(string $fieldName, string $reason): string
    {
        return (string) $this->MUvgReason === $reason ? trim((string) $this->getField($fieldName)) : '';
    }

    protected function parentSubmission(): ?Submission
    {
        $parent = $this->Submission();

        return $parent && $parent->exists() ? $parent : null;
    }
}
