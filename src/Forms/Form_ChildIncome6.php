<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use Kalakotra\EFormKinderzuschlag\Model\SubmissionChild;
use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Child 6: KiZ 1-AnK Punkt 3/4 and KiZ 5c (see ChildIncomeStepTrait).
 */
class Form_ChildIncome6 extends MultiFormStep
{
    use StepTrait;
    use ChildIncomeStepTrait;

    public const CHILD = 6;

    public const FIELD_PREFIX = 'Child6';

    public const RULE_MODEL = SubmissionChild::class;

    private static $default_sort_order = 22;

    private static array $conditional_required = [
        'Child6WageEvidence',
        'Child6CertTraining',
        'Child6MNoneAddressSteps',
        'Child6MNonePaternitySteps',
        'Child6MNoneOtherText',
        'Child6MPartialOtherText',
        'Child6MLawyerDate',
        'Child6MYouthOfficePlace',
        'Child6MYouthOfficeDate',
        'Child6MCourtDate',
        'Child6MEnforcementDate',
        'Child6MOtherActionText',
        'Child6MUvgReason',
        'Child6MUvgReasonText',
    ];
}
