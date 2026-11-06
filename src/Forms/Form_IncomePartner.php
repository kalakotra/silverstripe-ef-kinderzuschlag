<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * KiZ 1-AnA Punkt 5/6, column "Partner(in)" (only with a partner) (see IncomeStepTrait).
 */
class Form_IncomePartner extends MultiFormStep
{
    use StepTrait;
    use IncomeStepTrait;

    public const PERSON_PREFIX = 'Partner';

    private static $default_sort_order = 33;

    private static array $conditional_required = [
        'PartnerWageEvidence',
        'PartnerCertTraining',
    ];

    protected function introText(): string
    {
        return _t(__CLASS__ . '.IntroText', 'Einnahmen und Ausgaben: Partner(in)');
    }
}
