<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

/**
 * User's preferred workout type, selected during registration or onboarding.
 *
 * Used to personalize training recommendations and to calculate initial RPG stats
 * via StatCalculationService. Each type biases stat distribution differently.
 */
enum WorkoutType: string
{
    case Cardio = 'cardio';
    case Strength = 'strength';
    case Mixed = 'mixed';
    case Crossfit = 'crossfit';
    case Gymnastics = 'gymnastics';
    case MartialArts = 'martial_arts';
    case Yoga = 'yoga';
}
