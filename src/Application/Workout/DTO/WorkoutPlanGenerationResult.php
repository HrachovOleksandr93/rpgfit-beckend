<?php

declare(strict_types=1);

namespace App\Application\Workout\DTO;

use App\Application\PsychProfile\DTO\WorkoutPlanAdaptation;
use App\Domain\Workout\Entity\WorkoutPlan;

/**
 * Return value of WorkoutPlanGeneratorService::generatePlanWithAdaptation.
 *
 * Application layer DTO (Workout bounded context). Carries the persisted
 * plan PLUS the psych-adapter adaptation applied to it, so the controller
 * can surface `warningCopy` + deltas to the client without re-querying.
 */
final class WorkoutPlanGenerationResult
{
    public function __construct(
        public readonly WorkoutPlan $plan,
        public readonly WorkoutPlanAdaptation $adaptation,
    ) {
    }
}
