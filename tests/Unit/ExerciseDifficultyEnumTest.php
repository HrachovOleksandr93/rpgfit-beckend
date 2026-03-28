<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Workout\Enum\ExerciseDifficulty;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ExerciseDifficulty enum.
 *
 * Verifies all 3 difficulty levels exist with correct string values.
 */
class ExerciseDifficultyEnumTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $cases = ExerciseDifficulty::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(ExerciseDifficulty::Beginner, $cases);
        $this->assertContains(ExerciseDifficulty::Intermediate, $cases);
        $this->assertContains(ExerciseDifficulty::Advanced, $cases);
    }

    public function testStringValues(): void
    {
        $this->assertSame('beginner', ExerciseDifficulty::Beginner->value);
        $this->assertSame('intermediate', ExerciseDifficulty::Intermediate->value);
        $this->assertSame('advanced', ExerciseDifficulty::Advanced->value);
    }

    public function testFromMethod(): void
    {
        $this->assertSame(ExerciseDifficulty::Beginner, ExerciseDifficulty::from('beginner'));
    }

    public function testFromMethodThrowsOnInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        ExerciseDifficulty::from('expert');
    }
}
