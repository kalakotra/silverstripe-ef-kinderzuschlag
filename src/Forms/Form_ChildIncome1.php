<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use Kalakotra\EFormKinderzuschlag\Model\SubmissionChild;
use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Child 1: KiZ 1-AnK Punkt 3/4 and KiZ 5c (see ChildIncomeStepTrait).
 */
class Form_ChildIncome1 extends MultiFormStep
{
    use StepTrait;
    use ChildIncomeStepTrait;

    public const CHILD = 1;

    public const FIELD_PREFIX = 'Child1';

    public const RULE_MODEL = SubmissionChild::class;

    private static $default_sort_order = 12;

    private static array $conditional_required = [
        'Child1WageEvidence',
        'Child1CertTraining',
        'Child1MNoneAddressSteps',
        'Child1MNonePaternitySteps',
        'Child1MNoneOtherText',
        'Child1MPartialOtherText',
        'Child1MLawyerDate',
        'Child1MYouthOfficePlace',
        'Child1MYouthOfficeDate',
        'Child1MCourtDate',
        'Child1MEnforcementDate',
        'Child1MOtherActionText',
        'Child1MUvgReason',
        'Child1MUvgReasonText',
    ];
}
