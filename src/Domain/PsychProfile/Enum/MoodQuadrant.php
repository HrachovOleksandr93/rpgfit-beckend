<?php

declare(strict_types=1);

namespace App\Domain\PsychProfile\Enum;

/**
 * Russell-quadrant mood label captured in Q1 of the daily check-in.
 *
 * Domain layer (PsychProfile bounded context). Matches the text-only picker
 * (spec 2026-04-18 §2.Q1) — no icons, no emojis ever attached to these
 * identifiers. UA / EN labels live on the client.
 *
 * Quadrant coordinates (Russell's circumplex):
 *  - DRAINED   -> LL  (low-valence, low-arousal)
 *  - AT_EASE   -> HL  (high-valence, low-arousal)
 *  - NEUTRAL   -> center
 *  - ENERGIZED -> HH  (high-valence, high-arousal)
 *  - ON_EDGE   -> LH  (low-valence, high-arousal)
 */
enum MoodQuadrant: string
{
    case DRAINED = 'DRAINED';
    case AT_EASE = 'AT_EASE';
    case NEUTRAL = 'NEUTRAL';
    case ENERGIZED = 'ENERGIZED';
    case ON_EDGE = 'ON_EDGE';
}
