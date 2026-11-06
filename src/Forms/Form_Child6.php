<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use Kalakotra\EFormKinderzuschlag\Model\SubmissionChild;
use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Child 6: KiZ 1 Punkt 4/5, KiZ 1-AnK Punkt 1/2 (see ChildStepTrait).
 */
class Form_Child6 extends MultiFormStep
{
    use StepTrait;
    use ChildStepTrait;

    public const CHILD = 6;

    public const FIELD_PREFIX = 'Child6';

    public const RULE_MODEL = SubmissionChild::class;

    private static $default_sort_order = 21;

    private static array $conditional_required = [
        'Child6AbsenceReason',
        'Child6DueDate',
    ];
}
