<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use Kalakotra\EFormKinderzuschlag\Model\SubmissionChild;
use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Child 5: KiZ 1-AnK Punkt 3/4 and KiZ 5c (see ChildIncomeStepTrait).
 */
class Form_ChildIncome5 extends MultiFormStep
{
    use StepTrait;
    use ChildIncomeStepTrait;

    public const CHILD = 5;

    public const FIELD_PREFIX = 'Child5';

    public const RULE_MODEL = SubmissionChild::class;

    private static $default_sort_order = 20;

    private static array $conditional_required = [
        'Child5WageEvidence',
        'Child5CertTraining',
        'Child5MNoneAddressSteps',
        'Child5MNonePaternitySteps',
        'Child5MNoneOtherText',
        'Child5MPartialOtherText',
        'Child5MLawyerDate',
        'Child5MYouthOfficePlace',
        'Child5MYouthOfficeDate',
        'Child5MCourtDate',
        'Child5MEnforcementDate',
        'Child5MOtherActionText',
        'Child5MUvgReason',
        'Child5MUvgReasonText',
    ];
}
