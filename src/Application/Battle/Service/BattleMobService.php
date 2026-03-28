<?php

declare(strict_types=1);

namespace App\Application\Battle\Service;

use App\Domain\Battle\Enum\BattleMode;
use App\Domain\Inventory\Enum\ItemRarity;
use App\Domain\User\Entity\User;
use App\Infrastructure\Character\Repository\CharacterStatsRepository;
use App\Infrastructure\Mob\Repository\MobRepository;

/**
 * Selects and adjusts a mob for a battle session based on user level and battle mode.
 *
 * Application layer (Battle bounded context). Queries mobs within a level range
 * of the user (plus/minus 2), filters by rarity according to the battle mode,
 * and applies stat multipliers for raid encounters (+30% HP and XP).
 */
class BattleMobService
{
    /** Raid mode multiplier applied to mob HP and XP reward. */
    private const RAID_MULTIPLIER = 1.3;

    /** Level range above and below user level when searching for mobs. */
    private const LEVEL_RANGE = 2;

    public function __construct(
        private readonly MobRepository $mobRepository,
        private readonly CharacterStatsRepository $characterStatsRepository,
    ) {
    }

    /**
     * Select a random mob appropriate for the user's level and battle mode.
     *
     * Returns an array with the mob entity and adjusted HP/XP values.
     * For raid mode, HP and XP are multiplied by 1.3.
     *
     * @return array{mob: \App\Domain\Mob\Entity\Mob|null, hp: int, xpReward: int}
     */
    public function selectMob(User $user, BattleMode $mode): array
    {
        $userLevel = $this->getUserLevel($user);

        $levelMin = max(1, $userLevel - self::LEVEL_RANGE);
        $levelMax = $userLevel + self::LEVEL_RANGE;

        // Query mobs within the user's level range
        $qb = $this->mobRepository->createQueryBuilder('m')
            ->where('m.level BETWEEN :levelMin AND :levelMax')
            ->setParameter('levelMin', $levelMin)
            ->setParameter('levelMax', $levelMax);

        // Filter by rarity based on battle mode
        $rarities = $this->getAllowedRarities($mode);
        if (!empty($rarities)) {
            $qb->andWhere('m.rarity IN (:rarities)')
                ->setParameter('rarities', array_map(fn(ItemRarity $r) => $r->value, $rarities));
        }

        $mobs = $qb->getQuery()->getResult();

        if (empty($mobs)) {
            // Fallback: try any mob at any level if no match found
            $mobs = $this->mobRepository->findAll();
        }

        if (empty($mobs)) {
            return ['mob' => null, 'hp' => 0, 'xpReward' => 0];
        }

        // For raid mode, prefer rarer mobs by sorting by rarity weight
        if ($mode === BattleMode::Raid) {
            usort($mobs, function ($a, $b) {
                return $this->getRarityWeight($b->getRarity()) - $this->getRarityWeight($a->getRarity());
            });
            // Pick from the top third (rarest)
            $topCount = max(1, (int) ceil(count($mobs) / 3));
            $mob = $mobs[array_rand(array_slice($mobs, 0, $topCount))];
        } else {
            $mob = $mobs[array_rand($mobs)];
        }

        $hp = $mob->getHp();
        $xpReward = $mob->getXpReward();

        // Apply raid multiplier
        if ($mode === BattleMode::Raid) {
            $hp = (int) round($hp * self::RAID_MULTIPLIER);
            $xpReward = (int) round($xpReward * self::RAID_MULTIPLIER);
        }

        return [
            'mob' => $mob,
            'hp' => $hp,
            'xpReward' => $xpReward,
        ];
    }

    /** Get the user's current character level, defaulting to 1 if no stats exist. */
    private function getUserLevel(User $user): int
    {
        $stats = $this->characterStatsRepository->findByUser($user);

        return $stats?->getLevel() ?? 1;
    }

    /**
     * Get the allowed rarity tiers for the given battle mode.
     *
     * @return ItemRarity[]
     */
    private function getAllowedRarities(BattleMode $mode): array
    {
        return match ($mode) {
            BattleMode::Custom, BattleMode::Recommended => [
                ItemRarity::Common,
                ItemRarity::Uncommon,
                ItemRarity::Rare,
            ],
            BattleMode::Raid => [
                ItemRarity::Rare,
                ItemRarity::Epic,
                ItemRarity::Legendary,
            ],
        };
    }

    /** Numeric weight for rarity sorting (higher = rarer). */
    private function getRarityWeight(?ItemRarity $rarity): int
    {
        if ($rarity === null) {
            return 0;
        }

        return match ($rarity) {
            ItemRarity::Common => 1,
            ItemRarity::Uncommon => 2,
            ItemRarity::Rare => 3,
            ItemRarity::Epic => 4,
            ItemRarity::Legendary => 5,
        };
    }
}
