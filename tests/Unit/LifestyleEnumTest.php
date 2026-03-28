<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\User\Enum\Lifestyle;
use PHPUnit\Framework\TestCase;

/** Unit tests for the Lifestyle enum. */
class LifestyleEnumTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $cases = Lifestyle::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(Lifestyle::Sedentary, $cases);
        $this->assertContains(Lifestyle::Moderate, $cases);
        $this->assertContains(Lifestyle::Active, $cases);
        $this->assertContains(Lifestyle::VeryActive, $cases);
    }

    public function testStringValues(): void
    {
        $this->assertSame('sedentary', Lifestyle::Sedentary->value);
        $this->assertSame('moderate', Lifestyle::Moderate->value);
        $this->assertSame('active', Lifestyle::Active->value);
        $this->assertSame('very_active', Lifestyle::VeryActive->value);
    }

    public function testFromMethod(): void
    {
        $this->assertSame(Lifestyle::Sedentary, Lifestyle::from('sedentary'));
        $this->assertSame(Lifestyle::Moderate, Lifestyle::from('moderate'));
        $this->assertSame(Lifestyle::Active, Lifestyle::from('active'));
        $this->assertSame(Lifestyle::VeryActive, Lifestyle::from('very_active'));
    }

    public function testFromMethodThrowsOnInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        Lifestyle::from('couch_potato');
    }
}
