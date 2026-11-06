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
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use UncleCheese\DisplayLogic\Forms\Wrapper;

/**
 * Child N, part 1: KiZ 1 Punkt 4/5 and KiZ 1-AnK Punkt 1/2 (+ KiZ 7 for the child).
 * Field names are "Child{N}<SubmissionChild field>" (FIELD_PREFIX of the using class); labels use __TRAIT__ keys
 * (one translation for all six children), Form::resolveFieldLabel() takes the preview labels from the field titles.
 *
 * Using classes define: CHILD, FIELD_PREFIX, RULE_MODEL, $default_sort_order, $conditional_required.
 */
trait ChildStepTrait
{
    public function getFields(): FieldList
    {
        $p = static::FIELD_PREFIX;
        $select = '-- ' . _t(__TRAIT__ . '.DEFAULT_SELECT', 'Bitte auswählen') . ' --';

        return FieldList::create(
            HeaderField::create('IntroText', _t(__TRAIT__ . '.IntroText', 'Kind {number}: Angaben zum Kind', ['number' => static::CHILD]), 4),
            TextField::create($p . 'LastName', _t(__TRAIT__ . '.LastName', 'Familienname'))
                ->setMaxLength(40),
            TextField::create($p . 'FirstName', _t(__TRAIT__ . '.FirstName', 'Vorname'))
                ->setMaxLength(25),
            DateField::create($p . 'BirthDate', _t(__TRAIT__ . '.BirthDate', 'Geburtsdatum')),
            TextField::create($p . 'Nationality', _t(__TRAIT__ . '.Nationality', 'Staatsangehörigkeit'))
                ->setMaxLength(30),
            DropdownField::create($p . 'ApplicantRelation', _t(__TRAIT__ . '.ApplicantRelation', 'Verwandtschaftsverhältnis zu Ihnen'))
                ->setSource([
                    'own' => _t(__TRAIT__ . '.ApplicantRelation.own', 'eigenes (leibliches oder adoptiertes) Kind'),
                    'step' => _t(__TRAIT__ . '.ApplicantRelation.step', 'Stiefkind'),
                ])
                ->setEmptyString($select),
            DropdownField::create($p . 'PartnerRelation', _t(__TRAIT__ . '.PartnerRelation', 'Verwandtschaftsverhältnis zu Ihrer Partnerin / Ihrem Partner (falls vorhanden)'))
                ->setSource([
                    'own' => _t(__TRAIT__ . '.PartnerRelation.own', 'eigenes (leibliches oder adoptiertes) Kind'),
                    'step' => _t(__TRAIT__ . '.PartnerRelation.step', 'Stiefkind'),
                ])
                ->setEmptyString('-- ' . _t(__TRAIT__ . '.PartnerRelationNone', 'kein(e) Partner(in) im Haushalt') . ' --'),
            CheckboxField::create($p . 'Absent', _t(__TRAIT__ . '.Absent', 'Das Kind hält sich nicht ständig in meinem Haushalt auf (z. B. Aufenthalt beim anderen Elternteil, im Internat oder Wohnheim)')),
            Wrapper::create(
                TextField::create($p . 'AbsenceReason', _t(__TRAIT__ . '.AbsenceReason', 'Grund und Dauer der Abwesenheit'))
                    ->setMaxLength(70)
                    ->setDescription(_t(__TRAIT__ . '.AbsenceReasonDescription', 'Beim anderen Elternteil genügt die pauschale Angabe, ob sich das Kind regelmäßig 1-12 oder 13-17 Tage dort aufhält.')),
            )
                ->displayIf($p . 'Absent')->isChecked()
                ->end(),
            HeaderField::create($p . 'SituationHeader', _t(__TRAIT__ . '.SituationHeader', 'Mein Kind … (Zutreffendes ankreuzen, bitte aktuelle Nachweise beifügen)'), 5),
            CheckboxField::create($p . 'Married', _t(__TRAIT__ . '.Married', 'ist verheiratet')),
            CheckboxField::create($p . 'HasOwnChild', _t(__TRAIT__ . '.HasOwnChild', 'hat ein eigenes Kind')),
            CheckboxField::create($p . 'LivesWithPartner', _t(__TRAIT__ . '.LivesWithPartner', 'lebt mit seiner Partnerin / seinem Partner zusammen')),
            CheckboxField::create($p . 'VocationalSchool', _t(__TRAIT__ . '.VocationalSchool', 'besucht eine berufsbildende Schule oder studiert')),
            CheckboxField::create($p . 'Boarding', _t(__TRAIT__ . '.Boarding', 'ist während der Ausbildung in einem Wohnheim, Internat, einer besonderen Einrichtung für Menschen mit Behinderung oder beim Ausbilder mit voller Verpflegung oder anderweitig mit Kostenerstattung für Unterkunft und Verpflegung untergebracht')),
            CheckboxField::create($p . 'Inpatient', _t(__TRAIT__ . '.Inpatient', 'befindet sich in einer stationären Einrichtung')),
            HeaderField::create($p . 'NeedHeader', _t(__TRAIT__ . '.NeedHeader', 'Mehrbedarf des Kindes (freiwillig, bitte nachweisen)'), 5),
            CheckboxField::create($p . 'NeedPregnancy', _t(__TRAIT__ . '.NeedPregnancy', 'Schwangerschaft')),
            Wrapper::create(
                DateField::create($p . 'DueDate', _t(__TRAIT__ . '.DueDate', 'Voraussichtlicher Entbindungstermin')),
            )
                ->displayIf($p . 'NeedPregnancy')->isChecked()
                ->end(),
            CheckboxField::create($p . 'NeedNutrition', _t(__TRAIT__ . '.NeedNutrition', 'Kostenaufwändige Ernährung (die „Anlage zum Mehrbedarf bei kostenaufwändiger Ernährung“ KiZ 7 wird beigefügt; Punkt 3 füllt die Ärztin / der Arzt aus)')),
            Wrapper::create(
                CheckboxField::create($p . 'NutritionConsent', _t(__TRAIT__ . '.NutritionConsent', 'Schweigepflichtentbindung: Für mein Kind entbinde ich die ausstellende Ärztin / den ausstellenden Arzt von der ärztlichen Schweigepflicht (freiwillig, nur für die bescheinigte Erkrankung). Ist das Kind volljährig, unterschreibt es selbst.')),
            )
                ->displayIf($p . 'NeedNutrition')->isChecked()
                ->end(),
            CheckboxField::create($p . 'NeedOther', _t(__TRAIT__ . '.NeedOther', 'Anderer Mehrbedarf (z. B. Schwerbehinderung mit Merkzeichen „G“ bei voller Erwerbsminderung, Leistungen zur Teilhabe am Arbeitsleben)')),
            LiteralField::create($p . 'NeedNotice', '<p class="small">' . _t(__TRAIT__ . '.NeedNotice', 'Ein Mehrbedarf erhöht den Kinderzuschlag nicht, kann aber dazu führen, dass weniger Einkommen angerechnet wird.') . '</p>'),
        );
    }

    public function getValidator(): RequiredFieldsValidator
    {
        $p = static::FIELD_PREFIX;

        return RequiredFieldsValidator::create([
            $p . 'LastName',
            $p . 'FirstName',
            $p . 'BirthDate',
            $p . 'Nationality',
            $p . 'ApplicantRelation',
        ]);
    }
}
