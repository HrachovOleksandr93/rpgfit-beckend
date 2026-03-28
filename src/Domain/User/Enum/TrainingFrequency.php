<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

/**
 * How often the user trains per week, collected during onboarding.
 *
 * Affects initial stat point distribution via StatCalculationService.
 * Higher frequency yields more balanced stat bonuses.
 */
enum TrainingFrequency: string
{
    case None = 'none';
    case Light = 'light';
    case Moderate = 'moderate';
    case Heavy = 'heavy';
}
