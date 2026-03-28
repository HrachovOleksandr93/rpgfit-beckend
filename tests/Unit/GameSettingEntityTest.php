<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Config\Entity\GameSetting;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Unit tests for the GameSetting entity.
 */
class GameSettingEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $setting = new GameSetting();

        $this->assertInstanceOf(Uuid::class, $setting->getId());
    }

    public function testSettersAndGetters(): void
    {
        $setting = new GameSetting();

        $setting->setCategory('xp_rates');
        $setting->setKey('xp_rate_steps');
        $setting->setValue('10');
        $setting->setDescription('XP per 1,000 steps');

        $this->assertSame('xp_rates', $setting->getCategory());
        $this->assertSame('xp_rate_steps', $setting->getKey());
        $this->assertSame('10', $setting->getValue());
        $this->assertSame('XP per 1,000 steps', $setting->getDescription());
    }

    public function testDescriptionIsNullable(): void
    {
        $setting = new GameSetting();

        $setting->setDescription(null);

        $this->assertNull($setting->getDescription());
    }

    public function testToStringReturnsKey(): void
    {
        $setting = new GameSetting();
        $setting->setKey('level_max');

        $this->assertSame('level_max', (string) $setting);
    }

    public function testSetterChaining(): void
    {
        $setting = new GameSetting();

        $result = $setting
            ->setCategory('leveling')
            ->setKey('level_max')
            ->setValue('100')
            ->setDescription('Maximum level');

        $this->assertSame($setting, $result);
    }
}
