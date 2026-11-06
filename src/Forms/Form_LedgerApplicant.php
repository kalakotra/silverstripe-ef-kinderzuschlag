<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use Kalakotra\EFormKinderzuschlag\Model\SubmissionSelfEmployed;
use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * KiZ 5a Beiblatt for the applicant (see LedgerStepTrait).
 */
class Form_LedgerApplicant extends MultiFormStep
{
    use StepTrait, LedgerStepTrait {
        LedgerStepTrait::validateStep insteadof StepTrait;
    }

    public const FIELD_PREFIX = 'SEApplicant';

    public const RULE_MODEL = SubmissionSelfEmployed::class;

    private static $default_sort_order = 35;

    protected function introText(): string
    {
        return _t(__CLASS__ . '.IntroText', 'Beiblatt: Einnahmen und Ausgaben je Monat (Antragsteller(in))');
    }
}
