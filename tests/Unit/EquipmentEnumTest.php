<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Workout\Enum\Equipment;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Equipment enum.
 *
 * Verifies all 16 equipment types exist with correct string values.
 */
class EquipmentEnumTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $cases = Equipment::cases();

        $this->assertCount(16, $cases);
    }

    public function testStringValues(): void
    {
        $this->assertSame('barbell', Equipment::Barbell->value);
        $this->assertSame('dumbbell', Equipment::Dumbbell->value);
        $this->assertSame('cable', Equipment::Cable->value);
        $this->assertSame('machine', Equipment::Machine->value);
        $this->assertSame('bodyweight', Equipment::Bodyweight->value);
        $this->assertSame('kettlebell', Equipment::Kettlebell->value);
        $this->assertSame('resistance_band', Equipment::ResistanceBand->value);
        $this->assertSame('no_equipment', Equipment::NoEquipment->value);
        $this->assertSame('mat', Equipment::Mat->value);
        $this->assertSame('pool', Equipment::Pool->value);
        $this->assertSame('bike', Equipment::Bike->value);
        $this->assertSame('rowing_machine', Equipment::RowingMachine->value);
        $this->assertSame('jump_rope', Equipment::JumpRope->value);
        $this->assertSame('punching_bag', Equipment::PunchingBag->value);
        $this->assertSame('racquet', Equipment::Racquet->value);
        $this->assertSame('outdoor', Equipment::Outdoor->value);
    }

    public function testFromMethod(): void
    {
        $this->assertSame(Equipment::Barbell, Equipment::from('barbell'));
        $this->assertSame(Equipment::ResistanceBand, Equipment::from('resistance_band'));
        $this->assertSame(Equipment::NoEquipment, Equipment::from('no_equipment'));
        $this->assertSame(Equipment::Pool, Equipment::from('pool'));
        $this->assertSame(Equipment::RowingMachine, Equipment::from('rowing_machine'));
    }

    public function testFromMethodThrowsOnInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        Equipment::from('smith_machine');
    }
}
