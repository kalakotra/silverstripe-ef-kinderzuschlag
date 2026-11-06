<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * KiZ 1 Punkt 3 (Kontoverbindung) and the number of children for Punkt 4 (one "Anlage Kind" each).
 */
class Form_Bank extends MultiFormStep
{
    use StepTrait;

    private static $default_sort_order = 4;

    public const MAX_CHILDREN = 6;

    public function getFields(): FieldList
    {
        return FieldList::create(
            HeaderField::create('IntroText', _t(__CLASS__ . '.IntroText', 'Kontoverbindung und Kinder'), 4),
            TextField::create('IBAN', _t(__CLASS__ . '.IBAN', 'IBAN'))
                ->setMaxLength(42)
                ->setDescription(_t(__CLASS__ . '.IBANDescription', 'Das Konto, auf das Kindergeld und Kinderzuschlag überwiesen werden sollen.')),
            TextField::create('BIC', _t(__CLASS__ . '.BIC', 'BIC (optional)'))
                ->setMaxLength(11),
            TextField::create('AccountHolder', _t(__CLASS__ . '.AccountHolder', 'Kontoinhaber(in)'))
                ->setMaxLength(60),
            LiteralField::create('ChildrenNotice', '<div class="alert alert-info">' . _t(__CLASS__ . '.ChildrenNotice', 'Kinderzuschlag gibt es für Ihre eigenen (leiblichen oder adoptierten) Kinder und die Ihrer Partnerin / Ihres Partners, die unter 25 Jahre alt und unverheiratet sind, in Ihrem Haushalt leben und für die Sie oder Ihr(e) Partner(in) Kindergeld beziehen. Für jedes dieser Kinder wird eine „Anlage Kind“ erstellt.') . '</div>'),
            DropdownField::create('ChildCount', _t(__CLASS__ . '.ChildCount', 'Für wie viele Kinder beantragen Sie Kinderzuschlag?'))
                ->setSource([
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ])
                ->setEmptyString('-- ' . _t(__CLASS__ . '.DEFAULT_SELECT', 'Bitte auswählen') . ' --'),
        );
    }

    public function getValidator(): RequiredFieldsValidator
    {
        return RequiredFieldsValidator::create([
            'IBAN',
            'AccountHolder',
            'ChildCount',
        ]);
    }

    public function validateStep($data, $form)
    {
        $iban = (string) ($form->getData()['IBAN'] ?? '');

        if ($iban !== '' && !self::isValidIban($iban)) {
            $message = _t(__CLASS__ . '.IBANInvalid', 'Bitte prüfen Sie die IBAN.');
            $form->sessionFieldError($message, 'IBAN');
            $form->sessionMessage($message);

            return false;
        }

        return $this->validateConditionalRequired($data, $form) && parent::validateStep($data, $form);
    }

    /** ISO 13616 check (length per format + mod 97). */
    public static function isValidIban(string $iban): bool
    {
        $iban = strtoupper((string) preg_replace('/\s+/', '', $iban));
        if (!preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/', $iban)) {
            return false;
        }

        $rearranged = substr($iban, 4) . substr($iban, 0, 4);
        $numeric = '';
        foreach (str_split($rearranged) as $char) {
            $numeric .= ctype_alpha($char) ? (string) (ord($char) - 55) : $char;
        }

        $remainder = 0;
        foreach (str_split($numeric, 7) as $chunk) {
            $remainder = (int) (($remainder . $chunk) % 97);
        }

        return $remainder === 1;
    }
}
