<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Character\Enum\StatType;
use App\Domain\Inventory\Entity\ItemCatalog;
use App\Domain\Inventory\Entity\ItemStatBonus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ItemStatBonusEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $bonus = new ItemStatBonus();

        $this->assertInstanceOf(Uuid::class, $bonus->getId());
    }

    public function testSettersAndGetters(): void
    {
        $bonus = new ItemStatBonus();
        $item = new ItemCatalog();

        $bonus->setItemCatalog($item);
        $bonus->setStatType(StatType::Strength);
        $bonus->setPoints(5);

        $this->assertSame($item, $bonus->getItemCatalog());
        $this->assertSame(StatType::Strength, $bonus->getStatType());
        $this->assertSame(5, $bonus->getPoints());
    }

    public function testAllStatTypes(): void
    {
        $bonus = new ItemStatBonus();

        foreach (StatType::cases() as $statType) {
            $bonus->setStatType($statType);
            $this->assertSame($statType, $bonus->getStatType());
        }
    }

    public function testSetterChaining(): void
    {
        $bonus = new ItemStatBonus();
        $item = new ItemCatalog();

        $result = $bonus->setItemCatalog($item)
            ->setStatType(StatType::Constitution)
            ->setPoints(7);

        $this->assertSame($bonus, $result);
    }
}
