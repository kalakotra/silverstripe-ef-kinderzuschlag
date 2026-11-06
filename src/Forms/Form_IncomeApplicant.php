<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Forms;

use SilverStripe\MultiForm\Models\MultiFormStep;

/**
 * KiZ 1-AnA Punkt 5/6, column "für mich" (see IncomeStepTrait).
 */
class Form_IncomeApplicant extends MultiFormStep
{
    use StepTrait;
    use IncomeStepTrait;

    public const PERSON_PREFIX = 'Applicant';

    private static $default_sort_order = 32;

    private static array $conditional_required = [
        'ApplicantWageEvidence',
        'ApplicantCertTraining',
    ];

    protected function introText(): string
    {
        return _t(__CLASS__ . '.IntroText', 'Einnahmen und Ausgaben: Antragsteller(in)');
    }
}
