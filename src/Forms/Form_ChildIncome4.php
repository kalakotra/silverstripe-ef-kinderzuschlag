<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use Kalakotra\EFormKinderzuschlag\Model\SubmissionChild;
use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Child 4: KiZ 1-AnK Punkt 3/4 and KiZ 5c (see ChildIncomeStepTrait).
 */
class Form_ChildIncome4 extends MultiFormStep
{
    use StepTrait;
    use ChildIncomeStepTrait;

    public const CHILD = 4;

    public const FIELD_PREFIX = 'Child4';

    public const RULE_MODEL = SubmissionChild::class;

    private static $default_sort_order = 18;

    private static array $conditional_required = [
        'Child4WageEvidence',
        'Child4CertTraining',
        'Child4MNoneAddressSteps',
        'Child4MNonePaternitySteps',
        'Child4MNoneOtherText',
        'Child4MPartialOtherText',
        'Child4MLawyerDate',
        'Child4MYouthOfficePlace',
        'Child4MYouthOfficeDate',
        'Child4MCourtDate',
        'Child4MEnforcementDate',
        'Child4MOtherActionText',
        'Child4MUvgReason',
        'Child4MUvgReasonText',
    ];
}
