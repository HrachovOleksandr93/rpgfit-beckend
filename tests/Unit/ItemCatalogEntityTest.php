<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Character\Enum\StatType;
use App\Domain\Inventory\Entity\ItemCatalog;
use App\Domain\Inventory\Entity\ItemStatBonus;
use App\Domain\Inventory\Enum\EquipmentSlot;
use App\Domain\Inventory\Enum\ItemRarity;
use App\Domain\Inventory\Enum\ItemType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ItemCatalogEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $item = new ItemCatalog();

        $this->assertInstanceOf(Uuid::class, $item->getId());
    }

    public function testSettersAndGetters(): void
    {
        $item = new ItemCatalog();

        $item->setName('Iron Sword');
        $item->setSlug('iron-sword');
        $item->setDescription('A basic iron sword');
        $item->setItemType(ItemType::Equipment);
        $item->setRarity(ItemRarity::Common);
        $item->setIcon('iron-sword.png');
        $item->setSlot(EquipmentSlot::Weapon);
        $item->setDurability(100);
        $item->setDuration(null);
        $item->setStackable(false);
        $item->setMaxStack(1);

        $this->assertSame('Iron Sword', $item->getName());
        $this->assertSame('iron-sword', $item->getSlug());
        $this->assertSame('A basic iron sword', $item->getDescription());
        $this->assertSame(ItemType::Equipment, $item->getItemType());
        $this->assertSame(ItemRarity::Common, $item->getRarity());
        $this->assertSame('iron-sword.png', $item->getIcon());
        $this->assertSame(EquipmentSlot::Weapon, $item->getSlot());
        $this->assertSame(100, $item->getDurability());
        $this->assertNull($item->getDuration());
        $this->assertFalse($item->isStackable());
        $this->assertSame(1, $item->getMaxStack());
    }

    public function testDefaultValues(): void
    {
        $item = new ItemCatalog();

        $this->assertFalse($item->isStackable());
        $this->assertSame(1, $item->getMaxStack());
    }

    public function testNullableFields(): void
    {
        $item = new ItemCatalog();

        $item->setDescription(null);
        $item->setIcon(null);
        $item->setSlot(null);
        $item->setDurability(null);
        $item->setDuration(null);

        $this->assertNull($item->getDescription());
        $this->assertNull($item->getIcon());
        $this->assertNull($item->getSlot());
        $this->assertNull($item->getDurability());
        $this->assertNull($item->getDuration());
    }

    public function testToStringReturnsName(): void
    {
        $item = new ItemCatalog();
        $item->setName('Iron Sword');

        $this->assertSame('Iron Sword', (string) $item);
    }

    public function testStatBonusesCollection(): void
    {
        $item = new ItemCatalog();

        $this->assertCount(0, $item->getStatBonuses());

        $bonus = new ItemStatBonus();
        $bonus->setStatType(StatType::Strength);
        $bonus->setPoints(5);
        $item->addStatBonus($bonus);

        $this->assertCount(1, $item->getStatBonuses());
        $this->assertTrue($item->getStatBonuses()->contains($bonus));
        $this->assertSame($item, $bonus->getItemCatalog());

        $item->removeStatBonus($bonus);

        $this->assertCount(0, $item->getStatBonuses());
    }

    public function testSetterChaining(): void
    {
        $item = new ItemCatalog();

        $result = $item->setName('Iron Sword')
            ->setSlug('iron-sword')
            ->setDescription('A sword')
            ->setItemType(ItemType::Equipment)
            ->setRarity(ItemRarity::Common)
            ->setIcon('icon.png')
            ->setSlot(EquipmentSlot::Weapon)
            ->setDurability(100)
            ->setDuration(null)
            ->setStackable(false)
            ->setMaxStack(1);

        $this->assertSame($item, $result);
    }

    public function testConsumableItem(): void
    {
        $item = new ItemCatalog();

        $item->setItemType(ItemType::Potion);
        $item->setRarity(ItemRarity::Uncommon);
        $item->setSlot(null);
        $item->setDurability(null);
        $item->setDuration(30);
        $item->setStackable(true);
        $item->setMaxStack(10);

        $this->assertSame(ItemType::Potion, $item->getItemType());
        $this->assertNull($item->getSlot());
        $this->assertNull($item->getDurability());
        $this->assertSame(30, $item->getDuration());
        $this->assertTrue($item->isStackable());
        $this->assertSame(10, $item->getMaxStack());
    }
}
