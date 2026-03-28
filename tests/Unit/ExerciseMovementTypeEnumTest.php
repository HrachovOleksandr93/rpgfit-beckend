<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Workout\Enum\ExerciseMovementType;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ExerciseMovementType enum.
 *
 * Verifies compound and isolation movement types exist with correct string values.
 */
class ExerciseMovementTypeEnumTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $cases = ExerciseMovementType::cases();

        $this->assertCount(2, $cases);
        $this->assertContains(ExerciseMovementType::Compound, $cases);
        $this->assertContains(ExerciseMovementType::Isolation, $cases);
    }

    public function testStringValues(): void
    {
        $this->assertSame('compound', ExerciseMovementType::Compound->value);
        $this->assertSame('isolation', ExerciseMovementType::Isolation->value);
    }

    public function testFromMethodThrowsOnInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        ExerciseMovementType::from('hybrid');
    }
}
