<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use Kalakotra\EFormKinderzuschlag\Model\SubmissionChild;
use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Child 2: KiZ 1-AnK Punkt 3/4 and KiZ 5c (see ChildIncomeStepTrait).
 */
class Form_ChildIncome2 extends MultiFormStep
{
    use StepTrait;
    use ChildIncomeStepTrait;

    public const CHILD = 2;

    public const FIELD_PREFIX = 'Child2';

    public const RULE_MODEL = SubmissionChild::class;

    private static $default_sort_order = 14;

    private static array $conditional_required = [
        'Child2WageEvidence',
        'Child2CertTraining',
        'Child2MNoneAddressSteps',
        'Child2MNonePaternitySteps',
        'Child2MNoneOtherText',
        'Child2MPartialOtherText',
        'Child2MLawyerDate',
        'Child2MYouthOfficePlace',
        'Child2MYouthOfficeDate',
        'Child2MCourtDate',
        'Child2MEnforcementDate',
        'Child2MOtherActionText',
        'Child2MUvgReason',
        'Child2MUvgReasonText',
    ];
}
