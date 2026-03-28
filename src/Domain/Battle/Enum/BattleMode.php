<?php

declare(strict_types=1);

namespace App\Domain\Battle\Enum;

/** Battle mode determines mob selection rules and stat modifiers for a session. */
enum BattleMode: string
{
    case Custom = 'custom';
    case Recommended = 'recommended';
    case Raid = 'raid';
}
