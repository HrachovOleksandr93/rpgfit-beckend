<?php

declare(strict_types=1);

namespace App\Application\Battle\DTO;

/**
 * Value object representing the full result of a battle session calculation.
 *
 * Encapsulates all data produced by BattleResultCalculator: performance tier,
 * damage dealt, mobs defeated, XP awards, loot flags, and level-up info.
 * Used to transfer battle results between the calculator service and the controller.
 */
final class BattleResult
{
    public function __construct(
        /** Performance tier: failed, survived, completed, exceeded, or raid_exceeded. */
        public readonly string $performanceTier,
        /** Percentage of the workout plan completed (0-100+). */
        public readonly float $completionPercent,
        /** Number of mobs defeated during the session. */
        public readonly int $mobsDefeated,
        /** Total damage dealt to mobs across all ticks/exercises. */
        public readonly int $totalDamage,
        /** Raw XP earned from defeating mobs (before bonuses). */
        public readonly int $xpFromMobs,
        /** Bonus XP percentage applied (e.g. 10.0 = +10%). */
        public readonly float $bonusXpPercent,
        /** Final XP awarded after applying bonuses. */
        public readonly int $xpAwarded,
        /** Whether normal loot was earned. */
        public readonly bool $lootEarned,
        /** Whether super loot was earned (raid exceeded). */
        public readonly bool $superLootEarned,
        /** Whether the user leveled up as a result. */
        public readonly bool $levelUp,
        /** The user's new level after XP award. */
        public readonly int $newLevel,
        /** The user's total XP after award. */
        public readonly int $totalXp,
        /** Human-readable result message. */
        public readonly string $message,
    ) {
    }

    /** Convert to an array suitable for JSON API response. */
    public function toArray(): array
    {
        return [
            'performanceTier' => $this->performanceTier,
            'completionPercent' => $this->completionPercent,
            'mobsDefeated' => $this->mobsDefeated,
            'totalDamage' => $this->totalDamage,
            'xpFromMobs' => $this->xpFromMobs,
            'bonusXpPercent' => $this->bonusXpPercent,
            'xpAwarded' => $this->xpAwarded,
            'lootEarned' => $this->lootEarned,
            'superLootEarned' => $this->superLootEarned,
            'levelUp' => $this->levelUp,
            'newLevel' => $this->newLevel,
            'totalXp' => $this->totalXp,
            'message' => $this->message,
        ];
    }
}
