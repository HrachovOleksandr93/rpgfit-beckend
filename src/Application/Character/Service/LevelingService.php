<?php

declare(strict_types=1);

namespace App\Application\Character\Service;

use App\Infrastructure\Character\Repository\CharacterStatsRepository;
use App\Infrastructure\Character\Repository\ExperienceLogRepository;
use App\Infrastructure\Config\Repository\GameSettingRepository;

/**
 * Leveling curve calculations: XP required per level, total XP, level-from-XP lookup.
 *
 * Application layer (Character bounded context). Uses a quadratic formula
 * xp(L) = floor(quad * L^2 + linear * L) where the coefficients come from
 * the game_settings table (keys: level_formula_quad, level_formula_linear).
 *
 * The curve is tuned so that a casual gym-goer (~312 XP/day) reaches level 30
 * in about 6 months, level 70 in about 5 years, while a pro athlete (~900 XP/day)
 * can hit level 70 in under 2 years.
 */
final class LevelingService
{
    /** In-memory cache of the settings map for the current request. */
    private ?array $settingsCache = null;

    public function __construct(
        private readonly GameSettingRepository $gameSettingRepository,
        private readonly CharacterStatsRepository $characterStatsRepository,
        private readonly ExperienceLogRepository $experienceLogRepository,
    ) {
    }

    /**
     * XP required to advance from the given level to the next one.
     *
     * Formula: floor(quad * level^2 + linear * level)
     */
    public function getXpForLevel(int $level): int
    {
        $quad = (float) $this->getSetting('level_formula_quad', '4.2');
        $linear = (float) $this->getSetting('level_formula_linear', '28');

        return (int) floor($quad * $level * $level + $linear * $level);
    }

    /**
     * Cumulative XP needed to reach a given level (sum of xpForLevel from 1 to level).
     */
    public function getTotalXpForLevel(int $level): int
    {
        $total = 0;
        for ($i = 1; $i <= $level; $i++) {
            $total += $this->getXpForLevel($i);
        }

        return $total;
    }

    /**
     * Determine the current level for a given cumulative XP total.
     *
     * Iterates through levels 1..max and returns the highest level whose
     * cumulative XP threshold has been met.
     */
    public function getLevelForTotalXp(int $totalXp): int
    {
        $maxLevel = (int) $this->getSetting('level_max', '100');
        $cumulative = 0;

        for ($level = 1; $level <= $maxLevel; $level++) {
            $cumulative += $this->getXpForLevel($level);
            if ($cumulative > $totalXp) {
                return max(1, $level - 1);
            }
        }

        return $maxLevel;
    }

    /**
     * Return detailed progress information for the given total XP.
     *
     * @return array{level: int, currentLevelXp: int, xpToNextLevel: int, progressPercent: float}
     */
    public function getLevelProgress(int $totalXp): array
    {
        $maxLevel = (int) $this->getSetting('level_max', '100');
        $cumulative = 0;
        $currentLevel = 1;

        // Walk through levels to find where the totalXp falls
        for ($level = 1; $level <= $maxLevel; $level++) {
            $xpNeeded = $this->getXpForLevel($level);
            if ($cumulative + $xpNeeded > $totalXp) {
                $currentLevel = max(1, $level - 1);
                break;
            }
            $cumulative += $xpNeeded;
            if ($level === $maxLevel) {
                $currentLevel = $maxLevel;
            }
        }

        // XP earned within the current level bracket
        $xpIntoCurrentLevel = $totalXp - $cumulative;

        // XP needed for the next level (or 0 if at max)
        $xpToNextLevel = ($currentLevel < $maxLevel)
            ? $this->getXpForLevel($currentLevel + 1)
            : 0;

        $progressPercent = ($xpToNextLevel > 0)
            ? round($xpIntoCurrentLevel / $xpToNextLevel * 100, 1)
            : 100.0;

        return [
            'level' => $currentLevel,
            'currentLevelXp' => $xpIntoCurrentLevel,
            'xpToNextLevel' => $xpToNextLevel,
            'progressPercent' => $progressPercent,
        ];
    }

    /**
     * Build the full XP table for all levels (used by the public /api/levels/table endpoint).
     *
     * @return array<int, array{level: int, xpRequired: int, totalXpRequired: int}>
     */
    public function getFullLevelTable(): array
    {
        $maxLevel = (int) $this->getSetting('level_max', '100');
        $table = [];
        $cumulative = 0;

        for ($level = 1; $level <= $maxLevel; $level++) {
            $xp = $this->getXpForLevel($level);
            $cumulative += $xp;
            $table[] = [
                'level' => $level,
                'xpRequired' => $xp,
                'totalXpRequired' => $cumulative,
            ];
        }

        return $table;
    }

    /** Read a setting value, falling back to a default if not found. */
    private function getSetting(string $key, string $default): string
    {
        if ($this->settingsCache === null) {
            $this->settingsCache = $this->gameSettingRepository->getAllAsMap();
        }

        return $this->settingsCache[$key] ?? $default;
    }
}
