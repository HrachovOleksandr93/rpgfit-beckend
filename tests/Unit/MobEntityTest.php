<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Inventory\Enum\ItemRarity;
use App\Domain\Mob\Entity\Mob;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Unit tests for the Mob entity.
 *
 * Verifies UUID generation, getter/setter behaviour, setter chaining,
 * nullable fields, rarity enum assignment, and __toString output.
 */
class MobEntityTest extends TestCase
{
    /** Verify that a new Mob automatically receives a UUID v4 identifier. */
    public function testCreationGeneratesUuid(): void
    {
        $mob = new Mob();

        $this->assertInstanceOf(Uuid::class, $mob->getId());
    }

    /** Verify that createdAt is set automatically on construction. */
    public function testCreationSetsCreatedAt(): void
    {
        $before = new \DateTimeImmutable();
        $mob = new Mob();
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $mob->getCreatedAt());
        $this->assertLessThanOrEqual($after, $mob->getCreatedAt());
    }

    /** Verify all getters return the values set by their corresponding setters. */
    public function testSettersAndGetters(): void
    {
        $mob = new Mob();

        $mob->setName('Grey Wolf');
        $mob->setSlug('grey-wolf-3');
        $mob->setLevel(3);
        $mob->setHp(200);
        $mob->setXpReward(15);
        $mob->setDescription('A common wolf found in the forests');
        $mob->setRarity(ItemRarity::Common);

        $this->assertSame('Grey Wolf', $mob->getName());
        $this->assertSame('grey-wolf-3', $mob->getSlug());
        $this->assertSame(3, $mob->getLevel());
        $this->assertSame(200, $mob->getHp());
        $this->assertSame(15, $mob->getXpReward());
        $this->assertSame('A common wolf found in the forests', $mob->getDescription());
        $this->assertSame(ItemRarity::Common, $mob->getRarity());
    }

    /** Verify that all setters return $this to support fluent chaining. */
    public function testSetterChaining(): void
    {
        $mob = new Mob();

        $result = $mob->setName('Grey Wolf')
            ->setSlug('grey-wolf-3')
            ->setLevel(3)
            ->setHp(200)
            ->setXpReward(15)
            ->setDescription('A wolf')
            ->setRarity(ItemRarity::Common);

        $this->assertSame($mob, $result);
    }

    /** Verify that nullable fields (description, rarity, image) accept null values. */
    public function testNullableFields(): void
    {
        $mob = new Mob();

        $mob->setDescription(null);
        $mob->setRarity(null);
        $mob->setImage(null);

        $this->assertNull($mob->getDescription());
        $this->assertNull($mob->getRarity());
        $this->assertNull($mob->getImage());
    }

    /** Verify that all ItemRarity enum values can be assigned to a mob. */
    public function testRarityEnum(): void
    {
        $mob = new Mob();

        foreach (ItemRarity::cases() as $rarity) {
            $mob->setRarity($rarity);
            $this->assertSame($rarity, $mob->getRarity());
        }
    }

    /** Verify that __toString returns the mob name. */
    public function testToStringReturnsName(): void
    {
        $mob = new Mob();
        $mob->setName('Fire Dragon');

        $this->assertSame('Fire Dragon', (string) $mob);
    }
}
