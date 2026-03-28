<?php

declare(strict_types=1);

namespace App\Domain\Workout\Enum;

/** Lifecycle status of a scheduled workout plan. */
enum WorkoutPlanStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Skipped = 'skipped';
}
