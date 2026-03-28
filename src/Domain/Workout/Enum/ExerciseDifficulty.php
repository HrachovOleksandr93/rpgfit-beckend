<?php

declare(strict_types=1);

namespace App\Domain\Workout\Enum;

/** Difficulty level required to perform an exercise safely and effectively. */
enum ExerciseDifficulty: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
}
