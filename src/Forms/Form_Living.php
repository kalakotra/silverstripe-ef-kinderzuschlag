<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use SilverStripe\MultiForm\Models\MultiFormStep;
use UncleCheese\DisplayLogic\Forms\Wrapper;

/**
 * KiZ 1-AnA Punkt 1 (column "Ich"), Punkt 2 (Wohnen), Punkt 3 (Vermögen -> KiZ 6) and Punkt 4 (own Mehrbedarfe -> KiZ 7).
 */
class Form_Living extends MultiFormStep
{
    use StepTrait;

    private static $default_sort_order = 31;

    private static array $conditional_required = [
        'DueDate',
    ];

    public function getFields(): FieldList
    {
        $select = '-- ' . _t(__CLASS__ . '.DEFAULT_SELECT', 'Bitte auswählen') . ' --';

        return FieldList::create(
            HeaderField::create('IntroText', _t(__CLASS__ . '.IntroText', 'Wohnen, Vermögen und Mehrbedarfe'), 4),
            HeaderField::create('SituationHeader', _t(__CLASS__ . '.SituationHeader', 'Ich … (Zutreffendes ankreuzen und nachweisen)'), 5),
            CheckboxField::create('InTraining', _t(__CLASS__ . '.InTraining', 'befinde mich derzeit in einer Schul- oder Berufsausbildung oder in einem Studium')),
            CheckboxField::create('Inpatient', _t(__CLASS__ . '.Inpatient', 'befinde mich derzeit in einer stationären Einrichtung')),
            CheckboxField::create('ForeignBenefit', _t(__CLASS__ . '.ForeignBenefit', 'erhalte für mein(e) Kind(er) anstatt Kindergeld eine Geldleistung von einer Stelle außerhalb von Deutschland oder habe diese beantragt')),
            HeaderField::create('HousingHeader', _t(__CLASS__ . '.HousingHeader', 'Wohnen'), 5),
            DropdownField::create('Housing', _t(__CLASS__ . '.Housing', 'Wir wohnen'))
                ->setSource([
                    'rent' => _t(__CLASS__ . '.Housing.rent', 'zur Miete'),
                    'own' => _t(__CLASS__ . '.Housing.own', 'in einem Eigenheim / einer Eigentumswohnung'),
                ])
                ->setEmptyString($select),
            Wrapper::create(
                LiteralField::create('RentNotice', '<p>' . _t(__CLASS__ . '.RentNotice', 'Folgende Nachweise für den Antragsmonat füge ich bei:') . '</p>'),
                CheckboxField::create('RentContract', _t(__CLASS__ . '.RentContract', 'Mietvertrag bzw. aktuelle Mietbescheinigung bei Abweichungen zum Mietvertrag')),
                CheckboxField::create('RentBankStatement', _t(__CLASS__ . '.RentBankStatement', 'Kontoauszug, wenn Mietvertrag / Mietbescheinigung älter als ein Jahr ist')),
                CheckboxField::create('RentHeating', _t(__CLASS__ . '.RentHeating', 'Belege über monatliche Heizkosten, sofern nicht aus Mietvertrag / Mietbescheinigung ersichtlich')),
                CheckboxField::create('RentUtilities', _t(__CLASS__ . '.RentUtilities', 'Belege über monatliche Nebenkosten, sofern nicht aus Mietvertrag / Mietbescheinigung ersichtlich')),
                CheckboxField::create('RentHousingBenefit', _t(__CLASS__ . '.RentHousingBenefit', 'Wohngeldbescheid, wenn beantragt (Bewilligung oder Ablehnung, alle Seiten)')),
                CheckboxField::create('RentSubsidy', _t(__CLASS__ . '.RentSubsidy', 'Bescheid über kommunalen Mietzuschuss / Mietzulage, wenn beantragt')),
            )
                ->displayIf('Housing')->isEqualTo('rent')
                ->end(),
            Wrapper::create(
                LiteralField::create('OwnNotice', '<p>' . _t(__CLASS__ . '.OwnNotice', 'Folgende Nachweise für das letzte Kalenderjahr füge ich bei:') . '</p>'),
                CheckboxField::create('OwnInterest', _t(__CLASS__ . '.OwnInterest', 'Nachweis über aktuelle Höhe der Schuldzinsen (ohne Tilgungsraten)')),
                CheckboxField::create('OwnTaxInsurance', _t(__CLASS__ . '.OwnTaxInsurance', 'Nachweis über Grundsteuer und Gebäudeversicherung')),
                CheckboxField::create('OwnHeating', _t(__CLASS__ . '.OwnHeating', 'Belege über Heizkosten')),
                CheckboxField::create('OwnUtilities', _t(__CLASS__ . '.OwnUtilities', 'Belege über Nebenkosten (Wasser, Abwasser, Müllgebühren, Schornsteinfeger, Straßenreinigung; Rechnungsbeträge und Abschläge)')),
                CheckboxField::create('OwnHousingBenefit', _t(__CLASS__ . '.OwnHousingBenefit', 'Wohngeldbescheid, wenn beantragt (Lastenzuschuss, Bewilligung oder Ablehnung; alle Seiten)')),
                DateField::create('MoveInDate', _t(__CLASS__ . '.MoveInDate', 'Einzugsdatum (nur wenn Sie erst im Laufe dieses oder des letzten Jahres eingezogen sind)')),
            )
                ->displayIf('Housing')->isEqualTo('own')
                ->end(),
            DropdownField::create('HotWater', _t(__CLASS__ . '.HotWater', 'Das Warmwasser wird erzeugt'))
                ->setSource([
                    'central' => _t(__CLASS__ . '.HotWater.central', 'zentral (z. B. mit der zentralen Heizungsanlage)'),
                    'decentral' => _t(__CLASS__ . '.HotWater.decentral', 'dezentral (z. B. mit einem Boiler oder Durchlauferhitzer)'),
                ])
                ->setEmptyString($select),
            HeaderField::create('AssetsHeader', _t(__CLASS__ . '.AssetsHeader', 'Vermögen (Selbstauskunft)'), 5),
            LiteralField::create('AssetsNotice', '<div class="alert alert-info">' . _t(__CLASS__ . '.AssetsNotice', 'Erhebliches Vermögen liegt vor ab 55.000 Euro bei 2 Personen, 70.000 Euro bei 3 Personen, für jede weitere Person 15.000 Euro mehr (gesamte Bedarfsgemeinschaft). Zum Vermögen gehören u. a. nicht selbst genutzte Immobilien, ein Auto oder Motorrad über 15.000 Euro Verkaufswert, Bargeld, Konten (auch Tagesgeld, Kreditkarten, PayPal, Kryptowährung), Sparguthaben, Wertpapiere, Bausparverträge, Kapitallebens- und private Rentenversicherungen sowie Edelmetalle, Antiquitäten, Gemälde und Schmuck.') . '</div>'),
            DropdownField::create('HasAssets', _t(__CLASS__ . '.HasAssets', 'Haben Sie, Ihr(e) Partner(in) und Ihre im Haushalt lebenden Kinder gemeinsam ein erhebliches Vermögen?'))
                ->setSource([
                    'no' => _t(__CLASS__ . '.HasAssets.no', 'Nein'),
                    'yes' => _t(__CLASS__ . '.HasAssets.yes', 'Ja (die „Anlage zum erheblichen Vermögen“ KiZ 6 wird im nächsten Schritt ausgefüllt)'),
                ])
                ->setEmptyString($select),
            HeaderField::create('NeedHeader', _t(__CLASS__ . '.NeedHeader', 'Ihre Mehrbedarfe (freiwillig, bitte nachweisen)'), 5),
            CheckboxField::create('NeedPregnancy', _t(__CLASS__ . '.NeedPregnancy', 'Schwangerschaft')),
            Wrapper::create(
                DateField::create('DueDate', _t(__CLASS__ . '.DueDate', 'Voraussichtlicher Entbindungstermin')),
            )
                ->displayIf('NeedPregnancy')->isChecked()
                ->end(),
            CheckboxField::create('NeedSingleParent', _t(__CLASS__ . '.NeedSingleParent', 'Alleinerziehung (kein Nachweis erforderlich)')),
            CheckboxField::create('NeedNutrition', _t(__CLASS__ . '.NeedNutrition', 'Kostenaufwändige Ernährung (die „Anlage zum Mehrbedarf bei kostenaufwändiger Ernährung“ KiZ 7 wird beigefügt; Punkt 3 füllt die Ärztin / der Arzt aus)')),
            Wrapper::create(
                CheckboxField::create('NutritionConsent', _t(__CLASS__ . '.NutritionConsent', 'Schweigepflichtentbindung: Ich entbinde die ausstellende Ärztin / den ausstellenden Arzt von der ärztlichen Schweigepflicht (freiwillig, nur für die bescheinigte Erkrankung).')),
            )
                ->displayIf('NeedNutrition')->isChecked()
                ->end(),
            CheckboxField::create('NeedOther', _t(__CLASS__ . '.NeedOther', 'Anderer Mehrbedarf (z. B. Schwerbehinderung mit Merkzeichen „G“ bei voller Erwerbsminderung, Leistungen zur Teilhabe am Arbeitsleben)')),
        );
    }

    public function getValidator(): RequiredFieldsValidator
    {
        return RequiredFieldsValidator::create([
            'Housing',
            'HotWater',
            'HasAssets',
        ]);
    }
}
