<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use SilverStripe\MultiForm\Models\MultiFormStep;
use UncleCheese\DisplayLogic\Forms\Wrapper;

/**
 * KiZ 6 Anlage zum erheblichen Vermögen (only when Form_Living.HasAssets = yes).
 */
class Form_Assets extends MultiFormStep
{
    use StepTrait;

    private static $default_sort_order = 38;

    private static array $conditional_required = [
        'AssetCashDetails',
        'PensionPerson1Name',
        'PensionPerson1Years',
        'OtherAsset1Name',
        'OtherAsset1Value',
        'GiftRecipient',
        'GiftValue',
    ];

    public function getFields(): FieldList
    {
        $select = '-- ' . _t(__CLASS__ . '.DEFAULT_SELECT', 'Bitte auswählen') . ' --';
        $noYes = [
            'no' => _t(__CLASS__ . '.Answer.no', 'Nein'),
            'yes' => _t(__CLASS__ . '.Answer.yes', 'Ja'),
        ];

        return FieldList::create(
            HeaderField::create('IntroText', _t(__CLASS__ . '.IntroText', 'Anlage zum erheblichen Vermögen'), 4),
            LiteralField::create('AssetsNotice', '<div class="alert alert-info">' . _t(__CLASS__ . '.AssetsNotice', 'Die Angaben beziehen sich auf alle Personen der Bedarfsgemeinschaft (Sie, Ihr(e) Partner(in) und Ihre Kinder), im In- und Ausland. Maßgebend ist der aktuelle Wert bei Antragstellung. Auf jedem Nachweis muss erkennbar sein, wer Inhaber(in), Eigentümer(in) oder Versicherungsnehmer(in) ist; nicht benötigte Angaben dürfen geschwärzt werden.') . '</div>'),
            HeaderField::create('EvidenceHeader', _t(__CLASS__ . '.EvidenceHeader', 'Vorhandene Vermögenswerte – als Nachweis füge ich bei:'), 5),
            CheckboxField::create('AssetGiro', _t(__CLASS__ . '.AssetGiro', 'Girokonten und weitere Konten (z. B. Tagesgeld, Kreditkarten, PayPal): Kontoauszug mit aktuellem Kontostand')),
            CheckboxField::create('AssetSavings', _t(__CLASS__ . '.AssetSavings', 'Sparkonten (z. B. Sparbuch, Festgeld, Sparbrief): Kontoauszug mit aktuellem Kontostand und Zinsen im letzten Jahr')),
            CheckboxField::create('AssetBuildingSavings', _t(__CLASS__ . '.AssetBuildingSavings', 'Bausparverträge: Kontoauszug mit aktuellem Guthaben')),
            CheckboxField::create('AssetSecurities', _t(__CLASS__ . '.AssetSecurities', 'Wertpapiere (z. B. Aktien, Anleihen, Fonds-Anteile): Nachweis über derzeitigen Wert, Erträge im letzten Jahr, Beleihungs- und Gutschriftzinssatz sowie Gebühr bei vorzeitiger Auflösung')),
            CheckboxField::create('AssetInsurance', _t(__CLASS__ . '.AssetInsurance', 'Versicherungen mit Prämienrückgewähr (z. B. Kapitallebensversicherungen, private Rentenversicherungen): Bescheinigung über den Auszahlungsbetrag bei Rückkauf')),
            CheckboxField::create('AssetCash', _t(__CLASS__ . '.AssetCash', 'Bargeld')),
            Wrapper::create(
                TextField::create('AssetCashDetails', _t(__CLASS__ . '.AssetCashDetails', 'Eigentümer(in) und Gesamtbetrag in Euro'))
                    ->setMaxLength(50),
            )
                ->displayIf('AssetCash')->isChecked()
                ->end(),
            CheckboxField::create('AssetRiester', _t(__CLASS__ . '.AssetRiester', 'Steuerlich gefördertes Altersvorsorgevermögen (z. B. Riester-Rente): jährliche Bescheinigung des Anbieters nach § 92 EStG')),
            CheckboxField::create('AssetVehicle', _t(__CLASS__ . '.AssetVehicle', 'Kraftfahrzeuge über 15.000 Euro Verkaufswert: Fahrzeugschein, geschätzter Wert, ggf. bestehender Restkredit')),
            CheckboxField::create('AssetRealEstate', _t(__CLASS__ . '.AssetRealEstate', 'Immobilien, die nicht selbst genutzt werden: Nachweis über den aktuellen Verkehrswert und ggf. Belastungen (z. B. Hypotheken)')),
            HeaderField::create('PensionHeader', _t(__CLASS__ . '.PensionHeader', 'Private Altersvorsorge wegen hauptberuflich selbständiger Tätigkeit'), 5),
            DropdownField::create('SelfEmployedPension', _t(__CLASS__ . '.SelfEmployedPension', 'Wird oder wurde von Ihnen oder einer weiteren Person Ihrer Bedarfsgemeinschaft hauptberuflich eine selbständige Tätigkeit ausgeübt, während der keine Beiträge an die gesetzliche Rentenversicherung, eine öffentlich-rechtliche Versicherungseinrichtung oder eine Versorgungseinrichtung einer Berufsgruppe entrichtet werden oder wurden?'))
                ->setSource($noYes)
                ->setEmptyString($select),
            Wrapper::create(
                TextField::create('PensionPerson1Name', _t(__CLASS__ . '.PensionPerson1Name', 'Person 1: Name'))->setMaxLength(50),
                TextField::create('PensionPerson1Years', _t(__CLASS__ . '.PensionPerson1Years', 'Person 1: Anzahl der Jahre der Selbständigkeit'))->setMaxLength(10),
                TextField::create('PensionPerson2Name', _t(__CLASS__ . '.PensionPerson2Name', 'Person 2: Name (optional)'))->setMaxLength(50),
                TextField::create('PensionPerson2Years', _t(__CLASS__ . '.PensionPerson2Years', 'Person 2: Anzahl der Jahre der Selbständigkeit (optional)'))->setMaxLength(10),
                TextField::create('PensionPerson3Name', _t(__CLASS__ . '.PensionPerson3Name', 'Person 3: Name (optional)'))->setMaxLength(50),
                TextField::create('PensionPerson3Years', _t(__CLASS__ . '.PensionPerson3Years', 'Person 3: Anzahl der Jahre der Selbständigkeit (optional)'))->setMaxLength(10),
                TextareaField::create('PensionAssets', _t(__CLASS__ . '.PensionAssets', 'Welche der oben genannten Geldanlagen dienen der Alterssicherung? (bei mehreren Selbständigen bitte der Person zuordnen)'))
                    ->setRows(3),
            )
                ->displayIf('SelfEmployedPension')->isEqualTo('yes')
                ->end(),
            HeaderField::create('OtherAssetsHeader', _t(__CLASS__ . '.OtherAssetsHeader', 'Sonstiges Vermögen (z. B. Antiquitäten, Gemälde, Edelmetalle, Schmuck)'), 5),
            DropdownField::create('OtherAssets', _t(__CLASS__ . '.OtherAssets', 'Verfügen Sie oder im Haushalt lebende Angehörige über sonstiges Vermögen?'))
                ->setSource($noYes)
                ->setEmptyString($select),
            Wrapper::create(
                TextField::create('OtherAsset1Name', _t(__CLASS__ . '.OtherAsset1Name', 'Bezeichnung 1'))->setMaxLength(50),
                TextField::create('OtherAsset1Value', _t(__CLASS__ . '.OtherAsset1Value', 'Wert 1 in Euro'))->setMaxLength(20),
                TextField::create('OtherAsset2Name', _t(__CLASS__ . '.OtherAsset2Name', 'Bezeichnung 2 (optional)'))->setMaxLength(50),
                TextField::create('OtherAsset2Value', _t(__CLASS__ . '.OtherAsset2Value', 'Wert 2 in Euro (optional)'))->setMaxLength(20),
                TextField::create('OtherAsset3Name', _t(__CLASS__ . '.OtherAsset3Name', 'Bezeichnung 3 (optional)'))->setMaxLength(50),
                TextField::create('OtherAsset3Value', _t(__CLASS__ . '.OtherAsset3Value', 'Wert 3 in Euro (optional)'))->setMaxLength(20),
            )
                ->displayIf('OtherAssets')->isEqualTo('yes')
                ->end(),
            HeaderField::create('GiftHeader', _t(__CLASS__ . '.GiftHeader', 'Schenkung oder Spende'), 5),
            DropdownField::create('Gift', _t(__CLASS__ . '.Gift', 'Wurde Vermögen im In- oder Ausland verschenkt, gespendet oder auf eine andere Person übertragen?'))
                ->setSource($noYes)
                ->setEmptyString($select),
            Wrapper::create(
                TextField::create('GiftRecipient', _t(__CLASS__ . '.GiftRecipient', 'Empfänger(in)'))->setMaxLength(50),
                TextField::create('GiftValue', _t(__CLASS__ . '.GiftValue', 'Vermögenswert in Euro'))->setMaxLength(30),
                TextField::create('GiftPurpose', _t(__CLASS__ . '.GiftPurpose', 'Verwendungszweck (optional)'))->setMaxLength(50),
                TextField::create('GiftDate', _t(__CLASS__ . '.GiftDate', 'Zeitpunkt (optional)'))->setMaxLength(30),
            )
                ->displayIf('Gift')->isEqualTo('yes')
                ->end(),
        );
    }

    public function getValidator(): RequiredFieldsValidator
    {
        return RequiredFieldsValidator::create([
            'SelfEmployedPension',
            'OtherAssets',
            'Gift',
        ]);
    }
}
