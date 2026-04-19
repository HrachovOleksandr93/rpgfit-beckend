<?php

declare(strict_types=1);

namespace App\Application\Mob\Service;

use App\Domain\Mob\Entity\Mob;
use App\Domain\Mob\Enum\MobArchetype;

/**
 * Application service that transforms a base Mob into its champion variant
 * when eligible. Champion spawn behavior is documented in
 * `docs/vision/mobs-champion-variants.md` and BA/outputs/09-mob-bestiary.md §2.4.
 *
 * - Champion roll probability: 12% (middle of 10-15% range) by default.
 * - Only mobs with `acceptsChampion() === true` are eligible.
 * - Champion gets +10% HP, +25% XP, and a decoration drawn from a pool
 *   keyed by archetype.
 *
 * The random source is injected as a callable so tests can make the roll
 * deterministic (see `tests/Unit/MobSelectionServiceTest.php`).
 */
final class MobSelectionService
{
    /** Default champion spawn probability (0..1). */
    public const DEFAULT_CHAMPION_CHANCE = 0.12;

    /** Default HP multiplier applied to champions. */
    public const CHAMPION_HP_MULTIPLIER = 1.10;

    /** Default XP multiplier applied to champions. */
    public const CHAMPION_XP_MULTIPLIER = 1.25;

    /**
     * Decoration pool keyed by archetype. The first decoration that plausibly
     * fits the body plan is used by the deterministic picker (seeded by the
     * random source). Realm-specific restrictions are handled downstream.
     *
     * @var array<string, list<string>>
     */
    private const DECORATION_POOL = [
        'humanoid'  => ['apple_watch', 'powerbank_necklace', 'smartphone_pouch', 'hydration_pack'],
        'beast'     => ['gym_collar', 'gps_ear_tag', 'dumbbell_chain'],
        'chimera'   => ['apple_watch', 'dumbbell_chain', 'hydration_pack'],
        'undead'    => ['powerbank_necklace', 'smartphone_pouch'],
        'construct' => ['gym_plate_armor', 'barbell_arm'],
        // Spirit / swarm / divine mobs should normally have acceptsChampion=false
        // but we keep a fallback so the service never throws for legacy data.
        'spirit'    => ['smartphone_pouch'],
        'swarm'     => ['gps_ear_tag'],
        'divine'    => ['laurel_smartwatch'],
    ];

    /** @var callable():float */
    private $randomSource;

    /**
     * @param (callable():float)|null $randomSource Supplier of floats in [0, 1). Defaults to mt_rand-based.
     */
    public function __construct(?callable $randomSource = null)
    {
        $this->randomSource = $randomSource ?? static fn (): float => mt_rand() / mt_getrandmax();
    }

    /**
     * Roll for a champion variant. Returns a mutated Mob (same entity reference)
     * when promoted, or the input unchanged when the roll fails or the mob is
     * not champion-eligible.
     *
     * NOTE: mutates the entity in place (HP / XP / decoration / is_champion),
     * which matches how BattleService treats selected mobs — the caller is
     * expected to persist or discard the result within the same request scope.
     */
    public function maybeAsChampion(Mob $mob, ?float $chance = null): Mob
    {
        if (!$mob->acceptsChampion()) {
            return $mob;
        }

        $effectiveChance = $chance ?? self::DEFAULT_CHAMPION_CHANCE;
        $roll = ($this->randomSource)();

        if ($roll >= $effectiveChance) {
            return $mob;
        }

        $mob->setIsChampion(true);
        $mob->setHp((int) round($mob->getHp() * self::CHAMPION_HP_MULTIPLIER));
        $mob->setXpReward((int) round($mob->getXpReward() * self::CHAMPION_XP_MULTIPLIER));
        $mob->setChampionDecoration($this->pickDecoration($mob->getArchetype()));

        return $mob;
    }

    /**
     * Pick a deterministic decoration based on archetype. The index is drawn
     * from the random source so tests can pin the exact decoration.
     */
    private function pickDecoration(MobArchetype $archetype): string
    {
        $pool = self::DECORATION_POOL[$archetype->value] ?? ['apple_watch'];
        $index = (int) floor(($this->randomSource)() * count($pool));
        $index = max(0, min($index, count($pool) - 1));

        return $pool[$index];
    }
}
