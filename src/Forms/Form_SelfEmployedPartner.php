<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use Kalakotra\EFormKinderzuschlag\Model\SubmissionSelfEmployed;
use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * KiZ 5a page 1-2 for the partner (see SelfEmployedStepTrait).
 */
class Form_SelfEmployedPartner extends MultiFormStep
{
    use StepTrait, SelfEmployedStepTrait {
        SelfEmployedStepTrait::validateStep insteadof StepTrait;
    }

    public const FIELD_PREFIX = 'SEPartner';

    public const RULE_MODEL = SubmissionSelfEmployed::class;

    private static $default_sort_order = 36;

    private static array $conditional_required = [
        'SEPartnerGrantType',
        'SEPartnerGrantPayer',
        'SEPartnerHomeArea',
        'SEPartnerOfficeRooms',
        'SEPartnerOfficeArea',
        'SEPartnerPrepayment1Date',
        'SEPartnerPrepayment1Amount',
    ];

    protected function introText(): string
    {
        return _t(__CLASS__ . '.IntroText', 'Einkommen aus selbständiger Tätigkeit: Partner(in)');
    }
}
