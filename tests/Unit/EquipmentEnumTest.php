<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Workout\Enum\Equipment;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Equipment enum.
 *
 * Verifies all 8 equipment types exist with correct string values.
 */
class EquipmentEnumTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $cases = Equipment::cases();

        $this->assertCount(8, $cases);
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
        $this->assertSame('none', Equipment::None->value);
    }

    public function testFromMethod(): void
    {
        $this->assertSame(Equipment::Barbell, Equipment::from('barbell'));
        $this->assertSame(Equipment::ResistanceBand, Equipment::from('resistance_band'));
    }

    public function testFromMethodThrowsOnInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        Equipment::from('smith_machine');
    }
}
