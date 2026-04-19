<?php

declare(strict_types=1);

namespace App\Domain\Mob\Enum;

/**
 * Mob archetype — high-level visual / body-plan classification.
 *
 * Used for filtering (e.g. champion variants only decorate body-bearing
 * archetypes) and for artist briefs (visual_keywords are per-archetype).
 */
enum MobArchetype: string
{
    case Humanoid = 'humanoid';
    case Beast = 'beast';
    case Spirit = 'spirit';
    case Undead = 'undead';
    case Construct = 'construct';
    case Divine = 'divine';
    case Chimera = 'chimera';
    case Swarm = 'swarm';
}
