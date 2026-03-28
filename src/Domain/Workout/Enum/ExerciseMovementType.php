<?php

declare(strict_types=1);

namespace App\Domain\Workout\Enum;

/** Whether the exercise is compound (multi-joint) or isolation (single-joint). */
enum ExerciseMovementType: string
{
    case Compound = 'compound';
    case Isolation = 'isolation';
}
