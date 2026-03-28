<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Character\Entity\ExperienceLog;
use App\Domain\User\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ExperienceLogEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $log = new ExperienceLog();

        $this->assertInstanceOf(Uuid::class, $log->getId());
    }

    public function testEarnedAtDefaultsToNow(): void
    {
        $before = new \DateTimeImmutable();
        $log = new ExperienceLog();
        $after = new \DateTimeImmutable();

        $this->assertInstanceOf(\DateTimeImmutable::class, $log->getEarnedAt());
        $this->assertGreaterThanOrEqual($before, $log->getEarnedAt());
        $this->assertLessThanOrEqual($after, $log->getEarnedAt());
    }

    public function testSettersAndGetters(): void
    {
        $log = new ExperienceLog();
        $user = new User();
        $earnedAt = new \DateTimeImmutable('2026-01-15T10:00:00+00:00');

        $log->setUser($user);
        $log->setAmount(100);
        $log->setSource('workout');
        $log->setDescription('Completed a workout session');
        $log->setEarnedAt($earnedAt);

        $this->assertSame($user, $log->getUser());
        $this->assertSame(100, $log->getAmount());
        $this->assertSame('workout', $log->getSource());
        $this->assertSame('Completed a workout session', $log->getDescription());
        $this->assertEquals($earnedAt, $log->getEarnedAt());
    }

    public function testDescriptionIsNullable(): void
    {
        $log = new ExperienceLog();

        $log->setDescription(null);

        $this->assertNull($log->getDescription());
    }

    public function testSetterChaining(): void
    {
        $log = new ExperienceLog();
        $user = new User();

        $result = $log->setUser($user)
            ->setAmount(50)
            ->setSource('quest')
            ->setDescription('Some description')
            ->setEarnedAt(new \DateTimeImmutable());

        $this->assertSame($log, $result);
    }
}
