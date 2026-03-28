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

        $this->assertCount(12, $cases);
        $this->assertContains(EquipmentSlot::Weapon, $cases);
        $this->assertContains(EquipmentSlot::Shield, $cases);
        $this->assertContains(EquipmentSlot::Head, $cases);
        $this->assertContains(EquipmentSlot::Body, $cases);
        $this->assertContains(EquipmentSlot::Legs, $cases);
        $this->assertContains(EquipmentSlot::Feet, $cases);
        $this->assertContains(EquipmentSlot::Hands, $cases);
        $this->assertContains(EquipmentSlot::Bracers, $cases);
        $this->assertContains(EquipmentSlot::Bracelet, $cases);
        $this->assertContains(EquipmentSlot::Ring, $cases);
        $this->assertContains(EquipmentSlot::Shirt, $cases);
        $this->assertContains(EquipmentSlot::Necklace, $cases);
    }

    public function testStringValues(): void
    {
        $this->assertSame('weapon', EquipmentSlot::Weapon->value);
        $this->assertSame('shield', EquipmentSlot::Shield->value);
        $this->assertSame('head', EquipmentSlot::Head->value);
        $this->assertSame('body', EquipmentSlot::Body->value);
        $this->assertSame('legs', EquipmentSlot::Legs->value);
        $this->assertSame('feet', EquipmentSlot::Feet->value);
        $this->assertSame('hands', EquipmentSlot::Hands->value);
        $this->assertSame('bracers', EquipmentSlot::Bracers->value);
        $this->assertSame('bracelet', EquipmentSlot::Bracelet->value);
        $this->assertSame('ring', EquipmentSlot::Ring->value);
        $this->assertSame('shirt', EquipmentSlot::Shirt->value);
        $this->assertSame('necklace', EquipmentSlot::Necklace->value);
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
