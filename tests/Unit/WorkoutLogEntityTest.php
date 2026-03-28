<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Health\Entity\HealthDataPoint;
use App\Domain\Training\Entity\WorkoutLog;
use App\Domain\User\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class WorkoutLogEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $log = new WorkoutLog();

        $this->assertInstanceOf(Uuid::class, $log->getId());
    }

    public function testCreationSetsCreatedAt(): void
    {
        $before = new \DateTimeImmutable();
        $log = new WorkoutLog();
        $after = new \DateTimeImmutable();

        $this->assertInstanceOf(\DateTimeImmutable::class, $log->getCreatedAt());
        $this->assertGreaterThanOrEqual($before, $log->getCreatedAt());
        $this->assertLessThanOrEqual($after, $log->getCreatedAt());
    }

    public function testSettersAndGetters(): void
    {
        $log = new WorkoutLog();
        $user = new User();
        $performedAt = new \DateTimeImmutable('2026-03-28T08:00:00+00:00');

        $log->setUser($user);
        $log->setWorkoutType('running');
        $log->setDurationMinutes(45.5);
        $log->setCaloriesBurned(350.0);
        $log->setDistance(5.2);
        $log->setPerformedAt($performedAt);

        $this->assertSame($user, $log->getUser());
        $this->assertSame('running', $log->getWorkoutType());
        $this->assertSame(45.5, $log->getDurationMinutes());
        $this->assertSame(350.0, $log->getCaloriesBurned());
        $this->assertSame(5.2, $log->getDistance());
        $this->assertEquals($performedAt, $log->getPerformedAt());
    }

    public function testNullableFields(): void
    {
        $log = new WorkoutLog();

        $log->setCaloriesBurned(null);
        $log->setDistance(null);
        $log->setHealthDataPoint(null);
        $log->setExtraDetails(null);

        $this->assertNull($log->getCaloriesBurned());
        $this->assertNull($log->getDistance());
        $this->assertNull($log->getHealthDataPoint());
        $this->assertNull($log->getExtraDetails());
    }

    public function testJsonExtraDetails(): void
    {
        $log = new WorkoutLog();
        $details = ['sets' => 3, 'reps' => 12, 'weight' => 50.0];

        $log->setExtraDetails($details);

        $this->assertSame($details, $log->getExtraDetails());
    }

    public function testHealthDataPointRelation(): void
    {
        $log = new WorkoutLog();
        $healthDataPoint = new HealthDataPoint();

        $log->setHealthDataPoint($healthDataPoint);

        $this->assertSame($healthDataPoint, $log->getHealthDataPoint());
    }

    public function testSetterChaining(): void
    {
        $log = new WorkoutLog();
        $user = new User();

        $result = $log->setUser($user)
            ->setWorkoutType('cycling')
            ->setDurationMinutes(60.0)
            ->setCaloriesBurned(500.0)
            ->setDistance(20.0)
            ->setHealthDataPoint(null)
            ->setExtraDetails(['terrain' => 'flat'])
            ->setPerformedAt(new \DateTimeImmutable());

        $this->assertSame($log, $result);
    }
}
