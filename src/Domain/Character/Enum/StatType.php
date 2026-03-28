<?php

declare(strict_types=1);

namespace App\Domain\Character\Enum;

/** RPG character stat types. Each exercise awards points to one or more of these stats via ExerciseStatReward. */
enum StatType: string
{
    case Strength = 'str';
    case Constitution = 'con';
    case Dexterity = 'dex';
}
