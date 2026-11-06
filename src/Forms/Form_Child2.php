<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use Kalakotra\EFormKinderzuschlag\Model\SubmissionChild;
use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * Child 2: KiZ 1 Punkt 4/5, KiZ 1-AnK Punkt 1/2 (see ChildStepTrait).
 */
class Form_Child2 extends MultiFormStep
{
    use StepTrait;
    use ChildStepTrait;

    public const CHILD = 2;

    public const FIELD_PREFIX = 'Child2';

    public const RULE_MODEL = SubmissionChild::class;

    private static $default_sort_order = 13;

    private static array $conditional_required = [
        'Child2AbsenceReason',
        'Child2DueDate',
    ];
}
