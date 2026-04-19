<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Mob\Service\MobSelectionService;
use App\Domain\Mob\Entity\Mob;
use App\Domain\Mob\Enum\MobArchetype;
use App\Domain\Mob\Enum\MobBehavior;
use App\Domain\Mob\Enum\MobClassTier;
use App\Domain\Shared\Enum\Realm;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MobSelectionService::maybeAsChampion().
 *
 * Uses a scripted random source so the champion roll is deterministic.
 * Verifies:
 *  - low roll promotes eligible mobs to champion with +10% HP / +25% XP
 *  - high roll leaves the mob unchanged
 *  - non-eligible mobs (accepts_champion=false) never get promoted
 *  - decoration is drawn from the archetype-specific pool
 */
class MobSelectionServiceTest extends TestCase
{
    public function testMaybeAsChampionPromotesEligibleMobOnLowRoll(): void
    {
        $rolls = $this->scriptedRandom([0.05, 0.0]);
        $service = new MobSelectionService($rolls);

        $mob = $this->buildMob(MobArchetype::Humanoid, acceptsChampion: true, hp: 100, xp: 40);

        $result = $service->maybeAsChampion($mob);

        $this->assertTrue($result->isChampion());
        $this->assertSame(110, $result->getHp(), 'HP should be +10%');
        $this->assertSame(50, $result->getXpReward(), 'XP should be +25%');
        $this->assertNotNull($result->getChampionDecoration());
    }

    public function testMaybeAsChampionSkipsWhenRollAboveChance(): void
    {
        $rolls = $this->scriptedRandom([0.95]);
        $service = new MobSelectionService($rolls);

        $mob = $this->buildMob(MobArchetype::Humanoid, acceptsChampion: true, hp: 100, xp: 40);

        $result = $service->maybeAsChampion($mob);

        $this->assertFalse($result->isChampion());
        $this->assertSame(100, $result->getHp());
        $this->assertSame(40, $result->getXpReward());
        $this->assertNull($result->getChampionDecoration());
    }

    public function testMaybeAsChampionRefusesIneligibleMobEvenOnLowRoll(): void
    {
        // Even though the first roll would promote, acceptsChampion=false short-circuits.
        $rolls = $this->scriptedRandom([0.0]);
        $service = new MobSelectionService($rolls);

        $mob = $this->buildMob(MobArchetype::Spirit, acceptsChampion: false, hp: 100, xp: 40);

        $result = $service->maybeAsChampion($mob);

        $this->assertFalse($result->isChampion());
        $this->assertSame(100, $result->getHp());
        $this->assertSame(40, $result->getXpReward());
    }

    public function testMaybeAsChampionDecorationIsDrawnFromArchetypePool(): void
    {
        // First roll triggers promotion, second roll (=0.0) picks index 0 of pool.
        $rolls = $this->scriptedRandom([0.0, 0.0]);
        $service = new MobSelectionService($rolls);

        $mob = $this->buildMob(MobArchetype::Humanoid, acceptsChampion: true, hp: 100, xp: 40);

        $result = $service->maybeAsChampion($mob);

        // Humanoid pool starts with apple_watch.
        $this->assertSame('apple_watch', $result->getChampionDecoration());
    }

    public function testExplicitChanceOverridesDefault(): void
    {
        // A roll of 0.50 is above the default 0.12 but below 0.99.
        $rolls = $this->scriptedRandom([0.50, 0.0]);
        $service = new MobSelectionService($rolls);

        $mob = $this->buildMob(MobArchetype::Humanoid, acceptsChampion: true, hp: 200, xp: 80);

        $result = $service->maybeAsChampion($mob, chance: 0.99);

        $this->assertTrue($result->isChampion());
    }

    /**
     * Build a minimally-valid Mob instance for the service to operate on.
     */
    private function buildMob(
        MobArchetype $archetype,
        bool $acceptsChampion,
        int $hp,
        int $xp,
    ): Mob {
        $mob = new Mob();
        $mob->setName('Test Mob')
            ->setSlug('test-mob-' . bin2hex(random_bytes(3)))
            ->setLevel(5)
            ->setHp($hp)
            ->setXpReward($xp)
            ->setRealm(Realm::Olympus)
            ->setClassTier(MobClassTier::I)
            ->setBehavior(MobBehavior::Physical)
            ->setArchetype($archetype)
            ->setAcceptsChampion($acceptsChampion);

        return $mob;
    }

    /**
     * Build a random source that returns the scripted values in order,
     * repeating the last value once the script is exhausted.
     *
     * @param list<float> $values
     *
     * @return callable():float
     */
    private function scriptedRandom(array $values): callable
    {
        $index = 0;

        return static function () use (&$index, $values): float {
            $value = $values[$index] ?? $values[count($values) - 1];
            $index++;

            return $value;
        };
    }
}
