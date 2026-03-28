<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

/**
 * User's daily activity level outside of dedicated training, collected during onboarding.
 *
 * Affects initial stat point distribution via StatCalculationService.
 * More active lifestyles provide additional stat bonuses.
 */
enum Lifestyle: string
{
    case Sedentary = 'sedentary';
    case Moderate = 'moderate';
    case Active = 'active';
    case VeryActive = 'very_active';
}
