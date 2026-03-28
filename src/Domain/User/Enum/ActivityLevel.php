<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

/** User's daily activity level, selected during registration. Used to calibrate fitness goals and recommendations. */
enum ActivityLevel: string
{
    case Sedentary = 'sedentary';
    case Light = 'light';
    case Moderate = 'moderate';
    case Active = 'active';
    case VeryActive = 'very_active';
}
