<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use Kalakotra\EFormKinderzuschlag\Model\SubmissionChild;
use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Child 5: KiZ 1 Punkt 4/5, KiZ 1-AnK Punkt 1/2 (see ChildStepTrait).
 */
class Form_Child5 extends MultiFormStep
{
    use StepTrait;
    use ChildStepTrait;

    public const CHILD = 5;

    public const FIELD_PREFIX = 'Child5';

    public const RULE_MODEL = SubmissionChild::class;

    private static $default_sort_order = 19;

    private static array $conditional_required = [
        'Child5AbsenceReason',
        'Child5DueDate',
    ];
}
