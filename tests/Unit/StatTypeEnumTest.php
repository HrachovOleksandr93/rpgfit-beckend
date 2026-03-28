<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Character\Enum\StatType;
use PHPUnit\Framework\TestCase;

class StatTypeEnumTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $cases = StatType::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(StatType::Strength, $cases);
        $this->assertContains(StatType::Constitution, $cases);
        $this->assertContains(StatType::Dexterity, $cases);
    }

    public function testStringValues(): void
    {
        $this->assertSame('str', StatType::Strength->value);
        $this->assertSame('con', StatType::Constitution->value);
        $this->assertSame('dex', StatType::Dexterity->value);
    }

    public function testFromMethod(): void
    {
        $this->assertSame(StatType::Strength, StatType::from('str'));
        $this->assertSame(StatType::Constitution, StatType::from('con'));
        $this->assertSame(StatType::Dexterity, StatType::from('dex'));
    }

    public function testFromMethodThrowsOnInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        StatType::from('invalid');
    }
}
