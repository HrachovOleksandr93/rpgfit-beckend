<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Battle\Entity\WorkoutSession;
use App\Domain\Battle\Enum\BattleMode;
use App\Domain\Battle\Enum\SessionStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Unit tests for the WorkoutSession entity.
 *
 * Verifies UUID generation, default values, getter/setter behaviour,
 * enum assignments, and nullable field handling.
 */
class WorkoutSessionEntityTest extends TestCase
{
    /** Verify that a new WorkoutSession automatically receives a UUID v4 identifier. */
    public function testCreationGeneratesUuid(): void
    {
        $session = new WorkoutSession();

        $this->assertInstanceOf(Uuid::class, $session->getId());
    }

    /** Verify that startedAt is set automatically on construction. */
    public function testCreationSetsStartedAt(): void
    {
        $before = new \DateTimeImmutable();
        $session = new WorkoutSession();
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $session->getStartedAt());
        $this->assertLessThanOrEqual($after, $session->getStartedAt());
    }

    /** Verify that default status is active. */
    public function testDefaultStatusIsActive(): void
    {
        $session = new WorkoutSession();

        $this->assertSame(SessionStatus::Active, $session->getStatus());
    }

    /** Verify that default totalDamageDealt is 0. */
    public function testDefaultDamageIsZero(): void
    {
        $session = new WorkoutSession();

        $this->assertSame(0, $session->getTotalDamageDealt());
    }

    /** Verify that default xpAwarded is 0. */
    public function testDefaultXpAwardedIsZero(): void
    {
        $session = new WorkoutSession();

        $this->assertSame(0, $session->getXpAwarded());
    }

    /** Verify that nullable fields default to null. */
    public function testNullableFieldsDefaultToNull(): void
    {
        $session = new WorkoutSession();

        $this->assertNull($session->getMob());
        $this->assertNull($session->getMobHp());
        $this->assertNull($session->getMobXpReward());
        $this->assertNull($session->getCompletedAt());
        $this->assertNull($session->getHealthData());
        $this->assertNull($session->getUsedSkillSlugs());
        $this->assertNull($session->getUsedConsumableSlugs());
    }

    /** Verify that new mob tracking fields default to zero. */
    public function testMobTrackingFieldsDefaultToZero(): void
    {
        $session = new WorkoutSession();

        $this->assertSame(0, $session->getMobsDefeated());
        $this->assertSame(0, $session->getTotalXpFromMobs());
    }

    /** Verify usedSkillSlugs getter and setter with array data. */
    public function testUsedSkillSlugsSetterAndGetter(): void
    {
        $session = new WorkoutSession();
        $slugs = ['battle-fury', 'berserker-rage'];

        $session->setUsedSkillSlugs($slugs);

        $this->assertSame($slugs, $session->getUsedSkillSlugs());
    }

    /** Verify usedConsumableSlugs getter and setter with array data. */
    public function testUsedConsumableSlugsSetterAndGetter(): void
    {
        $session = new WorkoutSession();
        $slugs = ['strength-potion-minor'];

        $session->setUsedConsumableSlugs($slugs);

        $this->assertSame($slugs, $session->getUsedConsumableSlugs());
    }

    /** Verify mobsDefeated getter and setter. */
    public function testMobsDefeatedSetterAndGetter(): void
    {
        $session = new WorkoutSession();

        $session->setMobsDefeated(5);

        $this->assertSame(5, $session->getMobsDefeated());
    }

    /** Verify totalXpFromMobs getter and setter. */
    public function testTotalXpFromMobsSetterAndGetter(): void
    {
        $session = new WorkoutSession();

        $session->setTotalXpFromMobs(350);

        $this->assertSame(350, $session->getTotalXpFromMobs());
    }

    /** Verify mode getter and setter with all enum values. */
    public function testModeSetterAndGetter(): void
    {
        $session = new WorkoutSession();

        foreach (BattleMode::cases() as $mode) {
            $session->setMode($mode);
            $this->assertSame($mode, $session->getMode());
        }
    }

    /** Verify status getter and setter with all enum values. */
    public function testStatusSetterAndGetter(): void
    {
        $session = new WorkoutSession();

        foreach (SessionStatus::cases() as $status) {
            $session->setStatus($status);
            $this->assertSame($status, $session->getStatus());
        }
    }

    /** Verify integer field setters and getters. */
    public function testIntegerFields(): void
    {
        $session = new WorkoutSession();

        $session->setMobHp(850);
        $session->setMobXpReward(65);
        $session->setTotalDamageDealt(500);
        $session->setXpAwarded(235);

        $this->assertSame(850, $session->getMobHp());
        $this->assertSame(65, $session->getMobXpReward());
        $this->assertSame(500, $session->getTotalDamageDealt());
        $this->assertSame(235, $session->getXpAwarded());
    }

    /** Verify healthData getter and setter with array data. */
    public function testHealthDataSetterAndGetter(): void
    {
        $session = new WorkoutSession();
        $healthData = [
            'duration' => 3600,
            'calories' => 350.0,
            'distance' => null,
            'averageHeartRate' => 145,
        ];

        $session->setHealthData($healthData);

        $this->assertSame($healthData, $session->getHealthData());
    }

    /** Verify completedAt getter and setter. */
    public function testCompletedAtSetterAndGetter(): void
    {
        $session = new WorkoutSession();
        $now = new \DateTimeImmutable();

        $session->setCompletedAt($now);

        $this->assertSame($now, $session->getCompletedAt());
    }

    /** Verify that setters return $this for fluent chaining. */
    public function testSetterChaining(): void
    {
        $session = new WorkoutSession();

        $result = $session
            ->setMode(BattleMode::Recommended)
            ->setMobHp(100)
            ->setMobXpReward(50)
            ->setTotalDamageDealt(75)
            ->setXpAwarded(40)
            ->setStatus(SessionStatus::Completed);

        $this->assertSame($session, $result);
    }

    /** Verify __toString returns a useful string representation. */
    public function testToString(): void
    {
        $session = new WorkoutSession();

        $str = (string) $session;
        $this->assertStringContainsString('Session', $str);
        $this->assertStringContainsString('active', $str);
    }
}
