<?php

declare(strict_types=1);

namespace App\Domain\Workout\Enum;

/** Type of equipment required to perform an exercise. */
enum Equipment: string
{
    case Barbell = 'barbell';
    case Dumbbell = 'dumbbell';
    case Cable = 'cable';
    case Machine = 'machine';
    case Bodyweight = 'bodyweight';
    case Kettlebell = 'kettlebell';
    case ResistanceBand = 'resistance_band';
    case None = 'none';
}
