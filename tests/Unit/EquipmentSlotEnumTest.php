<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Inventory\Enum\EquipmentSlot;
use PHPUnit\Framework\TestCase;

class EquipmentSlotEnumTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $cases = EquipmentSlot::cases();

        $this->assertCount(8, $cases);
        $this->assertContains(EquipmentSlot::Head, $cases);
        $this->assertContains(EquipmentSlot::Body, $cases);
        $this->assertContains(EquipmentSlot::Legs, $cases);
        $this->assertContains(EquipmentSlot::Feet, $cases);
        $this->assertContains(EquipmentSlot::Hands, $cases);
        $this->assertContains(EquipmentSlot::Weapon, $cases);
        $this->assertContains(EquipmentSlot::Shield, $cases);
        $this->assertContains(EquipmentSlot::Accessory, $cases);
    }

    public function testStringValues(): void
    {
        $this->assertSame('head', EquipmentSlot::Head->value);
        $this->assertSame('body', EquipmentSlot::Body->value);
        $this->assertSame('legs', EquipmentSlot::Legs->value);
        $this->assertSame('feet', EquipmentSlot::Feet->value);
        $this->assertSame('hands', EquipmentSlot::Hands->value);
        $this->assertSame('weapon', EquipmentSlot::Weapon->value);
        $this->assertSame('shield', EquipmentSlot::Shield->value);
        $this->assertSame('accessory', EquipmentSlot::Accessory->value);
    }

    public function testFromMethod(): void
    {
        foreach (EquipmentSlot::cases() as $slot) {
            $this->assertSame($slot, EquipmentSlot::from($slot->value));
        }
    }

    public function testFromMethodThrowsOnInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        EquipmentSlot::from('invalid');
    }
}
