<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

/** User's preferred workout type, selected during registration. Used to personalize training recommendations. */
enum WorkoutType: string
{
    case Cardio = 'cardio';
    case Strength = 'strength';
    case Mixed = 'mixed';
}
