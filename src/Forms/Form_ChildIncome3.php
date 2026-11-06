<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use Kalakotra\EFormKinderzuschlag\Model\SubmissionChild;
use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Child 3: KiZ 1-AnK Punkt 3/4 and KiZ 5c (see ChildIncomeStepTrait).
 */
class Form_ChildIncome3 extends MultiFormStep
{
    use StepTrait;
    use ChildIncomeStepTrait;

    public const CHILD = 3;

    public const FIELD_PREFIX = 'Child3';

    public const RULE_MODEL = SubmissionChild::class;

    private static $default_sort_order = 16;

    private static array $conditional_required = [
        'Child3WageEvidence',
        'Child3CertTraining',
        'Child3MNoneAddressSteps',
        'Child3MNonePaternitySteps',
        'Child3MNoneOtherText',
        'Child3MPartialOtherText',
        'Child3MLawyerDate',
        'Child3MYouthOfficePlace',
        'Child3MYouthOfficeDate',
        'Child3MCourtDate',
        'Child3MEnforcementDate',
        'Child3MOtherActionText',
        'Child3MUvgReason',
        'Child3MUvgReasonText',
    ];
}
