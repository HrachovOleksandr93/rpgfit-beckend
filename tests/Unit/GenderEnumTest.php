<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\User\Enum\Gender;
use PHPUnit\Framework\TestCase;

/** Unit tests for the Gender enum. */
class GenderEnumTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $cases = Gender::cases();

        $this->assertCount(2, $cases);
        $this->assertContains(Gender::Male, $cases);
        $this->assertContains(Gender::Female, $cases);
    }

    public function testStringValues(): void
    {
        $this->assertSame('male', Gender::Male->value);
        $this->assertSame('female', Gender::Female->value);
    }

    public function testFromMethod(): void
    {
        $this->assertSame(Gender::Male, Gender::from('male'));
        $this->assertSame(Gender::Female, Gender::from('female'));
    }

    public function testFromMethodThrowsOnInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        Gender::from('invalid');
    }

    public function testTryFromReturnsNullForInvalid(): void
    {
        $this->assertNull(Gender::tryFrom('other'));
    }
}
