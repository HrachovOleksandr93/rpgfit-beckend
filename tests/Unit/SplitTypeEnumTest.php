<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Workout\Enum\SplitType;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the SplitType enum.
 *
 * Verifies all 5 training split types exist with correct string values.
 */
class SplitTypeEnumTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $cases = SplitType::cases();

        $this->assertCount(5, $cases);
    }

    public function testStringValues(): void
    {
        $this->assertSame('full_body', SplitType::FullBody->value);
        $this->assertSame('push_pull_legs', SplitType::PushPullLegs->value);
        $this->assertSame('upper_lower', SplitType::UpperLower->value);
        $this->assertSame('bro_split', SplitType::BroSplit->value);
        $this->assertSame('custom', SplitType::Custom->value);
    }

    public function testFromMethod(): void
    {
        $this->assertSame(SplitType::PushPullLegs, SplitType::from('push_pull_legs'));
    }

    public function testFromMethodThrowsOnInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        SplitType::from('invalid');
    }
}
