<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\User\Enum\TrainingFrequency;
use PHPUnit\Framework\TestCase;

/** Unit tests for the TrainingFrequency enum. */
class TrainingFrequencyEnumTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $cases = TrainingFrequency::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(TrainingFrequency::None, $cases);
        $this->assertContains(TrainingFrequency::Light, $cases);
        $this->assertContains(TrainingFrequency::Moderate, $cases);
        $this->assertContains(TrainingFrequency::Heavy, $cases);
    }

    public function testStringValues(): void
    {
        $this->assertSame('none', TrainingFrequency::None->value);
        $this->assertSame('light', TrainingFrequency::Light->value);
        $this->assertSame('moderate', TrainingFrequency::Moderate->value);
        $this->assertSame('heavy', TrainingFrequency::Heavy->value);
    }

    public function testFromMethod(): void
    {
        $this->assertSame(TrainingFrequency::None, TrainingFrequency::from('none'));
        $this->assertSame(TrainingFrequency::Light, TrainingFrequency::from('light'));
        $this->assertSame(TrainingFrequency::Moderate, TrainingFrequency::from('moderate'));
        $this->assertSame(TrainingFrequency::Heavy, TrainingFrequency::from('heavy'));
    }

    public function testFromMethodThrowsOnInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        TrainingFrequency::from('extreme');
    }
}
