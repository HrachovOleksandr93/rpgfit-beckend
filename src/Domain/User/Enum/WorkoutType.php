<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

enum WorkoutType: string
{
    case Cardio = 'cardio';
    case Strength = 'strength';
    case Mixed = 'mixed';
}
