<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Workout\Enum\MuscleGroup;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the MuscleGroup enum.
 *
 * Verifies all 10 muscle group cases exist with correct string values.
 */
class MuscleGroupEnumTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $cases = MuscleGroup::cases();

        $this->assertCount(10, $cases);
    }

    public function testStringValues(): void
    {
        $this->assertSame('chest', MuscleGroup::Chest->value);
        $this->assertSame('back', MuscleGroup::Back->value);
        $this->assertSame('shoulders', MuscleGroup::Shoulders->value);
        $this->assertSame('biceps', MuscleGroup::Biceps->value);
        $this->assertSame('triceps', MuscleGroup::Triceps->value);
        $this->assertSame('quads', MuscleGroup::Quads->value);
        $this->assertSame('hamstrings', MuscleGroup::Hamstrings->value);
        $this->assertSame('glutes', MuscleGroup::Glutes->value);
        $this->assertSame('calves', MuscleGroup::Calves->value);
        $this->assertSame('core', MuscleGroup::Core->value);
    }

    public function testFromMethod(): void
    {
        $this->assertSame(MuscleGroup::Chest, MuscleGroup::from('chest'));
        $this->assertSame(MuscleGroup::Back, MuscleGroup::from('back'));
    }

    public function testFromMethodThrowsOnInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        MuscleGroup::from('invalid');
    }
}
