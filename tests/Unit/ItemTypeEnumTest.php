<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Inventory\Enum\ItemType;
use PHPUnit\Framework\TestCase;

class ItemTypeEnumTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $cases = ItemType::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(ItemType::Equipment, $cases);
        $this->assertContains(ItemType::Scroll, $cases);
        $this->assertContains(ItemType::Potion, $cases);
    }

    public function testStringValues(): void
    {
        $this->assertSame('equipment', ItemType::Equipment->value);
        $this->assertSame('scroll', ItemType::Scroll->value);
        $this->assertSame('potion', ItemType::Potion->value);
    }

    public function testFromMethod(): void
    {
        $this->assertSame(ItemType::Equipment, ItemType::from('equipment'));
        $this->assertSame(ItemType::Scroll, ItemType::from('scroll'));
        $this->assertSame(ItemType::Potion, ItemType::from('potion'));
    }

    public function testFromMethodThrowsOnInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        ItemType::from('invalid');
    }
}
