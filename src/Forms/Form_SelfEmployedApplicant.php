<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use Kalakotra\EFormKinderzuschlag\Model\SubmissionSelfEmployed;
use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * KiZ 5a page 1-2 for the applicant (see SelfEmployedStepTrait).
 */
class Form_SelfEmployedApplicant extends MultiFormStep
{
    use StepTrait, SelfEmployedStepTrait {
        SelfEmployedStepTrait::validateStep insteadof StepTrait;
    }

    public const FIELD_PREFIX = 'SEApplicant';

    public const RULE_MODEL = SubmissionSelfEmployed::class;

    private static $default_sort_order = 34;

    private static array $conditional_required = [
        'SEApplicantGrantType',
        'SEApplicantGrantPayer',
        'SEApplicantHomeArea',
        'SEApplicantOfficeRooms',
        'SEApplicantOfficeArea',
        'SEApplicantPrepayment1Date',
        'SEApplicantPrepayment1Amount',
    ];

    protected function introText(): string
    {
        return _t(__CLASS__ . '.IntroText', 'Einkommen aus selbständiger Tätigkeit: Antragsteller(in)');
    }
}
