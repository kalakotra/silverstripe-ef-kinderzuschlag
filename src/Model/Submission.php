<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Model;

use app\forms\basemodel\BaseSubmission;
use DateTimeImmutable;
use mikehaertl\pdftk\Pdf;
use RuntimeException;
use SilverStripe\ORM\DataList;

/**
 * Antrag auf Kinderzuschlag (Familienkasse). One submission produces a merged PDF:
 *
 *   full:  KiZ 1 Antrag, KiZ 1-AnA, per child KiZ 1-AnK (+ KiZ 5c), KiZ 5a (applicant/partner), KiZ 5 (per employed
 *          person with "Verdienstbescheinigung"), KiZ 6 (erhebliches Vermögen), KiZ 7 (per person with
 *          kostenaufwändiger Ernährung)
 *   short: KiZ 1-K Kurzantrag
 *
 * Every document has its own mapping (keys = PDF field names of that document):
 *   $field_mapping (KiZ 1), $ana_field_mapping, $short_field_mapping, $assets_field_mapping on this class,
 *   SubmissionChild ($field_mapping = AnK, $maintenance_field_mapping = 5c), SubmissionSelfEmployed (5a),
 *   $earnings_field_mapping (KiZ 5) / $nutrition_field_mapping (KiZ 7): PDF field => token of personDocumentValues().
 * Mapping conventions (see BaseSubmission::getArrayDataForPDF): 'PDF field' => 'DbField' | 'Method:DbField:Arg'.
 * Every checkbox of these PDFs has the on-state "1" -> StateValue + $state_map.
 */
class Submission extends BaseSubmission
{
    private static string $table_name = 'Kalakotra_EFormKinderzuschlag_Submission';

    private static array $db = [
        // Form_Step1
        'ApplicationType' => 'Varchar(5)',
        'KindergeldNumber' => 'Varchar(11)',
        'SecondApplicantKGNumber' => 'Varchar(11)',
        'ShortChangedHousehold' => 'Varchar(3)',
        'ShortChangedAssets' => 'Varchar(3)',
        'ShortChangedIncome' => 'Varchar(3)',
        'ShortChangedHousing' => 'Varchar(3)',
        'ShortLastWasShort' => 'Varchar(3)',

        // Form_Applicant
        'LastName' => 'Varchar(60)',
        'FirstName' => 'Varchar(60)',
        'BirthDate' => 'Date',
        'BirthName' => 'Varchar(60)',
        'Title' => 'Varchar(25)',
        'Street' => 'Varchar(60)',
        'PostalCode' => 'Varchar(10)',
        'City' => 'Varchar(40)',

        // Form_Family
        'Gender' => 'Varchar(12)',
        'Nationality' => 'Varchar(45)',
        'Phone' => 'Varchar(30)',
        'MaritalStatus' => 'Varchar(10)',
        'MaritalSince' => 'Date',
        'HasPartner' => 'Varchar(3)',
        'PartnerLastName' => 'Varchar(40)',
        'PartnerFirstName' => 'Varchar(30)',
        'PartnerBirthName' => 'Varchar(25)',
        'PartnerNationality' => 'Varchar(22)',
        'PartnerBirthDate' => 'Date',
        'PartnerInTraining' => 'Boolean',
        'PartnerInpatient' => 'Boolean',
        'PartnerForeignBenefit' => 'Boolean',
        'PartnerNeedPregnancy' => 'Boolean',
        'PartnerDueDate' => 'Date',
        'PartnerNeedNutrition' => 'Boolean',
        'PartnerNeedOther' => 'Boolean',

        // Form_Bank
        'IBAN' => 'Varchar(42)',
        'BIC' => 'Varchar(11)',
        'AccountHolder' => 'Varchar(60)',
        'ChildCount' => 'Int',

        // Form_Household
        'OtherChildrenCount' => 'Int',
        'OtherChild1Name' => 'Varchar(45)',
        'OtherChild1BirthDate' => 'Date',
        'OtherChild1Presence' => 'Varchar(40)',
        'OtherChild2Name' => 'Varchar(45)',
        'OtherChild2BirthDate' => 'Date',
        'OtherChild2Presence' => 'Varchar(40)',
        'OtherChild3Name' => 'Varchar(45)',
        'OtherChild3BirthDate' => 'Date',
        'OtherChild3Presence' => 'Varchar(40)',
        'HouseholdPersonsCount' => 'Int',
        'HouseholdPerson1Name' => 'Varchar(45)',
        'HouseholdPerson1BirthDate' => 'Date',
        'HouseholdPerson1RelationApplicant' => 'Varchar(20)',
        'HouseholdPerson1RelationPartner' => 'Varchar(20)',
        'HouseholdPerson2Name' => 'Varchar(45)',
        'HouseholdPerson2BirthDate' => 'Date',
        'HouseholdPerson2RelationApplicant' => 'Varchar(20)',
        'HouseholdPerson2RelationPartner' => 'Varchar(20)',
        'HouseholdPerson3Name' => 'Varchar(45)',
        'HouseholdPerson3BirthDate' => 'Date',
        'HouseholdPerson3RelationApplicant' => 'Varchar(20)',
        'HouseholdPerson3RelationPartner' => 'Varchar(20)',
        'HouseholdPerson4Name' => 'Varchar(45)',
        'HouseholdPerson4BirthDate' => 'Date',
        'HouseholdPerson4RelationApplicant' => 'Varchar(20)',
        'HouseholdPerson4RelationPartner' => 'Varchar(20)',
        'HouseholdPerson5Name' => 'Varchar(45)',
        'HouseholdPerson5BirthDate' => 'Date',
        'HouseholdPerson5RelationApplicant' => 'Varchar(20)',
        'HouseholdPerson5RelationPartner' => 'Varchar(20)',

        // Form_Living
        'InTraining' => 'Boolean',
        'Inpatient' => 'Boolean',
        'ForeignBenefit' => 'Boolean',
        'Housing' => 'Varchar(4)',
        'RentContract' => 'Boolean',
        'RentBankStatement' => 'Boolean',
        'RentHeating' => 'Boolean',
        'RentUtilities' => 'Boolean',
        'RentHousingBenefit' => 'Boolean',
        'RentSubsidy' => 'Boolean',
        'OwnInterest' => 'Boolean',
        'OwnTaxInsurance' => 'Boolean',
        'OwnHeating' => 'Boolean',
        'OwnUtilities' => 'Boolean',
        'OwnHousingBenefit' => 'Boolean',
        'MoveInDate' => 'Date',
        'HotWater' => 'Varchar(10)',
        'HasAssets' => 'Varchar(3)',
        'NeedPregnancy' => 'Boolean',
        'DueDate' => 'Date',
        'NeedSingleParent' => 'Boolean',
        'NeedNutrition' => 'Boolean',
        'NutritionConsent' => 'Boolean',
        'NeedOther' => 'Boolean',

        // Form_IncomeApplicant / Form_IncomePartner
        'ApplicantWage' => 'Boolean',
        'ApplicantWageEvidence' => 'Varchar(12)',
        'ApplicantCertTraining' => 'Varchar(3)',
        'ApplicantCertTrainingUntil' => 'Date',
        'ApplicantSelfEmployed' => 'Boolean',
        'ApplicantSelfEmployedOtherEvidence' => 'Boolean',
        'ApplicantVoluntaryService' => 'Boolean',
        'ApplicantBasicIncome' => 'Boolean',
        'ApplicantUnemployment' => 'Boolean',
        'ApplicantSickPay' => 'Boolean',
        'ApplicantPension' => 'Boolean',
        'ApplicantParentalAllowance' => 'Boolean',
        'ApplicantBafoeg' => 'Boolean',
        'ApplicantOtherBenefits' => 'Boolean',
        'ApplicantMaintenance' => 'Boolean',
        'ApplicantRental' => 'Boolean',
        'ApplicantOtherIncome' => 'Boolean',
        'ApplicantPublicTransport' => 'Boolean',
        'ApplicantCarKm' => 'Varchar(6)',
        'ApplicantCarDays' => 'Varchar(3)',
        'ApplicantDoubleHousehold' => 'Boolean',
        'ApplicantMeals' => 'Boolean',
        'ApplicantOtherWorkExpenses' => 'Boolean',
        'ApplicantCarInsurance' => 'Boolean',
        'ApplicantRiester' => 'Boolean',
        'ApplicantHealthInsurance' => 'Boolean',
        'ApplicantMaintenancePayments' => 'Boolean',
        'ApplicantChildcare' => 'Boolean',
        'PartnerWage' => 'Boolean',
        'PartnerWageEvidence' => 'Varchar(12)',
        'PartnerCertTraining' => 'Varchar(3)',
        'PartnerCertTrainingUntil' => 'Date',
        'PartnerSelfEmployed' => 'Boolean',
        'PartnerSelfEmployedOtherEvidence' => 'Boolean',
        'PartnerVoluntaryService' => 'Boolean',
        'PartnerBasicIncome' => 'Boolean',
        'PartnerUnemployment' => 'Boolean',
        'PartnerSickPay' => 'Boolean',
        'PartnerPension' => 'Boolean',
        'PartnerParentalAllowance' => 'Boolean',
        'PartnerBafoeg' => 'Boolean',
        'PartnerOtherBenefits' => 'Boolean',
        'PartnerMaintenance' => 'Boolean',
        'PartnerRental' => 'Boolean',
        'PartnerOtherIncome' => 'Boolean',
        'PartnerPublicTransport' => 'Boolean',
        'PartnerCarKm' => 'Varchar(6)',
        'PartnerCarDays' => 'Varchar(3)',
        'PartnerDoubleHousehold' => 'Boolean',
        'PartnerMeals' => 'Boolean',
        'PartnerOtherWorkExpenses' => 'Boolean',
        'PartnerCarInsurance' => 'Boolean',
        'PartnerRiester' => 'Boolean',
        'PartnerHealthInsurance' => 'Boolean',
        'PartnerMaintenancePayments' => 'Boolean',
        'PartnerChildcare' => 'Boolean',

        // Form_Assets
        'AssetGiro' => 'Boolean',
        'AssetSavings' => 'Boolean',
        'AssetBuildingSavings' => 'Boolean',
        'AssetSecurities' => 'Boolean',
        'AssetInsurance' => 'Boolean',
        'AssetCash' => 'Boolean',
        'AssetCashDetails' => 'Varchar(50)',
        'AssetRiester' => 'Boolean',
        'AssetVehicle' => 'Boolean',
        'AssetRealEstate' => 'Boolean',
        'SelfEmployedPension' => 'Varchar(3)',
        'PensionPerson1Name' => 'Varchar(50)',
        'PensionPerson1Years' => 'Varchar(10)',
        'PensionPerson2Name' => 'Varchar(50)',
        'PensionPerson2Years' => 'Varchar(10)',
        'PensionPerson3Name' => 'Varchar(50)',
        'PensionPerson3Years' => 'Varchar(10)',
        'PensionAssets' => 'Text',
        'OtherAssets' => 'Varchar(3)',
        'OtherAsset1Name' => 'Varchar(50)',
        'OtherAsset1Value' => 'Varchar(20)',
        'OtherAsset2Name' => 'Varchar(50)',
        'OtherAsset2Value' => 'Varchar(20)',
        'OtherAsset3Name' => 'Varchar(50)',
        'OtherAsset3Value' => 'Varchar(20)',
        'Gift' => 'Varchar(3)',
        'GiftRecipient' => 'Varchar(50)',
        'GiftValue' => 'Varchar(30)',
        'GiftPurpose' => 'Varchar(50)',
        'GiftDate' => 'Varchar(30)',
    ];

    private static array $has_many = [
        'Children' => SubmissionChild::class,
        'SelfEmployedRecords' => SubmissionSelfEmployed::class,
    ];

    private static array $cascade_deletes = [
        'Children',
        'SelfEmployedRecords',
    ];

    /**
     * Dependency rules, mirrored by the display-logic in the steps (see FieldConditions for the format).
     * Order matters: parents before children.
     */
    private static array $field_conditions = [
        // Form_Step1
        [
            ['ShortChangedHousehold', 'ShortChangedAssets', 'ShortChangedIncome', 'ShortChangedHousing', 'ShortLastWasShort'],
            ['ApplicationType' => 'short'],
        ],

        // Form_Family
        [['MaritalSince'], ['MaritalStatus' => ['married', 'separated', 'divorced', 'widowed']]],
        [
            [
                'PartnerLastName', 'PartnerFirstName', 'PartnerBirthName', 'PartnerNationality', 'PartnerBirthDate',
                'PartnerInTraining', 'PartnerInpatient', 'PartnerForeignBenefit', 'PartnerNeedPregnancy',
                'PartnerNeedNutrition', 'PartnerNeedOther',
            ],
            ['HasPartner' => 'yes'],
        ],
        [['PartnerDueDate'], ['PartnerNeedPregnancy' => '1']],

        // Form_Household
        [['OtherChild1Name', 'OtherChild1BirthDate', 'OtherChild1Presence'], ['OtherChildrenCount' => ['1', '2', '3']]],
        [['OtherChild2Name', 'OtherChild2BirthDate', 'OtherChild2Presence'], ['OtherChildrenCount' => ['2', '3']]],
        [['OtherChild3Name', 'OtherChild3BirthDate', 'OtherChild3Presence'], ['OtherChildrenCount' => '3']],
        [
            ['HouseholdPerson1Name', 'HouseholdPerson1BirthDate', 'HouseholdPerson1RelationApplicant', 'HouseholdPerson1RelationPartner'],
            ['HouseholdPersonsCount' => ['1', '2', '3', '4', '5']],
        ],
        [
            ['HouseholdPerson2Name', 'HouseholdPerson2BirthDate', 'HouseholdPerson2RelationApplicant', 'HouseholdPerson2RelationPartner'],
            ['HouseholdPersonsCount' => ['2', '3', '4', '5']],
        ],
        [
            ['HouseholdPerson3Name', 'HouseholdPerson3BirthDate', 'HouseholdPerson3RelationApplicant', 'HouseholdPerson3RelationPartner'],
            ['HouseholdPersonsCount' => ['3', '4', '5']],
        ],
        [
            ['HouseholdPerson4Name', 'HouseholdPerson4BirthDate', 'HouseholdPerson4RelationApplicant', 'HouseholdPerson4RelationPartner'],
            ['HouseholdPersonsCount' => ['4', '5']],
        ],
        [
            ['HouseholdPerson5Name', 'HouseholdPerson5BirthDate', 'HouseholdPerson5RelationApplicant', 'HouseholdPerson5RelationPartner'],
            ['HouseholdPersonsCount' => '5'],
        ],

        // Form_Living
        [
            ['RentContract', 'RentBankStatement', 'RentHeating', 'RentUtilities', 'RentHousingBenefit', 'RentSubsidy'],
            ['Housing' => 'rent'],
        ],
        [['OwnInterest', 'OwnTaxInsurance', 'OwnHeating', 'OwnUtilities', 'OwnHousingBenefit', 'MoveInDate'], ['Housing' => 'own']],
        [['DueDate'], ['NeedPregnancy' => '1']],
        [['NutritionConsent'], ['NeedNutrition' => '1']],

        // Form_IncomeApplicant / Form_IncomePartner
        [['ApplicantWageEvidence'], ['ApplicantWage' => '1']],
        [['ApplicantCertTraining'], ['ApplicantWageEvidence' => 'certificate']],
        [['ApplicantCertTrainingUntil'], ['ApplicantCertTraining' => 'yes']],
        [['ApplicantSelfEmployedOtherEvidence'], ['ApplicantSelfEmployed' => '1']],
        // Form_IncomePartner is only on the path with a partner (whole-step gate, no display-logic)
        [
            [
                'PartnerWage', 'PartnerWageEvidence', 'PartnerCertTraining', 'PartnerCertTrainingUntil',
                'PartnerSelfEmployed', 'PartnerSelfEmployedOtherEvidence', 'PartnerVoluntaryService',
                'PartnerBasicIncome', 'PartnerUnemployment', 'PartnerSickPay', 'PartnerPension',
                'PartnerParentalAllowance', 'PartnerBafoeg', 'PartnerOtherBenefits', 'PartnerMaintenance',
                'PartnerRental', 'PartnerOtherIncome', 'PartnerPublicTransport', 'PartnerCarKm', 'PartnerCarDays',
                'PartnerDoubleHousehold', 'PartnerMeals', 'PartnerOtherWorkExpenses', 'PartnerCarInsurance',
                'PartnerRiester', 'PartnerHealthInsurance', 'PartnerMaintenancePayments', 'PartnerChildcare',
            ],
            ['HasPartner' => 'yes'],
        ],
        [['PartnerWageEvidence'], ['PartnerWage' => '1']],
        [['PartnerCertTraining'], ['PartnerWageEvidence' => 'certificate']],
        [['PartnerCertTrainingUntil'], ['PartnerCertTraining' => 'yes']],
        [['PartnerSelfEmployedOtherEvidence'], ['PartnerSelfEmployed' => '1']],

        // Form_Assets
        // Form_Assets (KiZ 6) is only on the path with erheblichem Vermögen (whole-step gate)
        [
            [
                'AssetGiro', 'AssetSavings', 'AssetBuildingSavings', 'AssetSecurities', 'AssetInsurance', 'AssetCash',
                'AssetCashDetails', 'AssetRiester', 'AssetVehicle', 'AssetRealEstate', 'SelfEmployedPension',
                'PensionPerson1Name', 'PensionPerson1Years', 'PensionPerson2Name', 'PensionPerson2Years',
                'PensionPerson3Name', 'PensionPerson3Years', 'PensionAssets', 'OtherAssets', 'OtherAsset1Name',
                'OtherAsset1Value', 'OtherAsset2Name', 'OtherAsset2Value', 'OtherAsset3Name', 'OtherAsset3Value',
                'Gift', 'GiftRecipient', 'GiftValue', 'GiftPurpose', 'GiftDate',
            ],
            ['HasAssets' => 'yes'],
        ],
        [['AssetCashDetails'], ['AssetCash' => '1']],
        [
            [
                'PensionPerson1Name', 'PensionPerson1Years', 'PensionPerson2Name', 'PensionPerson2Years',
                'PensionPerson3Name', 'PensionPerson3Years', 'PensionAssets',
            ],
            ['SelfEmployedPension' => 'yes'],
        ],
        [
            ['OtherAsset1Name', 'OtherAsset1Value', 'OtherAsset2Name', 'OtherAsset2Value', 'OtherAsset3Name', 'OtherAsset3Value'],
            ['OtherAssets' => 'yes'],
        ],
        [['GiftRecipient', 'GiftValue', 'GiftPurpose', 'GiftDate'], ['Gift' => 'yes']],
    ];

    /**
     * KiZ 1 Antrag auf Kinderzuschlag (Familienkasse 02.24). Page 1 (Vorblatt) has no fields.
     */
    private static array $field_mapping = [
        'Formular1[0].Seite-1[0].Kopfzeile[0].#area[2].Name_Vorname_KGB[0]' => 'DocumentText:LastName:HeaderName',
        'Formular1[0].Seite-1[0].Kopfzeile[0].#area[2].KG-Nr[0]' => 'KindergeldNumber',

        // Punkt 1
        'Formular1[0].Seite-1[0].Frage-1[0].Name-KGB[0]' => 'DocumentText:LastName:HeaderName',
        'Formular1[0].Seite-1[0].Frage-1[0].Geburtsdatum-KGB[0]' => 'DateFormating:BirthDate:1',
        'Formular1[0].Seite-1[0].Frage-1[0].abweichenderGeburtsname[0]' => 'BirthName',
        'Formular1[0].Seite-1[0].Frage-1[0].Titel-KGB[0]' => 'Title',
        'Formular1[0].Seite-1[0].Frage-1[0].Anschrift[0]' => 'DocumentText:Street:Address',
        'Formular1[0].Seite-1[0].Frage-1[0].Geschlecht[0]' => 'PdfValue:Gender:gender',
        'Formular1[0].Seite-1[0].Frage-1[0].Staatsangeh-KGB[0]' => 'Nationality',
        'Formular1[0].Seite-1[0].Frage-1[0].Telefonnr[0]' => 'Phone',
        'Formular1[0].Seite-1[0].Frage-1[0].ledig[0]' => 'StateValue:MaritalStatus:Formular1[0].Seite-1[0].Frage-1[0].ledig[0]',
        'Formular1[0].Seite-1[0].Frage-1[0].verheiratet[0]' => 'StateValue:MaritalStatus:Formular1[0].Seite-1[0].Frage-1[0].verheiratet[0]',
        'Formular1[0].Seite-1[0].Frage-1[0].geschieden[0]' => 'StateValue:MaritalStatus:Formular1[0].Seite-1[0].Frage-1[0].geschieden[0]',
        'Formular1[0].Seite-1[0].Frage-1[0].getrennt-lebend[0]' => 'StateValue:MaritalStatus:Formular1[0].Seite-1[0].Frage-1[0].getrennt-lebend[0]',
        'Formular1[0].Seite-1[0].Frage-1[0].verwitwet[0]' => 'StateValue:MaritalStatus:Formular1[0].Seite-1[0].Frage-1[0].verwitwet[0]',
        'Formular1[0].Seite-1[0].Frage-1[0].Fam-stand-seit[0]' => 'DateFormating:MaritalSince:1',

        // Punkt 2
        'Formular1[0].Seite-1[0].Frage-2[0].Name-Partner[0]' => 'PartnerNameLine:PartnerLastName:x',
        'Formular1[0].Seite-1[0].Frage-2[0].Staatsangeh-Partner[0]' => 'PartnerNationality',
        'Formular1[0].Seite-1[0].Frage-2[0].Geburtsdatum-Partner[0]' => 'DateFormating:PartnerBirthDate:1',

        // Punkt 3
        // IBAN / BIC: printed character by character into the boxes (documents.antrag.combs), the fields stay empty
        'Formular1[0].Seite-1[0].Frage-3[0].Kontoinhaber[0]' => 'AccountHolder',

        // Punkt 4 (children from SubmissionChild)
        'Formular1[0].Seite-1[0].Frage-4[0].Kindertabelle-P-4[0].Zeile1[0].Zelle1[0]' => 'ChildCell:ChildCount:1-name',
        'Formular1[0].Seite-1[0].Frage-4[0].Kindertabelle-P-4[0].Zeile1[0].Zelle2[0]' => 'ChildCell:ChildCount:1-birth',
        'Formular1[0].Seite-1[0].Frage-4[0].Kindertabelle-P-4[0].Zeile2[0].Zelle1[0]' => 'ChildCell:ChildCount:2-name',
        'Formular1[0].Seite-1[0].Frage-4[0].Kindertabelle-P-4[0].Zeile2[0].Zelle2[0]' => 'ChildCell:ChildCount:2-birth',
        'Formular1[0].Seite-1[0].Frage-4[0].Kindertabelle-P-4[0].Zeile3[0].Zelle1[0]' => 'ChildCell:ChildCount:3-name',
        'Formular1[0].Seite-1[0].Frage-4[0].Kindertabelle-P-4[0].Zeile3[0].Zelle2[0]' => 'ChildCell:ChildCount:3-birth',
        'Formular1[0].Seite-1[0].Frage-4[0].Kindertabelle-P-4[0].Zeile4[0].Zelle1[0]' => 'ChildCell:ChildCount:4-name',
        'Formular1[0].Seite-1[0].Frage-4[0].Kindertabelle-P-4[0].Zeile4[0].Zelle2[0]' => 'ChildCell:ChildCount:4-birth',
        'Formular1[0].Seite-1[0].Frage-4[0].Kindertabelle-P-4[0].Zeile5[0].Zelle1[0]' => 'ChildCell:ChildCount:5-name',
        'Formular1[0].Seite-1[0].Frage-4[0].Kindertabelle-P-4[0].Zeile5[0].Zelle2[0]' => 'ChildCell:ChildCount:5-birth',
        'Formular1[0].Seite-1[0].Frage-4[0].Kindertabelle-P-4[0].Zeile6[0].Zelle1[0]' => 'ChildCell:ChildCount:6-name',
        'Formular1[0].Seite-1[0].Frage-4[0].Kindertabelle-P-4[0].Zeile6[0].Zelle2[0]' => 'ChildCell:ChildCount:6-birth',

        // Punkt 5 (children not permanently in the household; more than three are joined into row 3)
        'Formular1[0].Seite-2[0].Frage-5[0].Kindertabelle-P-5[0].Zeile1[0].Zelle1[0]' => 'AbsentCell:ChildCount:1-name',
        'Formular1[0].Seite-2[0].Frage-5[0].Kindertabelle-P-5[0].Zeile1[0].Zelle3[0]' => 'AbsentCell:ChildCount:1-reason',
        'Formular1[0].Seite-2[0].Frage-5[0].Kindertabelle-P-5[0].Zeile2[0].Zelle1[0]' => 'AbsentCell:ChildCount:2-name',
        'Formular1[0].Seite-2[0].Frage-5[0].Kindertabelle-P-5[0].Zeile2[0].Zelle1[1]' => 'AbsentCell:ChildCount:2-reason',
        'Formular1[0].Seite-2[0].Frage-5[0].Kindertabelle-P-5[0].Zeile3[0].Zelle1[0]' => 'AbsentCell:ChildCount:3-name',
        'Formular1[0].Seite-2[0].Frage-5[0].Kindertabelle-P-5[0].Zeile3[0].Zelle3[0]' => 'AbsentCell:ChildCount:3-reason',

        // Punkt 6
        'Formular1[0].Seite-2[0].Frage-6[0].Kindertabelle-P-6[0].Zeile1[0].Zelle1[0]' => 'OtherChild1Name',
        'Formular1[0].Seite-2[0].Frage-6[0].Kindertabelle-P-6[0].Zeile1[0].Zelle2[0]' => 'DateFormating:OtherChild1BirthDate:1',
        'Formular1[0].Seite-2[0].Frage-6[0].Kindertabelle-P-6[0].Zeile1[0].Zelle3[0]' => 'OtherChild1Presence',
        'Formular1[0].Seite-2[0].Frage-6[0].Kindertabelle-P-6[0].Zeile2[0].Zelle1[0]' => 'OtherChild2Name',
        'Formular1[0].Seite-2[0].Frage-6[0].Kindertabelle-P-6[0].Zeile2[0].Zelle2[0]' => 'DateFormating:OtherChild2BirthDate:1',
        'Formular1[0].Seite-2[0].Frage-6[0].Kindertabelle-P-6[0].Zeile2[0].Zelle3[0]' => 'OtherChild2Presence',
        'Formular1[0].Seite-2[0].Frage-6[0].Kindertabelle-P-6[0].Zeile3[0].Zelle1[0]' => 'OtherChild3Name',
        'Formular1[0].Seite-2[0].Frage-6[0].Kindertabelle-P-6[0].Zeile3[0].Zelle2[0]' => 'DateFormating:OtherChild3BirthDate:1',
        'Formular1[0].Seite-2[0].Frage-6[0].Kindertabelle-P-6[0].Zeile3[0].Zelle3[0]' => 'OtherChild3Presence',

        // Punkt 7
        'Formular1[0].Seite-2[0].Frage-7[0].Tabelle-P-7[0].Zeile1[0].Zelle1[0]' => 'HouseholdPerson1Name',
        'Formular1[0].Seite-2[0].Frage-7[0].Tabelle-P-7[0].Zeile1[0].Zelle2[0]' => 'DateFormating:HouseholdPerson1BirthDate:1',
        'Formular1[0].Seite-2[0].Frage-7[0].Tabelle-P-7[0].Zeile1[0].Zelle3[0]' => 'HouseholdPerson1RelationApplicant',
        'Formular1[0].Seite-2[0].Frage-7[0].Tabelle-P-7[0].Zeile1[0].Zelle4[0]' => 'HouseholdPerson1RelationPartner',
        'Formular1[0].Seite-2[0].Frage-7[0].Tabelle-P-7[0].Zeile2[0].Zelle1[0]' => 'HouseholdPerson2Name',
        'Formular1[0].Seite-2[0].Frage-7[0].Tabelle-P-7[0].Zeile2[0].Zelle2[0]' => 'DateFormating:HouseholdPerson2BirthDate:1',
        'Formular1[0].Seite-2[0].Frage-7[0].Tabelle-P-7[0].Zeile2[0].Zelle3[0]' => 'HouseholdPerson2RelationApplicant',
        'Formular1[0].Seite-2[0].Frage-7[0].Tabelle-P-7[0].Zeile2[0].Zelle4[0]' => 'HouseholdPerson2RelationPartner',
        'Formular1[0].Seite-2[0].Frage-7[0].Tabelle-P-7[0].Zeile3[0].Zelle1[0]' => 'HouseholdPerson3Name',
        'Formular1[0].Seite-2[0].Frage-7[0].Tabelle-P-7[0].Zeile3[0].Zelle2[0]' => 'DateFormating:HouseholdPerson3BirthDate:1',
        'Formular1[0].Seite-2[0].Frage-7[0].Tabelle-P-7[0].Zeile3[0].#field[2]' => 'HouseholdPerson3RelationApplicant',
        'Formular1[0].Seite-2[0].Frage-7[0].Tabelle-P-7[0].Zeile3[0].Zelle4[0]' => 'HouseholdPerson3RelationPartner',
        'Formular1[0].Seite-2[0].Frage-7[0].Tabelle-P-7[0].Zeile4[0].Zelle1[0]' => 'HouseholdPerson4Name',
        'Formular1[0].Seite-2[0].Frage-7[0].Tabelle-P-7[0].Zeile4[0].Zelle2[0]' => 'DateFormating:HouseholdPerson4BirthDate:1',
        'Formular1[0].Seite-2[0].Frage-7[0].Tabelle-P-7[0].Zeile4[0].Zelle3[0]' => 'HouseholdPerson4RelationApplicant',
        'Formular1[0].Seite-2[0].Frage-7[0].Tabelle-P-7[0].Zeile4[0].Zelle4[0]' => 'HouseholdPerson4RelationPartner',
        'Formular1[0].Seite-2[0].Frage-7[0].Tabelle-P-7[0].Zeile5[0].Zelle1[0]' => 'HouseholdPerson5Name',
        'Formular1[0].Seite-2[0].Frage-7[0].Tabelle-P-7[0].Zeile5[0].Zelle2[0]' => 'DateFormating:HouseholdPerson5BirthDate:1',
        'Formular1[0].Seite-2[0].Frage-7[0].Tabelle-P-7[0].Zeile5[0].Zelle3[0]' => 'HouseholdPerson5RelationApplicant',
        'Formular1[0].Seite-2[0].Frage-7[0].Tabelle-P-7[0].Zeile5[0].Zelle4[0]' => 'HouseholdPerson5RelationPartner',

        // Erklärung
        'Formular1[0].Seite-2[0].#area[3].Datum\.Antrag\.0[0]' => 'DocumentText:LastName:Date',
        'Formular1[0].Seite-2[0].#area[3].KG-Nr-zweiter-Antragsteller[0]' => 'SecondApplicantKGNumber',
    ];

    /**
     * KiZ 1-AnA Anlage Antragsteller(in) und Partner(in) (Familienkasse 01.26).
     * Note: in this PDF "Alg-1-*" is the Grundsicherungsgeld row and "Alg-2-*" the Arbeitslosengeld row
     * (verified with a rendered test fill; KiZ 1-AnK uses the opposite naming).
     */
    private static array $ana_field_mapping = [
        'topmostSubform[0].Page1[0].Kopfzeile[0].Name_Vorname_KGB[0]' => 'DocumentText:LastName:HeaderName',
        'topmostSubform[0].Page1[0].Kopfzeile[0].KG-Nr[0]' => 'KindergeldNumber',
        'topmostSubform[0].Page1[0].Kopfzeile[0].Antragsdatum[0]' => 'DocumentText:LastName:Date',

        // Punkt 1
        'topmostSubform[0].Page1[0].Frage-1[0].Ausbildung-Antragsteller[0]' => 'StateValue:InTraining:topmostSubform[0].Page1[0].Frage-1[0].Ausbildung-Antragsteller[0]',
        'topmostSubform[0].Page1[0].Frage-1[0].stationäre-Unterbring-Antragsteller[0]' => 'StateValue:Inpatient:topmostSubform[0].Page1[0].Frage-1[0].stationäre-Unterbring-Antragsteller[0]',
        'topmostSubform[0].Page1[0].Frage-1[0].öffentl-Dienst-Antragsteller[0]' => 'StateValue:ForeignBenefit:topmostSubform[0].Page1[0].Frage-1[0].öffentl-Dienst-Antragsteller[0]',
        'topmostSubform[0].Page1[0].Frage-1[0].Ausbildung-Partner[0]' => 'StateValue:PartnerInTraining:topmostSubform[0].Page1[0].Frage-1[0].Ausbildung-Partner[0]',
        'topmostSubform[0].Page1[0].Frage-1[0].stationäre-Unterbring-Partner[0]' => 'StateValue:PartnerInpatient:topmostSubform[0].Page1[0].Frage-1[0].stationäre-Unterbring-Partner[0]',
        'topmostSubform[0].Page1[0].Frage-1[0].öffentl-Dienst-Partner[0]' => 'StateValue:PartnerForeignBenefit:topmostSubform[0].Page1[0].Frage-1[0].öffentl-Dienst-Partner[0]',

        // Punkt 2
        'topmostSubform[0].Page1[0].Frage-2[0].Miete[0]' => 'StateValue:Housing:topmostSubform[0].Page1[0].Frage-2[0].Miete[0]',
        'topmostSubform[0].Page1[0].Frage-2[0].Mietvertrag[0]' => 'StateValue:RentContract:topmostSubform[0].Page1[0].Frage-2[0].Mietvertrag[0]',
        'topmostSubform[0].Page1[0].Frage-2[0].Kontoauszug[0]' => 'StateValue:RentBankStatement:topmostSubform[0].Page1[0].Frage-2[0].Kontoauszug[0]',
        'topmostSubform[0].Page1[0].Frage-2[0].Heizkosten-Miete[0]' => 'StateValue:RentHeating:topmostSubform[0].Page1[0].Frage-2[0].Heizkosten-Miete[0]',
        'topmostSubform[0].Page1[0].Frage-2[0].Betriebskosten[0]' => 'StateValue:RentUtilities:topmostSubform[0].Page1[0].Frage-2[0].Betriebskosten[0]',
        'topmostSubform[0].Page1[0].Frage-2[0].Wohngeldbescheid-Miete[0]' => 'StateValue:RentHousingBenefit:topmostSubform[0].Page1[0].Frage-2[0].Wohngeldbescheid-Miete[0]',
        'topmostSubform[0].Page1[0].Frage-2[0].#area[3].Mietzuschuss[0]' => 'StateValue:RentSubsidy:topmostSubform[0].Page1[0].Frage-2[0].#area[3].Mietzuschuss[0]',
        'topmostSubform[0].Page1[0].Frage-2[0].Eigenheim[0]' => 'StateValue:Housing:topmostSubform[0].Page1[0].Frage-2[0].Eigenheim[0]',
        'topmostSubform[0].Page1[0].Frage-2[0].Schuldzinsen[0]' => 'StateValue:OwnInterest:topmostSubform[0].Page1[0].Frage-2[0].Schuldzinsen[0]',
        'topmostSubform[0].Page1[0].Frage-2[0].Grundsteuer[0]' => 'StateValue:OwnTaxInsurance:topmostSubform[0].Page1[0].Frage-2[0].Grundsteuer[0]',
        'topmostSubform[0].Page1[0].Frage-2[0].Heizkosten-Eigenheim[0]' => 'StateValue:OwnHeating:topmostSubform[0].Page1[0].Frage-2[0].Heizkosten-Eigenheim[0]',
        'topmostSubform[0].Page1[0].Frage-2[0].Nebenkosten[0]' => 'StateValue:OwnUtilities:topmostSubform[0].Page1[0].Frage-2[0].Nebenkosten[0]',
        'topmostSubform[0].Page1[0].Frage-2[0].Wohngeldbescheid-Eigenheim[0]' => 'StateValue:OwnHousingBenefit:topmostSubform[0].Page1[0].Frage-2[0].Wohngeldbescheid-Eigenheim[0]',
        'topmostSubform[0].Page1[0].Frage-2[0].#area[4].Einzugsdatum[0]' => 'DateFormating:MoveInDate:1',
        'topmostSubform[0].Page1[0].Frage-2[0].WW-Erzeugung[0].zentral[0]' => 'StateValue:HotWater:topmostSubform[0].Page1[0].Frage-2[0].WW-Erzeugung[0].zentral[0]',
        'topmostSubform[0].Page1[0].Frage-2[0].WW-Erzeugung[0].dezentral[0]' => 'StateValue:HotWater:topmostSubform[0].Page1[0].Frage-2[0].WW-Erzeugung[0].dezentral[0]',

        // Punkt 3 / 4
        'topmostSubform[0].Page2[0].Frage-3[0].Vermögen-ja[0]' => 'StateValue:HasAssets:topmostSubform[0].Page2[0].Frage-3[0].Vermögen-ja[0]',
        'topmostSubform[0].Page2[0].Frage-3[0].Vermögen-nein[0]' => 'StateValue:HasAssets:topmostSubform[0].Page2[0].Frage-3[0].Vermögen-nein[0]',
        'topmostSubform[0].Page2[0].Frage-4[0].Mehrbedarf-ja[0]' => 'AnyNeed:NeedPregnancy:x',
        'topmostSubform[0].Page2[0].Frage-4[0].Entbindungstermin[0]' => 'DueDates:DueDate:x',

        // Punkt 5 / 6, columns "für mich" (A) and "Partner(in)" (P)
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Lohn[0].Lohn-A[0]' => 'StateValue:ApplicantWageEvidence:topmostSubform[0].Page2[0].Frage-5[0].Zeile-Lohn[0].Lohn-A[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Lohn[0].Verdienstbesch-A[0]' => 'StateValue:ApplicantWageEvidence:topmostSubform[0].Page2[0].Frage-5[0].Zeile-Lohn[0].Verdienstbesch-A[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Einkommen-Selbständ[0].KiZ5a-A[0]' => 'StateValue:ApplicantSelfEmployed:topmostSubform[0].Page2[0].Frage-5[0].Zeile-Einkommen-Selbständ[0].KiZ5a-A[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Einkommen-Selbständ[0].selbst-a-Nachw-A[0]' => 'StateValue:ApplicantSelfEmployedOtherEvidence:topmostSubform[0].Page2[0].Frage-5[0].Zeile-Einkommen-Selbständ[0].selbst-a-Nachw-A[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-FWD[0].FWD-Nachw-A[0]' => 'StateValue:ApplicantVoluntaryService:topmostSubform[0].Page2[0].Frage-5[0].Zeile-FWD[0].FWD-Nachw-A[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Alg-1-A[0]' => 'StateValue:ApplicantBasicIncome:topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Alg-1-A[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Alg-2-A[0]' => 'StateValue:ApplicantUnemployment:topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Alg-2-A[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Krankengeld-A[0]' => 'StateValue:ApplicantSickPay:topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Krankengeld-A[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Rente-A[0]' => 'StateValue:ApplicantPension:topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Rente-A[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Elterngeld-Antragst[0]' => 'StateValue:ApplicantParentalAllowance:topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Elterngeld-Antragst[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Bafög-A[0]' => 'StateValue:ApplicantBafoeg:topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Bafög-A[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Leist-staatl-A[0]' => 'StateValue:ApplicantOtherBenefits:topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Leist-staatl-A[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Unterhalt[0].UH-A[0]' => 'StateValue:ApplicantMaintenance:topmostSubform[0].Page2[0].Frage-5[0].Zeile-Unterhalt[0].UH-A[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Mieteinnahmen[0].Mieteinnahmen-A[0]' => 'StateValue:ApplicantRental:topmostSubform[0].Page2[0].Frage-5[0].Zeile-Mieteinnahmen[0].Mieteinnahmen-A[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-sonst-Einnahmen[0].sonst-Einn-A[0]' => 'StateValue:ApplicantOtherIncome:topmostSubform[0].Page2[0].Frage-5[0].Zeile-sonst-Einnahmen[0].sonst-Einn-A[0]',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[4].FK-Öffis-A[0]' => 'StateValue:ApplicantPublicTransport:topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[4].FK-Öffis-A[0]',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[5].FK-KfZ-km-A[0]' => 'ApplicantCarKm',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[6].FK-Tage-A[0]' => 'ApplicantCarDays',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[7].doppelte-HH-A[0]' => 'StateValue:ApplicantDoubleHousehold:topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[7].doppelte-HH-A[0]',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[8].Verpfleg-A[0]' => 'StateValue:ApplicantMeals:topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[8].Verpfleg-A[0]',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[9].sonst-WK-A[0]' => 'StateValue:ApplicantOtherWorkExpenses:topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[9].sonst-WK-A[0]',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Versicherungen[0].Kfz-Versich-A[0]' => 'StateValue:ApplicantCarInsurance:topmostSubform[0].Page3[0].Frage-6[0].Zeile-Versicherungen[0].Kfz-Versich-A[0]',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Versicherungen[0].Riester-A[0]' => 'StateValue:ApplicantRiester:topmostSubform[0].Page3[0].Frage-6[0].Zeile-Versicherungen[0].Riester-A[0]',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Versicherungen[0].sonst-Versich-A[0]' => 'StateValue:ApplicantHealthInsurance:topmostSubform[0].Page3[0].Frage-6[0].Zeile-Versicherungen[0].sonst-Versich-A[0]',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Unterhaltszahlungen[0].Unterhaltszahl-A[0]' => 'StateValue:ApplicantMaintenancePayments:topmostSubform[0].Page3[0].Frage-6[0].Zeile-Unterhaltszahlungen[0].Unterhaltszahl-A[0]',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Kinderbetr-kosten[0].Betr-kosten-A[0]' => 'StateValue:ApplicantChildcare:topmostSubform[0].Page3[0].Frage-6[0].Zeile-Kinderbetr-kosten[0].Betr-kosten-A[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Lohn[0].Lohn-P[0]' => 'StateValue:PartnerWageEvidence:topmostSubform[0].Page2[0].Frage-5[0].Zeile-Lohn[0].Lohn-P[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Lohn[0].Verdienstbesch-P[0]' => 'StateValue:PartnerWageEvidence:topmostSubform[0].Page2[0].Frage-5[0].Zeile-Lohn[0].Verdienstbesch-P[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Einkommen-Selbständ[0].KiZ5a-P[0]' => 'StateValue:PartnerSelfEmployed:topmostSubform[0].Page2[0].Frage-5[0].Zeile-Einkommen-Selbständ[0].KiZ5a-P[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Einkommen-Selbständ[0].selbst-a-Nachw-P[0]' => 'StateValue:PartnerSelfEmployedOtherEvidence:topmostSubform[0].Page2[0].Frage-5[0].Zeile-Einkommen-Selbständ[0].selbst-a-Nachw-P[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-FWD[0].FWD-Nachw-P[0]' => 'StateValue:PartnerVoluntaryService:topmostSubform[0].Page2[0].Frage-5[0].Zeile-FWD[0].FWD-Nachw-P[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Alg-1-P[0]' => 'StateValue:PartnerBasicIncome:topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Alg-1-P[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Alg-2-P[0]' => 'StateValue:PartnerUnemployment:topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Alg-2-P[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Krankengeld-P[0]' => 'StateValue:PartnerSickPay:topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Krankengeld-P[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Rente-P[0]' => 'StateValue:PartnerPension:topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Rente-P[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Elterngeld-Partner[0]' => 'StateValue:PartnerParentalAllowance:topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Elterngeld-Partner[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Bafög-P[0]' => 'StateValue:PartnerBafoeg:topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Bafög-P[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Leist-staatl-P[0]' => 'StateValue:PartnerOtherBenefits:topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Leist-staatl-P[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Unterhalt[0].UH-P[0]' => 'StateValue:PartnerMaintenance:topmostSubform[0].Page2[0].Frage-5[0].Zeile-Unterhalt[0].UH-P[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Mieteinnahmen[0].Mieteinnahmen-P[0]' => 'StateValue:PartnerRental:topmostSubform[0].Page2[0].Frage-5[0].Zeile-Mieteinnahmen[0].Mieteinnahmen-P[0]',
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-sonst-Einnahmen[0].sonst-Einn-P[0]' => 'StateValue:PartnerOtherIncome:topmostSubform[0].Page2[0].Frage-5[0].Zeile-sonst-Einnahmen[0].sonst-Einn-P[0]',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[4].FK-Öffis-P[0]' => 'StateValue:PartnerPublicTransport:topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[4].FK-Öffis-P[0]',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[5].FK-KfZ-km-P[0]' => 'PartnerCarKm',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[6].FK-Tage-P[0]' => 'PartnerCarDays',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[7].doppelte-HH-P[0]' => 'StateValue:PartnerDoubleHousehold:topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[7].doppelte-HH-P[0]',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[8].Verpfleg-P[0]' => 'StateValue:PartnerMeals:topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[8].Verpfleg-P[0]',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[9].sonst-WK-P[0]' => 'StateValue:PartnerOtherWorkExpenses:topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[9].sonst-WK-P[0]',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Versicherungen[0].Kfz-Versich-P[0]' => 'StateValue:PartnerCarInsurance:topmostSubform[0].Page3[0].Frage-6[0].Zeile-Versicherungen[0].Kfz-Versich-P[0]',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Versicherungen[0].Riester-P[0]' => 'StateValue:PartnerRiester:topmostSubform[0].Page3[0].Frage-6[0].Zeile-Versicherungen[0].Riester-P[0]',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Versicherungen[0].sonst-Versich-P[0]' => 'StateValue:PartnerHealthInsurance:topmostSubform[0].Page3[0].Frage-6[0].Zeile-Versicherungen[0].sonst-Versich-P[0]',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Unterhaltszahlungen[0].Unterhaltszahl-P[0]' => 'StateValue:PartnerMaintenancePayments:topmostSubform[0].Page3[0].Frage-6[0].Zeile-Unterhaltszahlungen[0].Unterhaltszahl-P[0]',
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Kinderbetr-kosten[0].Betr-kosten-P[0]' => 'StateValue:PartnerChildcare:topmostSubform[0].Page3[0].Frage-6[0].Zeile-Kinderbetr-kosten[0].Betr-kosten-P[0]',

        'topmostSubform[0].Page3[0].Erklärung[0].Unterschriftenzeile[0].Datum\.Antrag\.0[0]' => 'DocumentText:LastName:Date',
    ];

    /** KiZ 1-K Kurzantrag (Familienkasse 01.26). Page 1 (Hinweise) has no fields. */
    private static array $short_field_mapping = [
        'topmostSubform[0].Page2[0].Kopfzeile[0].Name_Vorname_KGB[0]' => 'DocumentText:LastName:HeaderName',
        'topmostSubform[0].Page2[0].Kopfzeile[0].KG-Nr[0]' => 'KindergeldNumber',
        'topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[3].BDG-ja[0]' => 'StateValue:ShortChangedHousehold:topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[3].BDG-ja[0]',
        'topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[3].DDG-nein[0]' => 'StateValue:ShortChangedHousehold:topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[3].DDG-nein[0]',
        'topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[4].VM-ja[0]' => 'StateValue:ShortChangedAssets:topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[4].VM-ja[0]',
        'topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[4].VM-nein[0]' => 'StateValue:ShortChangedAssets:topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[4].VM-nein[0]',
        'topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[5].EK-ja[0]' => 'StateValue:ShortChangedIncome:topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[5].EK-ja[0]',
        'topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[5].EK-nein[0]' => 'StateValue:ShortChangedIncome:topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[5].EK-nein[0]',
        'topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[6].WK-ja[0]' => 'StateValue:ShortChangedHousing:topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[6].WK-ja[0]',
        'topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[6].WK-nein[0]' => 'StateValue:ShortChangedHousing:topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[6].WK-nein[0]',
        'topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[7].KA-ja[0]' => 'StateValue:ShortLastWasShort:topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[7].KA-ja[0]',
        'topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[7].KA-nein[0]' => 'StateValue:ShortLastWasShort:topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[7].KA-nein[0]',
        'topmostSubform[0].Page2[0].Frage-1[0].Name-Antragsteller[0]' => 'DocumentText:LastName:HeaderName',
        'topmostSubform[0].Page2[0].Frage-1[0].Geburtsdatum[0]' => 'DateFormating:BirthDate:1',
        'topmostSubform[0].Page2[0].Frage-1[0].Geburtsname[0]' => 'BirthName',
        'topmostSubform[0].Page2[0].Frage-1[0].Titel[0]' => 'Title',
        'topmostSubform[0].Page2[0].Frage-1[0].Anschrift[0]' => 'DocumentText:Street:Address',
        'topmostSubform[0].Page2[0].Erklärung[0].Datum\.Antrag\.0[0]' => 'DocumentText:LastName:Date',
        'topmostSubform[0].Page2[0].Erklärung[0].KG-Nr-zweiter-Antragsteller[0]' => 'SecondApplicantKGNumber',
    ];

    /** KiZ 6 Anlage zum erheblichen Vermögen (Familienkasse 01.24). */
    private static array $assets_field_mapping = [
        'topmostSubform[0].Page1[0].Kopfangaben[0].Name_Vorname_KGB[0]' => 'DocumentText:LastName:HeaderName',
        'topmostSubform[0].Page1[0].Kopfangaben[0].KG-Nr[0]' => 'KindergeldNumber',
        'topmostSubform[0].Page1[0].Punkt-1[0].Giro-Konto[0].Girokonto-ja[0]' => 'StateValue:AssetGiro:topmostSubform[0].Page1[0].Punkt-1[0].Giro-Konto[0].Girokonto-ja[0]',
        'topmostSubform[0].Page1[0].Punkt-1[0].Spar-Konto[0].Sparkonto-ja[0]' => 'StateValue:AssetSavings:topmostSubform[0].Page1[0].Punkt-1[0].Spar-Konto[0].Sparkonto-ja[0]',
        'topmostSubform[0].Page1[0].Punkt-1[0].Bausparvertrag[0].Bauspar-ja[0]' => 'StateValue:AssetBuildingSavings:topmostSubform[0].Page1[0].Punkt-1[0].Bausparvertrag[0].Bauspar-ja[0]',
        'topmostSubform[0].Page1[0].Punkt-1[0].Aktien[0].Aktien-ja[0]' => 'StateValue:AssetSecurities:topmostSubform[0].Page1[0].Punkt-1[0].Aktien[0].Aktien-ja[0]',
        'topmostSubform[0].Page1[0].Punkt-1[0].Lebensversicherung[0].Lebensversich-ja[0]' => 'StateValue:AssetInsurance:topmostSubform[0].Page1[0].Punkt-1[0].Lebensversicherung[0].Lebensversich-ja[0]',
        'topmostSubform[0].Page1[0].Punkt-1[0].Bargelder[0].Bargeld-Betrag[0]' => 'AssetCashDetails',
        'topmostSubform[0].Page1[0].Punkt-1[0].Bargelder[0].Bargeld-ja[0]' => 'StateValue:AssetCash:topmostSubform[0].Page1[0].Punkt-1[0].Bargelder[0].Bargeld-ja[0]',
        'topmostSubform[0].Page1[0].Punkt-1[0].Altersvorsorge[0].Riester-ja[0]' => 'StateValue:AssetRiester:topmostSubform[0].Page1[0].Punkt-1[0].Altersvorsorge[0].Riester-ja[0]',
        'topmostSubform[0].Page1[0].Punkt-1[0].Kfz[0].Auto-ja[0]' => 'StateValue:AssetVehicle:topmostSubform[0].Page1[0].Punkt-1[0].Kfz[0].Auto-ja[0]',
        'topmostSubform[0].Page1[0].Punkt-1[0].Immobilien[0].Eigenheim-ja[0]' => 'StateValue:AssetRealEstate:topmostSubform[0].Page1[0].Punkt-1[0].Immobilien[0].Eigenheim-ja[0]',
        'topmostSubform[0].Page2[0].Punkt-2[0].Befreiung-RV[0].RV-befreit-ja[0]' => 'StateValue:SelfEmployedPension:topmostSubform[0].Page2[0].Punkt-2[0].Befreiung-RV[0].RV-befreit-ja[0]',
        'topmostSubform[0].Page2[0].Punkt-2[0].Befreiung-RV[0].RV-befreit-nein[0]' => 'StateValue:SelfEmployedPension:topmostSubform[0].Page2[0].Punkt-2[0].Befreiung-RV[0].RV-befreit-nein[0]',
        'topmostSubform[0].Page2[0].Punkt-2[0].Alterssicherung[0]' => 'PensionAssets',
        'topmostSubform[0].Page2[0].Punkt-2[0].Tabelle-Pkt-2[0].Zeile1[0].Zelle1[0]' => 'PensionPerson1Name',
        'topmostSubform[0].Page2[0].Punkt-2[0].Tabelle-Pkt-2[0].Zeile1[0].Zelle2[0]' => 'PensionPerson1Years',
        'topmostSubform[0].Page2[0].Punkt-2[0].Tabelle-Pkt-2[0].Zeile2[0].Zelle1[0]' => 'PensionPerson2Name',
        'topmostSubform[0].Page2[0].Punkt-2[0].Tabelle-Pkt-2[0].Zeile2[0].Zelle2[0]' => 'PensionPerson2Years',
        'topmostSubform[0].Page2[0].Punkt-2[0].Tabelle-Pkt-2[0].Zeile3[0].Zelle1[0]' => 'PensionPerson3Name',
        'topmostSubform[0].Page2[0].Punkt-2[0].Tabelle-Pkt-2[0].Zeile3[0].Zelle2[0]' => 'PensionPerson3Years',
        'topmostSubform[0].Page2[0].Punkt-3[0].sonst-Vermögen[0].sonst-VM-ja[0]' => 'StateValue:OtherAssets:topmostSubform[0].Page2[0].Punkt-3[0].sonst-Vermögen[0].sonst-VM-ja[0]',
        'topmostSubform[0].Page2[0].Punkt-3[0].sonst-Vermögen[0].sonst-VM-nein[0]' => 'StateValue:OtherAssets:topmostSubform[0].Page2[0].Punkt-3[0].sonst-Vermögen[0].sonst-VM-nein[0]',
        'topmostSubform[0].Page2[0].Punkt-3[0].Tabelle-sonst-VM[0].Zeile1[0].Zelle1[0]' => 'OtherAsset1Name',
        'topmostSubform[0].Page2[0].Punkt-3[0].Tabelle-sonst-VM[0].Zeile1[0].Zelle2[0]' => 'OtherAsset1Value',
        'topmostSubform[0].Page2[0].Punkt-3[0].Tabelle-sonst-VM[0].Zeile2[0].Zelle1[0]' => 'OtherAsset2Name',
        'topmostSubform[0].Page2[0].Punkt-3[0].Tabelle-sonst-VM[0].Zeile2[0].Zelle2[0]' => 'OtherAsset2Value',
        'topmostSubform[0].Page2[0].Punkt-3[0].Tabelle-sonst-VM[0].Zeile3[0].Zelle1[0]' => 'OtherAsset3Name',
        'topmostSubform[0].Page2[0].Punkt-3[0].Tabelle-sonst-VM[0].Zeile3[0].Zelle2[0]' => 'OtherAsset3Value',
        'topmostSubform[0].Page2[0].Punkt-4[0].Verschenkt[0].Schenkung-ja[0]' => 'StateValue:Gift:topmostSubform[0].Page2[0].Punkt-4[0].Verschenkt[0].Schenkung-ja[0]',
        'topmostSubform[0].Page2[0].Punkt-4[0].Verschenkt[0].Schenkung-nein[0]' => 'StateValue:Gift:topmostSubform[0].Page2[0].Punkt-4[0].Verschenkt[0].Schenkung-nein[0]',
        'topmostSubform[0].Page2[0].Punkt-4[0].Empfänger[0]' => 'GiftRecipient',
        'topmostSubform[0].Page2[0].Punkt-4[0].Wert[0]' => 'GiftValue',
        'topmostSubform[0].Page2[0].Punkt-4[0].Zweck[0]' => 'GiftPurpose',
        'topmostSubform[0].Page2[0].Punkt-4[0].Zeitpunkt[0]' => 'GiftDate',
        'topmostSubform[0].Page2[0].#area[6].Datum\.Antrag\.0[0]' => 'DocumentText:LastName:Date',
    ];

    /**
     * KiZ 5 Verdienstbescheinigung des Arbeitgebers (Familienkasse 01.26): only the applicant's part
     * (header, Bemessungszeitraum, Punkt 1). Values = tokens of personDocumentValues().
     * Note: DateField2 is the start ("für die Monate"), DateField1 the end ("bis") of the period.
     */
    private static array $earnings_field_mapping = [
        'topmostSubform[0].Page1[0].Kopfangaben[0].Name_Vorname_KGB[0]' => 'header',
        'topmostSubform[0].Page1[0].Kopfangaben[0].KG-Nr[0]' => 'kg',
        'topmostSubform[0].Page1[0].Titelzeile[0].DateField2[0]' => 'periodFrom',
        'topmostSubform[0].Page1[0].Titelzeile[0].DateField1[0]' => 'periodTo',
        'topmostSubform[0].Page1[0].Frage-1[0].#area[5].Name_Vorname_KGB[1]' => 'name',
        'topmostSubform[0].Page1[0].Frage-1[0].#area[5].Geburtsdatum[0]' => 'birthDate',
        'topmostSubform[0].Page1[0].Frage-1[0].#area[6].Ausbild-bis[0]' => 'trainingUntil',
        'topmostSubform[0].Page1[0].Frage-1[0].#area[6].#area[7].Ausbild-ja[0]' => 'trainingYes',
        'topmostSubform[0].Page1[0].Frage-1[0].#area[6].#area[7].Ausbild-nein[0]' => 'trainingNo',
    ];

    /**
     * KiZ 7 Anlage zum Mehrbedarf bei kostenaufwändiger Ernährung (Familienkasse 01.23): Punkt 1 and 2.
     * Punkt 3 (ärztliche Bescheinigung) is left to the doctor. Values = tokens of personDocumentValues().
     */
    private static array $nutrition_field_mapping = [
        'topmostSubform[0].Page1[0].Kopfzeile[0].Name_Vorname_KGB-Kopf[0]' => 'header',
        'topmostSubform[0].Page1[0].Kopfzeile[0].KG-Nr-Kopf[0]' => 'kg',
        'topmostSubform[0].Page1[0].#area[2].Punkt-1[0].Antrag-MB[0]' => 'claim',
        'topmostSubform[0].Page1[0].#area[2].Datum\.F1[0]' => 'date',
        'topmostSubform[0].Page1[0].Punkt-2-m-Entbind-Schweigepflicht[0].Punkt-2[0].Name_Vorname_KGB-F2[0]' => 'name',
        'topmostSubform[0].Page1[0].Punkt-2-m-Entbind-Schweigepflicht[0].Punkt-2[0].Geburtsdatum-F2[0]' => 'birthDate',
        'topmostSubform[0].Page1[0].Punkt-2-m-Entbind-Schweigepflicht[0].Punkt-2[0].Anschrift-F2[0]' => 'address',
        'topmostSubform[0].Page1[0].Punkt-2-m-Entbind-Schweigepflicht[0].Punkt-2[0].Entbind-Schweigepflicht[0].Datum\.F2[0]' => 'consentDate',
    ];

    /**
     * PDF field => [stored value => on-state "1"] for KiZ 1, KiZ 1-AnA, KiZ 1-K and KiZ 6
     * (field names are unique across these PDFs).
     */
    private static array $state_map = [
        // KiZ 1
        'Formular1[0].Seite-1[0].Frage-1[0].ledig[0]' => ['single' => '1'],
        'Formular1[0].Seite-1[0].Frage-1[0].verheiratet[0]' => ['married' => '1'],
        'Formular1[0].Seite-1[0].Frage-1[0].geschieden[0]' => ['divorced' => '1'],
        'Formular1[0].Seite-1[0].Frage-1[0].getrennt-lebend[0]' => ['separated' => '1'],
        'Formular1[0].Seite-1[0].Frage-1[0].verwitwet[0]' => ['widowed' => '1'],

        // KiZ 1-AnA
        'topmostSubform[0].Page1[0].Frage-1[0].Ausbildung-Antragsteller[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Frage-1[0].stationäre-Unterbring-Antragsteller[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Frage-1[0].öffentl-Dienst-Antragsteller[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Frage-1[0].Ausbildung-Partner[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Frage-1[0].stationäre-Unterbring-Partner[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Frage-1[0].öffentl-Dienst-Partner[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Frage-2[0].Miete[0]' => ['rent' => '1'],
        'topmostSubform[0].Page1[0].Frage-2[0].Mietvertrag[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Frage-2[0].Kontoauszug[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Frage-2[0].Heizkosten-Miete[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Frage-2[0].Betriebskosten[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Frage-2[0].Wohngeldbescheid-Miete[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Frage-2[0].#area[3].Mietzuschuss[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Frage-2[0].Eigenheim[0]' => ['own' => '1'],
        'topmostSubform[0].Page1[0].Frage-2[0].Schuldzinsen[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Frage-2[0].Grundsteuer[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Frage-2[0].Heizkosten-Eigenheim[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Frage-2[0].Nebenkosten[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Frage-2[0].Wohngeldbescheid-Eigenheim[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Frage-2[0].WW-Erzeugung[0].zentral[0]' => ['central' => '1'],
        'topmostSubform[0].Page1[0].Frage-2[0].WW-Erzeugung[0].dezentral[0]' => ['decentral' => '1'],
        'topmostSubform[0].Page2[0].Frage-3[0].Vermögen-ja[0]' => ['yes' => '1'],
        'topmostSubform[0].Page2[0].Frage-3[0].Vermögen-nein[0]' => ['no' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Lohn[0].Lohn-A[0]' => ['payslips' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Lohn[0].Verdienstbesch-A[0]' => ['certificate' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Einkommen-Selbständ[0].KiZ5a-A[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Einkommen-Selbständ[0].selbst-a-Nachw-A[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-FWD[0].FWD-Nachw-A[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Alg-1-A[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Alg-2-A[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Krankengeld-A[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Rente-A[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Elterngeld-Antragst[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Bafög-A[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Leist-staatl-A[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Unterhalt[0].UH-A[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Mieteinnahmen[0].Mieteinnahmen-A[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-sonst-Einnahmen[0].sonst-Einn-A[0]' => ['1' => '1'],
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[4].FK-Öffis-A[0]' => ['1' => '1'],
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[7].doppelte-HH-A[0]' => ['1' => '1'],
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[8].Verpfleg-A[0]' => ['1' => '1'],
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[9].sonst-WK-A[0]' => ['1' => '1'],
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Versicherungen[0].Kfz-Versich-A[0]' => ['1' => '1'],
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Versicherungen[0].Riester-A[0]' => ['1' => '1'],
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Versicherungen[0].sonst-Versich-A[0]' => ['1' => '1'],
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Unterhaltszahlungen[0].Unterhaltszahl-A[0]' => ['1' => '1'],
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Kinderbetr-kosten[0].Betr-kosten-A[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Lohn[0].Lohn-P[0]' => ['payslips' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Lohn[0].Verdienstbesch-P[0]' => ['certificate' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Einkommen-Selbständ[0].KiZ5a-P[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Einkommen-Selbständ[0].selbst-a-Nachw-P[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-FWD[0].FWD-Nachw-P[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Alg-1-P[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Alg-2-P[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Krankengeld-P[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Rente-P[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Elterngeld-Partner[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Bafög-P[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-and-EK[0].Leist-staatl-P[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Unterhalt[0].UH-P[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-Mieteinnahmen[0].Mieteinnahmen-P[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Frage-5[0].Zeile-sonst-Einnahmen[0].sonst-Einn-P[0]' => ['1' => '1'],
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[4].FK-Öffis-P[0]' => ['1' => '1'],
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[7].doppelte-HH-P[0]' => ['1' => '1'],
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[8].Verpfleg-P[0]' => ['1' => '1'],
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Werbungskosten[0].#area[9].sonst-WK-P[0]' => ['1' => '1'],
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Versicherungen[0].Kfz-Versich-P[0]' => ['1' => '1'],
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Versicherungen[0].Riester-P[0]' => ['1' => '1'],
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Versicherungen[0].sonst-Versich-P[0]' => ['1' => '1'],
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Unterhaltszahlungen[0].Unterhaltszahl-P[0]' => ['1' => '1'],
        'topmostSubform[0].Page3[0].Frage-6[0].Zeile-Kinderbetr-kosten[0].Betr-kosten-P[0]' => ['1' => '1'],

        // KiZ 1-K
        'topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[3].BDG-ja[0]' => ['yes' => '1'],
        'topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[3].DDG-nein[0]' => ['no' => '1'],
        'topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[4].VM-ja[0]' => ['yes' => '1'],
        'topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[4].VM-nein[0]' => ['no' => '1'],
        'topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[5].EK-ja[0]' => ['yes' => '1'],
        'topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[5].EK-nein[0]' => ['no' => '1'],
        'topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[6].WK-ja[0]' => ['yes' => '1'],
        'topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[6].WK-nein[0]' => ['no' => '1'],
        'topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[7].KA-ja[0]' => ['yes' => '1'],
        'topmostSubform[0].Page2[0].Hinweiskasten[0].Kurzantrag-verwenden[0].#area[7].KA-nein[0]' => ['no' => '1'],

        // KiZ 6
        'topmostSubform[0].Page1[0].Punkt-1[0].Giro-Konto[0].Girokonto-ja[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Punkt-1[0].Spar-Konto[0].Sparkonto-ja[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Punkt-1[0].Bausparvertrag[0].Bauspar-ja[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Punkt-1[0].Aktien[0].Aktien-ja[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Punkt-1[0].Lebensversicherung[0].Lebensversich-ja[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Punkt-1[0].Bargelder[0].Bargeld-ja[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Punkt-1[0].Altersvorsorge[0].Riester-ja[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Punkt-1[0].Kfz[0].Auto-ja[0]' => ['1' => '1'],
        'topmostSubform[0].Page1[0].Punkt-1[0].Immobilien[0].Eigenheim-ja[0]' => ['1' => '1'],
        'topmostSubform[0].Page2[0].Punkt-2[0].Befreiung-RV[0].RV-befreit-ja[0]' => ['yes' => '1'],
        'topmostSubform[0].Page2[0].Punkt-2[0].Befreiung-RV[0].RV-befreit-nein[0]' => ['no' => '1'],
        'topmostSubform[0].Page2[0].Punkt-3[0].sonst-Vermögen[0].sonst-VM-ja[0]' => ['yes' => '1'],
        'topmostSubform[0].Page2[0].Punkt-3[0].sonst-Vermögen[0].sonst-VM-nein[0]' => ['no' => '1'],
        'topmostSubform[0].Page2[0].Punkt-4[0].Verschenkt[0].Schenkung-ja[0]' => ['yes' => '1'],
        'topmostSubform[0].Page2[0].Punkt-4[0].Verschenkt[0].Schenkung-nein[0]' => ['no' => '1'],
    ];

    /** Stored value => printed value (PdfValue:Field:set). The PDF field "Geschlecht" is free text. */
    private static array $pdf_values = [
        'gender' => [
            'female' => 'weiblich',
            'male' => 'männlich',
            'diverse' => 'divers',
            'unspecified' => 'ohne Angabe',
        ],
    ];

    /**
     * Documents of the package: CMS slot of PDFMultiFormPage, page range kept from the original, signature stamps
     * and white masks (flattened push buttons "Alle Eingaben löschen / Drucken / Speichern").
     * Positions in mm from the top-left corner of the given page of that document. A stamp with "when" is only
     * placed if the document part provides that flag (see generateFilledPDF()).
     * PDFMultiFormPage.SignaturePositionX/Y override x/y of the "main" stamp of "antrag" and "short".
     *
     * @var array<string, array<string, mixed>>
     */
    private static array $documents = [
        'antrag' => [
            'slot' => 'PDFFile',
            'pages' => '2-end',
            'stamps' => ['main' => ['page' => 3, 'x' => 110.0, 'y' => 205.0, 'width' => 32.0]],
            'masks' => ['buttons' => ['page' => 3, 'x' => 51.0, 'y' => 274.5, 'width' => 109.0, 'height' => 9.5]],
            // one character per box: x = left edge of the first box, cell = box width, y = baseline (mm)
            'combs' => [
                'IBAN' => ['page' => 2, 'x' => 16.89, 'y' => 196.2, 'cell' => 4.985, 'cells' => 34, 'size' => 9],
                'BIC' => ['page' => 2, 'x' => 16.89, 'y' => 206.2, 'cell' => 4.985, 'cells' => 11, 'size' => 9],
            ],
        ],
        'ana' => [
            'slot' => 'PDFFile2',
            'pages' => 'all',
            'stamps' => ['main' => ['page' => 3, 'x' => 110.0, 'y' => 162.0, 'width' => 32.0]],
            'masks' => ['buttons' => ['page' => 3, 'x' => 51.0, 'y' => 184.0, 'width' => 109.0, 'height' => 9.5]],
        ],
        'ank' => [
            'slot' => 'PDFFile3',
            'pages' => 'all',
            'stamps' => ['main' => ['page' => 2, 'x' => 106.0, 'y' => 258.0, 'width' => 32.0]],
            'masks' => ['buttons' => ['page' => 2, 'x' => 48.5, 'y' => 275.0, 'width' => 109.0, 'height' => 9.5]],
        ],
        'maintenance' => [
            'slot' => 'PDFFile4',
            'pages' => 'all',
            'stamps' => ['main' => ['page' => 2, 'x' => 110.0, 'y' => 243.0, 'width' => 32.0]],
            'masks' => ['buttons' => ['page' => 2, 'x' => 53.5, 'y' => 264.0, 'width' => 109.0, 'height' => 9.5]],
        ],
        'earnings' => [
            'slot' => 'PDFFile5',
            'pages' => 'all',
            'stamps' => [],
            'masks' => ['buttons' => ['page' => 2, 'x' => 50.5, 'y' => 274.0, 'width' => 109.0, 'height' => 9.5]],
        ],
        'selfemployed' => [
            'slot' => 'PDFFile6',
            'pages' => 'all',
            'stamps' => [
                'main' => ['page' => 2, 'x' => 110.0, 'y' => 89.5, 'width' => 22.0],
                'person' => ['page' => 2, 'x' => 110.0, 'y' => 101.0, 'width' => 22.0, 'when' => 'self'],
            ],
            'masks' => ['buttons' => ['page' => 2, 'x' => 50.0, 'y' => 274.0, 'width' => 111.0, 'height' => 9.5]],
        ],
        'assets' => [
            'slot' => 'PDFFile7',
            'pages' => 'all',
            'stamps' => ['main' => ['page' => 2, 'x' => 112.0, 'y' => 217.0, 'width' => 32.0]],
            'masks' => ['buttons' => ['page' => 2, 'x' => 52.5, 'y' => 244.0, 'width' => 109.0, 'height' => 9.5]],
        ],
        'nutrition' => [
            'slot' => 'PDFFile8',
            'pages' => 'all',
            'stamps' => [
                'main' => ['page' => 1, 'x' => 112.0, 'y' => 149.0, 'width' => 32.0],
                'patient' => ['page' => 1, 'x' => 120.0, 'y' => 225.0, 'width' => 32.0, 'when' => 'patient'],
            ],
            'masks' => ['buttons' => ['page' => 1, 'x' => 53.5, 'y' => 273.0, 'width' => 109.0, 'height' => 9.5]],
        ],
        'short' => [
            'slot' => 'PDFFile9',
            'pages' => '2-end',
            'stamps' => ['main' => ['page' => 2, 'x' => 112.0, 'y' => 221.0, 'width' => 32.0]],
            'masks' => ['buttons' => ['page' => 2, 'x' => 19.0, 'y' => 281.0, 'width' => 112.0, 'height' => 9.5]],
        ],
    ];

    /**
     * TTF used by pdftk for field appearances (ž, ć, đ … are dropped otherwise).
     * Relative to the module root or absolute; '' disables it. Liberation Sans: SIL OFL 1.1, see fonts/.
     */
    private static string $replacement_font = 'fonts/LiberationSans-Regular.ttf';

    /** Rows of KiZ 1 Punkt 5; further absent children are appended to the last row. */
    private const ABSENT_ROWS = 3;

    /**
     * Safety net: values of inactive (hidden) fields are never stored, whatever the steps delivered.
     */
    protected function onBeforeWrite(): void
    {
        parent::onBeforeWrite();
        FieldConditions::cleanRecord($this, (array) $this->config()->get('field_conditions'));
    }

    public function isShortApplication(): bool
    {
        return $this->ApplicationType === 'short';
    }

    /**
     * @return array<int, SubmissionChild>
     */
    public function childRecords(): array
    {
        $list = $this->Children();

        return $list instanceof DataList ? $list->sort('Number')->toArray() : [];
    }

    /**
     * @return array<int, SubmissionSelfEmployed>
     */
    public function selfEmployedRecords(): array
    {
        $records = [];
        $list = $this->SelfEmployedRecords();
        if ($list instanceof DataList) {
            foreach ($list as $record) {
                $records[(string) $record->Person] = $record;
            }
        }

        return array_values(array_filter([
            $records[SubmissionSelfEmployed::PERSON_APPLICANT] ?? null,
            $records[SubmissionSelfEmployed::PERSON_PARTNER] ?? null,
        ]));
    }

    public function applicationDate(): DateTimeImmutable
    {
        $created = (string) ($this->Created ?: 'now');

        return new DateTimeImmutable($created);
    }

    /**
     * Shared document values: "HeaderName" (Familienname, Vorname der kindergeldbeziehenden Person),
     * "KindergeldNumber", "Address", "Date" (application date), "PeriodFrom" / "PeriodTo" (Bemessungszeitraum MM/YYYY).
     */
    public function textFor(string $key): string
    {
        return match ($key) {
            'HeaderName' => $this->joinParts(', ', [$this->text('LastName'), $this->text('FirstName')]),
            'KindergeldNumber' => $this->text('KindergeldNumber'),
            'Address' => $this->joinParts(', ', [
                $this->text('Street'),
                $this->joinParts(' ', [$this->text('PostalCode'), $this->text('City')]),
            ]),
            'Date' => $this->applicationDate()->format('d.m.Y'),
            'PeriodFrom' => SubmissionSelfEmployed::periodMonths($this->applicationDate())[0],
            'PeriodTo' => SubmissionSelfEmployed::periodMonths($this->applicationDate())[5],
            default => '',
        };
    }

    /**
     * Person data for KiZ 5 / KiZ 5a / KiZ 7.
     *
     * @return array<string, string|bool>
     */
    public function personData(string $kind, ?SubmissionChild $child = null): array
    {
        $date = fn ($value): string => $value ? date('d.m.Y', (int) strtotime((string) $value)) : '';

        return match ($kind) {
            'partner' => [
                'name' => $this->joinParts(', ', [$this->text('PartnerLastName'), $this->text('PartnerFirstName')]),
                'birthDate' => $date($this->PartnerBirthDate),
                'training' => (string) $this->PartnerCertTraining,
                'trainingUntil' => $date($this->PartnerCertTrainingUntil),
                'signsHimself' => false,
            ],
            'child' => [
                'name' => $child ? $child->NameLine('LastName') : '',
                'birthDate' => $child ? $date($child->BirthDate) : '',
                'training' => $child ? (string) $child->CertTraining : '',
                'trainingUntil' => $child ? $date($child->CertTrainingUntil) : '',
                // the applicant signs as legal representative of a minor child
                'signsHimself' => $child !== null && $this->isMinor((string) $child->BirthDate),
            ],
            default => [
                'name' => $this->textFor('HeaderName'),
                'birthDate' => $date($this->BirthDate),
                'training' => (string) $this->ApplicantCertTraining,
                'trainingUntil' => $date($this->ApplicantCertTrainingUntil),
                'signsHimself' => true,
            ],
        };
    }

    /**
     * Values for the token mappings of KiZ 5 ("earnings") and KiZ 7 ("nutrition").
     *
     * @param array<string, string|bool> $person personData()
     * @return array<string, string>
     */
    public function personDocumentValues(array $person, bool $consent): array
    {
        $training = (string) ($person['training'] ?? '');

        return [
            'header' => $this->textFor('HeaderName'),
            'kg' => $this->textFor('KindergeldNumber'),
            'date' => $this->textFor('Date'),
            'periodFrom' => $this->textFor('PeriodFrom'),
            'periodTo' => $this->textFor('PeriodTo'),
            'address' => $this->textFor('Address'),
            'name' => (string) ($person['name'] ?? ''),
            'birthDate' => (string) ($person['birthDate'] ?? ''),
            'trainingYes' => $training === 'yes' ? '1' : '',
            'trainingNo' => $training === 'no' ? '1' : '',
            'trainingUntil' => $training === 'yes' ? (string) ($person['trainingUntil'] ?? '') : '',
            'claim' => '1',
            'consentDate' => $consent ? $this->textFor('Date') : '',
        ];
    }

    /**
     * @param array<string, string> $values personDocumentValues()
     * @return array<string, string>
     */
    public function tokenDocumentData(string $configKey, array $values): array
    {
        $data = [];
        foreach ((array) $this->config()->get($configKey) as $pdfField => $token) {
            $data[(string) $pdfField] = $values[(string) $token] ?? '';
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function documentData(string $configKey): array
    {
        return $configKey === 'field_mapping'
            ? $this->getArrayDataForPDF()
            : MappingResolver::resolve($this, (array) $this->config()->get($configKey));
    }

    // ---------------------------------------------------------------------------------------------
    // Mapping helpers (signature fixed by BaseSubmission::getArrayDataForPDF: method($field, $arg))
    // ---------------------------------------------------------------------------------------------

    public function StateValue(string $fieldName, string $pdfField): string
    {
        return FieldConditions::stateValue($this, $fieldName, (array) ($this->config()->get('state_map')[$pdfField] ?? []));
    }

    public function DocumentText(string $fieldName, string $key): string
    {
        return $this->textFor($key);
    }

    public function PdfValue(string $fieldName, string $set): string
    {
        $map = (array) ($this->config()->get('pdf_values')[$set] ?? []);

        return (string) ($map[(string) $this->getField($fieldName)] ?? '');
    }

    /** KiZ 1 Punkt 2 "Familienname, ggf. abweichender Geburtsname, Vorname". */
    public function PartnerNameLine(string $fieldName, string $unused = ''): string
    {
        $birthName = $this->text('PartnerBirthName');

        return $this->joinParts(', ', [
            $this->text('PartnerLastName'),
            $birthName !== '' ? 'geb. ' . $birthName : '',
            $this->text('PartnerFirstName'),
        ]);
    }


    /** KiZ 1 Punkt 4 row $row ("1".."6") + "-name" / "-birth". */
    public function ChildCell(string $fieldName, string $cell): string
    {
        [$row, $part] = array_pad(explode('-', $cell, 2), 2, '');
        $child = $this->childRecords()[(int) $row - 1] ?? null;
        if (!$child) {
            return '';
        }

        return $part === 'birth' ? (string) $child->DateFormating('BirthDate') : $child->NameLine('LastName');
    }

    /** KiZ 1 Punkt 5 row $row ("1".."3") + "-name" (Vorname) / "-reason" (Grund und Dauer). */
    public function AbsentCell(string $fieldName, string $cell): string
    {
        [$row, $part] = array_pad(explode('-', $cell, 2), 2, '');
        $row = (int) $row;
        $absent = array_values(array_filter($this->childRecords(), static fn (SubmissionChild $child): bool => (bool) $child->Absent));

        $rows = array_slice($absent, $row - 1, $row === self::ABSENT_ROWS ? null : 1);
        if ($rows === []) {
            return '';
        }

        if ($part === 'name') {
            return $this->joinParts(', ', array_map(fn (SubmissionChild $child): string => trim((string) $child->FirstName), $rows));
        }

        if (count($rows) === 1) {
            return trim((string) $rows[0]->AbsenceReason);
        }

        return $this->joinParts('; ', array_map(
            static fn (SubmissionChild $child): string => trim((string) $child->FirstName) . ': ' . trim((string) $child->AbsenceReason),
            $rows
        ));
    }

    /** KiZ 1-AnA Punkt 4 "Ja": any Mehrbedarf of the applicant or the partner. */
    public function AnyNeed(string $fieldName, string $unused = ''): string
    {
        foreach (['NeedPregnancy', 'NeedSingleParent', 'NeedNutrition', 'NeedOther', 'PartnerNeedPregnancy', 'PartnerNeedNutrition', 'PartnerNeedOther'] as $field) {
            if ($this->getField($field)) {
                return '1';
            }
        }

        return '';
    }

    /** KiZ 1-AnA Punkt 4 Entbindungstermin(e) of the applicant and the partner. */
    public function DueDates(string $fieldName, string $unused = ''): string
    {
        return $this->joinParts(', ', [
            (string) $this->DateFormating('DueDate'),
            (string) $this->DateFormating('PartnerDueDate'),
        ]);
    }

    // ---------------------------------------------------------------------------------------------
    // PDF generation
    // ---------------------------------------------------------------------------------------------

    public function generateFilledPDF(): Pdf
    {
        $package = new PdfPackage($this->replacementFontPath());

        if ($this->isShortApplication()) {
            $this->addDocument($package, 'short', $this->documentData('short_field_mapping'));
        } else {
            $this->addFullApplication($package);
        }

        $signature = trim((string) $this->Signature);
        $signaturePng = $signature !== '' ? $this->writeSignatureImage($signature) : null;

        try {
            $path = $package->build($signaturePng);
        } finally {
            if ($signaturePng !== null) {
                @unlink($signaturePng);
            }
        }

        return new Pdf($path);
    }

    private function addFullApplication(PdfPackage $package): void
    {
        $this->addDocument($package, 'antrag', $this->documentData('field_mapping'));
        $this->addDocument($package, 'ana', $this->documentData('ana_field_mapping'));

        $children = $this->childRecords();
        foreach ($children as $child) {
            $this->addDocument($package, 'ank', $child->getArrayDataForPDF());
            if ($child->needsMaintenanceAnnex()) {
                $this->addDocument($package, 'maintenance', $child->getMaintenanceDataForPDF());
            }
        }

        foreach ($this->selfEmployedRecords() as $record) {
            $flags = $record->Person === SubmissionSelfEmployed::PERSON_APPLICANT ? ['self'] : [];
            $this->addDocument($package, 'selfemployed', $record->getArrayDataForPDF(), $flags);
        }

        // KiZ 5: one Verdienstbescheinigung per employed person who chose the employer certificate
        $earners = [];
        if ($this->ApplicantWage && $this->ApplicantWageEvidence === 'certificate') {
            $earners[] = $this->personData('applicant');
        }
        if ($this->HasPartner === 'yes' && $this->PartnerWage && $this->PartnerWageEvidence === 'certificate') {
            $earners[] = $this->personData('partner');
        }
        foreach ($children as $child) {
            if ($child->Wage && $child->WageEvidence === 'certificate') {
                $earners[] = $this->personData('child', $child);
            }
        }
        foreach ($earners as $person) {
            $this->addDocument($package, 'earnings', $this->tokenDocumentData('earnings_field_mapping', $this->personDocumentValues($person, false)));
        }

        if ($this->HasAssets === 'yes') {
            $this->addDocument($package, 'assets', $this->documentData('assets_field_mapping'));
        }

        // KiZ 7: one per person with kostenaufwändiger Ernährung
        $patients = [];
        if ($this->NeedNutrition) {
            $patients[] = [$this->personData('applicant'), (bool) $this->NutritionConsent];
        }
        if ($this->HasPartner === 'yes' && $this->PartnerNeedNutrition) {
            $patients[] = [$this->personData('partner'), false];
        }
        foreach ($children as $child) {
            if ($child->NeedNutrition) {
                $patients[] = [$this->personData('child', $child), (bool) $child->NutritionConsent];
            }
        }
        foreach ($patients as [$person, $consent]) {
            // Punkt 2 is signed by the patient or the legal representative: stamped for the applicant and minor children
            $consent = $consent && (bool) $person['signsHimself'];
            $values = $this->personDocumentValues($person, $consent);
            $this->addDocument($package, 'nutrition', $this->tokenDocumentData('nutrition_field_mapping', $values), $consent ? ['patient'] : []);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $flags enable stamps with a matching "when"
     */
    private function addDocument(PdfPackage $package, string $key, array $data, array $flags = []): void
    {
        $document = (array) ($this->config()->get('documents')[$key] ?? []);
        if ($document === []) {
            throw new RuntimeException('Unknown Kinderzuschlag document: ' . $key);
        }

        $stamps = [];
        foreach ((array) ($document['stamps'] ?? []) as $name => $stamp) {
            if (!is_array($stamp) || (isset($stamp['when']) && !in_array($stamp['when'], $flags, true))) {
                continue;
            }
            if ($name === 'main' && in_array($key, ['antrag', 'short'], true)) {
                $stamp = $this->applyCmsSignaturePosition($stamp);
            }
            $stamps[] = $stamp;
        }

        $package->add(
            $this->templatePath((string) $document['slot']),
            $data,
            (string) ($document['pages'] ?? 'all'),
            $stamps,
            array_values(array_filter((array) ($document['masks'] ?? []), 'is_array')),
            $this->combTexts((array) ($document['combs'] ?? []))
        );
    }

    /**
     * Characters of comb fields (IBAN, BIC) as centred texts, one per box.
     *
     * @param array<string, array<string, float|int>> $combs source field => box geometry
     * @return array<int, array<string, mixed>>
     */
    private function combTexts(array $combs): array
    {
        $texts = [];

        foreach ($combs as $fieldName => $comb) {
            $value = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $this->getField((string) $fieldName)));
            $chars = str_split(substr($value, 0, (int) $comb['cells']));

            foreach ($chars as $index => $char) {
                if ($char === '') {
                    continue;
                }
                $texts[] = [
                    'page' => (int) $comb['page'],
                    'x' => (float) $comb['x'] + ((float) $comb['cell']) * ($index + 0.5),
                    'y' => (float) $comb['y'],
                    'text' => $char,
                    'size' => (float) ($comb['size'] ?? 9),
                ];
            }
        }

        return $texts;
    }

    /**
     * @param array<string, mixed> $stamp
     * @return array<string, mixed>
     */
    private function applyCmsSignaturePosition(array $stamp): array
    {
        $holder = $this->getMyHolderPage();
        $x = (float) ($holder?->SignaturePositionX ?: 0);
        $y = (float) ($holder?->SignaturePositionY ?: 0);

        if ($x > 0) {
            $stamp['x'] = $x;
        }
        if ($y > 0) {
            $stamp['y'] = $y;
        }

        return $stamp;
    }

    protected function templatePath(string $slot): string
    {
        $file = $this->FormSubmission()->getBasePDFLink($slot);
        if (!$file) {
            throw new RuntimeException('Kinderzuschlag: no PDF uploaded in CMS slot ' . $slot);
        }

        return getcwd() . '/assets/' . $file;
    }

    /** Config replacement_font: absolute path or relative to the module root; empty disables it. */
    private function replacementFontPath(): ?string
    {
        $font = trim((string) $this->config()->get('replacement_font'));
        if ($font === '') {
            return null;
        }

        $path = str_starts_with($font, '/') ? $font : dirname(__DIR__, 2) . '/' . $font;

        return is_file($path) ? $path : null;
    }

    /**
     * jSignature delivers a PNG data URI; base64 SVG is supported as fallback (needs Imagick).
     */
    private function writeSignatureImage(string $signature): string
    {
        $path = sys_get_temp_dir() . '/kinderzuschlag_sig_' . uniqid('', true) . '.png';

        if (preg_match('#^data:image/png;base64,(.+)$#i', $signature, $matches)) {
            $png = base64_decode($matches[1], true);
            if ($png === false || file_put_contents($path, $png) === false) {
                throw new RuntimeException('Invalid PNG signature payload.');
            }

            return $path;
        }

        $svgBase64 = preg_match('#^data:image/svg\+xml(?:;charset=[^;]+)?;base64,(.+)$#i', $signature, $matches)
            ? $matches[1]
            : $signature;
        $svg = base64_decode($svgBase64, true);
        if ($svg === false) {
            throw new RuntimeException('Invalid signature payload.');
        }

        $imagick = new \Imagick();
        $imagick->readImageBlob($svg);
        $imagick->setBackgroundColor(new \ImagickPixel('white'));
        $imagick = $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
        $imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
        $imagick->setImageFormat('png');
        $imagick->writeImage($path);
        $imagick->clear();

        return $path;
    }

    private function isMinor(string $birthDate): bool
    {
        if ($birthDate === '') {
            return false;
        }

        return (new DateTimeImmutable($birthDate))->modify('+18 years') > $this->applicationDate();
    }

    private function text(string $fieldName): string
    {
        return trim((string) preg_replace('/\s+/', ' ', (string) ($this->getField($fieldName) ?? '')));
    }

    /**
     * @param array<int, string> $parts
     */
    private function joinParts(string $glue, array $parts): string
    {
        return implode($glue, array_filter(array_map('trim', $parts), static fn (string $part): bool => $part !== ''));
    }
}
