<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

/** User's primary fitness goal, selected during registration. Drives personalized workout and nutrition guidance. */
enum DesiredGoal: string
{
    case LoseWeight = 'lose_weight';
    case GainMass = 'gain_mass';
    case Maintain = 'maintain';
}
