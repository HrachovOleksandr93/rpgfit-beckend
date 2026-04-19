<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enum;

/**
 * Canonical realm identifier shared across Mob, Portal, and Artifact domains.
 *
 * Matches the six realms established in the world lore
 * (docs/lore-extracts/realms-canon.md) plus a "neutral" fallback for
 * legacy content that is not yet bound to a specific realm.
 */
enum Realm: string
{
    case Olympus = 'olympus';
    case Asgard = 'asgard';
    case Dharma = 'dharma';
    case Duat = 'duat';
    case Nav = 'nav';
    case Shiba = 'shiba';
    case Neutral = 'neutral';
}
