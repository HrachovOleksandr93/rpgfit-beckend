<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Workout\Enum\WorkoutPlanStatus;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the WorkoutPlanStatus enum.
 *
 * Verifies all 4 plan status values exist with correct string values.
 */
class WorkoutPlanStatusEnumTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $cases = WorkoutPlanStatus::cases();

        $this->assertCount(4, $cases);
    }

    public function testStringValues(): void
    {
        $this->assertSame('pending', WorkoutPlanStatus::Pending->value);
        $this->assertSame('in_progress', WorkoutPlanStatus::InProgress->value);
        $this->assertSame('completed', WorkoutPlanStatus::Completed->value);
        $this->assertSame('skipped', WorkoutPlanStatus::Skipped->value);
    }

    public function testFromMethod(): void
    {
        $this->assertSame(WorkoutPlanStatus::InProgress, WorkoutPlanStatus::from('in_progress'));
    }

    public function testFromMethodThrowsOnInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        WorkoutPlanStatus::from('cancelled');
    }
}
