<?php

declare(strict_types=1);

namespace App\Application\Test\Service;

use App\Domain\Character\Entity\CharacterStats;
use App\Domain\User\Entity\User;
use App\Infrastructure\Character\Repository\CharacterStatsRepository;

/**
 * Directly sets STR / DEX / CON on the target. Bypasses the usual
 * "30-point allocation" rule — the audit row flags this as a state-machine
 * bypass (see spec §3.8).
 */
final class StatsTestService
{
    private const MIN_STAT = 0;
    private const MAX_STAT = 999;

    public function __construct(
        private readonly CharacterStatsRepository $characterStatsRepository,
    ) {
    }

    /**
     * Null entries are left untouched.
     *
     * @return array{str: int, dex: int, con: int}
     */
    public function setStats(User $user, ?int $str, ?int $dex, ?int $con): array
    {
        $stats = $this->characterStatsRepository->findByUser($user);
        if ($stats === null) {
            $stats = new CharacterStats();
            $stats->setUser($user);
        }

        if ($str !== null) {
            $stats->setStrength($this->clamp($str));
        }
        if ($dex !== null) {
            $stats->setDexterity($this->clamp($dex));
        }
        if ($con !== null) {
            $stats->setConstitution($this->clamp($con));
        }

        $this->characterStatsRepository->save($stats);

        return [
            'str' => $stats->getStrength(),
            'dex' => $stats->getDexterity(),
            'con' => $stats->getConstitution(),
        ];
    }

    private function clamp(int $value): int
    {
        return max(self::MIN_STAT, min(self::MAX_STAT, $value));
    }
}
