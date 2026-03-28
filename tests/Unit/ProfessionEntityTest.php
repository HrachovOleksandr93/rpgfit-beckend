<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Activity\Entity\ActivityCategory;
use App\Domain\Activity\Entity\Profession;
use App\Domain\Character\Enum\StatType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Unit tests for the Profession entity.
 *
 * Verifies UUID generation, getter/setter behaviour, setter chaining,
 * nullable fields, stat enum assignment, and __toString output.
 */
class ProfessionEntityTest extends TestCase
{
    /** Verify that a new Profession automatically receives a UUID v4 identifier. */
    public function testCreationGeneratesUuid(): void
    {
        $profession = new Profession();

        $this->assertInstanceOf(Uuid::class, $profession->getId());
    }

    /** Verify all getters return the values set by their corresponding setters. */
    public function testSettersAndGetters(): void
    {
        $profession = new Profession();
        $category = new ActivityCategory();
        $category->setName('Combat')->setSlug('combat');

        $profession->setName('Gladiator');
        $profession->setSlug('gladiator');
        $profession->setTier(2);
        $profession->setDescription('A seasoned arena combatant.');
        $profession->setPrimaryStat(StatType::Strength);
        $profession->setSecondaryStat(StatType::Constitution);
        $profession->setCategory($category);

        $this->assertSame('Gladiator', $profession->getName());
        $this->assertSame('gladiator', $profession->getSlug());
        $this->assertSame(2, $profession->getTier());
        $this->assertSame('A seasoned arena combatant.', $profession->getDescription());
        $this->assertSame(StatType::Strength, $profession->getPrimaryStat());
        $this->assertSame(StatType::Constitution, $profession->getSecondaryStat());
        $this->assertSame($category, $profession->getCategory());
    }

    /** Verify that all setters return $this to support fluent chaining. */
    public function testSetterChaining(): void
    {
        $profession = new Profession();
        $category = new ActivityCategory();
        $category->setName('Combat')->setSlug('combat');

        $result = $profession->setName('Fighter')
            ->setSlug('fighter')
            ->setTier(1)
            ->setDescription('A raw recruit.')
            ->setPrimaryStat(StatType::Strength)
            ->setSecondaryStat(StatType::Constitution)
            ->setCategory($category);

        $this->assertSame($profession, $result);
    }

    /** Verify that nullable fields (description, image) accept null values. */
    public function testNullableFields(): void
    {
        $profession = new Profession();

        $profession->setDescription(null);
        $profession->setImage(null);

        $this->assertNull($profession->getDescription());
        $this->assertNull($profession->getImage());
    }

    /** Verify that all StatType enum values can be assigned as primary and secondary stats. */
    public function testStatEnums(): void
    {
        $profession = new Profession();

        foreach (StatType::cases() as $stat) {
            $profession->setPrimaryStat($stat);
            $this->assertSame($stat, $profession->getPrimaryStat());

            $profession->setSecondaryStat($stat);
            $this->assertSame($stat, $profession->getSecondaryStat());
        }
    }

    /** Verify that all three tier values (1, 2, 3) can be assigned. */
    public function testTierValues(): void
    {
        $profession = new Profession();

        foreach ([1, 2, 3] as $tier) {
            $profession->setTier($tier);
            $this->assertSame($tier, $profession->getTier());
        }
    }

    /** Verify that __toString returns the profession name. */
    public function testToStringReturnsName(): void
    {
        $profession = new Profession();
        $profession->setName('Wind Rider');

        $this->assertSame('Wind Rider', (string) $profession);
    }
}
