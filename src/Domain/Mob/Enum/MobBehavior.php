<?php

declare(strict_types=1);

namespace App\Domain\Mob\Enum;

/**
 * Mob behavior — how the mob expects to be defeated.
 *
 * - physical:    raw damage from any workout (default 70% of roster)
 * - ritual:      damage gated by knowledge / artifact / specific workout type
 * - oracle_task: structured physical challenge (e.g. 20 pull-ups in one session)
 * - team:        raid-only, spawnable only in raid mode
 */
enum MobBehavior: string
{
    case Physical = 'physical';
    case Ritual = 'ritual';
    case OracleTask = 'oracle_task';
    case Team = 'team';
}
