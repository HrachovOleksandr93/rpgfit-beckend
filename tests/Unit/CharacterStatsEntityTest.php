<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Character\Entity\CharacterStats;
use App\Domain\User\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class CharacterStatsEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $stats = new CharacterStats();

        $this->assertInstanceOf(Uuid::class, $stats->getId());
    }

    public function testDefaultsAreZero(): void
    {
        $stats = new CharacterStats();

        $this->assertSame(0, $stats->getStrength());
        $this->assertSame(0, $stats->getDexterity());
        $this->assertSame(0, $stats->getConstitution());
    }

    public function testSettersAndGetters(): void
    {
        $stats = new CharacterStats();
        $user = new User();

        $stats->setUser($user);
        $stats->setStrength(10);
        $stats->setDexterity(15);
        $stats->setConstitution(12);

        $this->assertSame($user, $stats->getUser());
        $this->assertSame(10, $stats->getStrength());
        $this->assertSame(15, $stats->getDexterity());
        $this->assertSame(12, $stats->getConstitution());
    }

    public function testSetterChaining(): void
    {
        $stats = new CharacterStats();
        $user = new User();

        $result = $stats->setUser($user)
            ->setStrength(5)
            ->setDexterity(8)
            ->setConstitution(3);

        $this->assertSame($stats, $result);
    }

    public function testUpdatedAtIsSetOnCreation(): void
    {
        $before = new \DateTimeImmutable();
        $stats = new CharacterStats();
        $after = new \DateTimeImmutable();

        $this->assertInstanceOf(\DateTimeImmutable::class, $stats->getUpdatedAt());
        $this->assertGreaterThanOrEqual($before, $stats->getUpdatedAt());
        $this->assertLessThanOrEqual($after, $stats->getUpdatedAt());
    }

    public function testUpdateTimestamp(): void
    {
        $stats = new CharacterStats();
        $original = $stats->getUpdatedAt();

        // Simulate PreUpdate callback
        usleep(1000);
        $stats->updateTimestamp();

        $this->assertGreaterThanOrEqual($original, $stats->getUpdatedAt());
    }
}
