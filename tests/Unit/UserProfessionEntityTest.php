<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Activity\Entity\ActivityCategory;
use App\Domain\Activity\Entity\Profession;
use App\Domain\Activity\Entity\UserProfession;
use App\Domain\Character\Enum\StatType;
use App\Domain\User\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Unit tests for the UserProfession entity.
 *
 * Verifies UUID generation, getter/setter behaviour, setter chaining,
 * default values, and __toString output.
 */
class UserProfessionEntityTest extends TestCase
{
    /** Verify that a new UserProfession automatically receives a UUID v4 identifier. */
    public function testCreationGeneratesUuid(): void
    {
        $userProfession = new UserProfession();

        $this->assertInstanceOf(Uuid::class, $userProfession->getId());
    }

    /** Verify that unlockedAt is set automatically on construction. */
    public function testCreationSetsUnlockedAt(): void
    {
        $before = new \DateTimeImmutable();
        $userProfession = new UserProfession();
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $userProfession->getUnlockedAt());
        $this->assertLessThanOrEqual($after, $userProfession->getUnlockedAt());
    }

    /** Verify that active defaults to true on construction. */
    public function testDefaultActiveIsTrue(): void
    {
        $userProfession = new UserProfession();

        $this->assertTrue($userProfession->isActive());
    }

    /** Verify all getters return the values set by their corresponding setters. */
    public function testSettersAndGetters(): void
    {
        $userProfession = new UserProfession();
        $user = new User();
        $profession = $this->createProfession();
        $unlockedAt = new \DateTimeImmutable('2025-06-01');

        $userProfession->setUser($user);
        $userProfession->setProfession($profession);
        $userProfession->setUnlockedAt($unlockedAt);
        $userProfession->setActive(false);

        $this->assertSame($user, $userProfession->getUser());
        $this->assertSame($profession, $userProfession->getProfession());
        $this->assertSame($unlockedAt, $userProfession->getUnlockedAt());
        $this->assertFalse($userProfession->isActive());
    }

    /** Verify that all setters return $this to support fluent chaining. */
    public function testSetterChaining(): void
    {
        $userProfession = new UserProfession();
        $user = new User();
        $profession = $this->createProfession();

        $result = $userProfession->setUser($user)
            ->setProfession($profession)
            ->setUnlockedAt(new \DateTimeImmutable())
            ->setActive(true);

        $this->assertSame($userProfession, $result);
    }

    /** Verify that __toString delegates to the profession's __toString. */
    public function testToStringReturnsProfessionName(): void
    {
        $userProfession = new UserProfession();
        $profession = $this->createProfession();
        $userProfession->setProfession($profession);

        $this->assertSame('Gladiator', (string) $userProfession);
    }

    /** Helper to create a minimal Profession entity for testing. */
    private function createProfession(): Profession
    {
        $category = new ActivityCategory();
        $category->setName('Combat')->setSlug('combat');

        $profession = new Profession();
        $profession->setName('Gladiator')
            ->setSlug('gladiator')
            ->setTier(2)
            ->setPrimaryStat(StatType::Strength)
            ->setSecondaryStat(StatType::Constitution)
            ->setCategory($category);

        return $profession;
    }
}
