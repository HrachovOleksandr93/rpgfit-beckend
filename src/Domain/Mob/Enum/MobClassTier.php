<?php

declare(strict_types=1);

namespace App\Domain\Mob\Enum;

/**
 * Mob class tier as defined in the world lore (docx Part III.1).
 *
 * - I:   dregs, spirits, nymphs (common / uncommon)
 * - II:  demigods, named monsters (rare)
 * - III: lesser gods, titan-spawn (epic)
 * - IV:  realm residents, raid-only (legendary)
 */
enum MobClassTier: string
{
    case I = 'I';
    case II = 'II';
    case III = 'III';
    case IV = 'IV';
}
