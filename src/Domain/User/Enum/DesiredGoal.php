<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

enum DesiredGoal: string
{
    case LoseWeight = 'lose_weight';
    case GainMass = 'gain_mass';
    case Maintain = 'maintain';
}
