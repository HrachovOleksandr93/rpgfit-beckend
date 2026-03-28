<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Inventory\Enum\ItemRarity;
use PHPUnit\Framework\TestCase;

class ItemRarityEnumTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $cases = ItemRarity::cases();

        $this->assertCount(5, $cases);
        $this->assertContains(ItemRarity::Common, $cases);
        $this->assertContains(ItemRarity::Uncommon, $cases);
        $this->assertContains(ItemRarity::Rare, $cases);
        $this->assertContains(ItemRarity::Epic, $cases);
        $this->assertContains(ItemRarity::Legendary, $cases);
    }

    public function testStringValues(): void
    {
        $this->assertSame('common', ItemRarity::Common->value);
        $this->assertSame('uncommon', ItemRarity::Uncommon->value);
        $this->assertSame('rare', ItemRarity::Rare->value);
        $this->assertSame('epic', ItemRarity::Epic->value);
        $this->assertSame('legendary', ItemRarity::Legendary->value);
    }

    public function testFromMethod(): void
    {
        $this->assertSame(ItemRarity::Common, ItemRarity::from('common'));
        $this->assertSame(ItemRarity::Uncommon, ItemRarity::from('uncommon'));
        $this->assertSame(ItemRarity::Rare, ItemRarity::from('rare'));
        $this->assertSame(ItemRarity::Epic, ItemRarity::from('epic'));
        $this->assertSame(ItemRarity::Legendary, ItemRarity::from('legendary'));
    }

    public function testFromMethodThrowsOnInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        ItemRarity::from('invalid');
    }
}
