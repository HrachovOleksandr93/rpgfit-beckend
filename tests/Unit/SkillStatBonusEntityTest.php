<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Character\Enum\StatType;
use App\Domain\Skill\Entity\Skill;
use App\Domain\Skill\Entity\SkillStatBonus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class SkillStatBonusEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $bonus = new SkillStatBonus();

        $this->assertInstanceOf(Uuid::class, $bonus->getId());
    }

    public function testSettersAndGetters(): void
    {
        $bonus = new SkillStatBonus();
        $skill = new Skill();

        $bonus->setSkill($skill);
        $bonus->setStatType(StatType::Strength);
        $bonus->setPoints(5);

        $this->assertSame($skill, $bonus->getSkill());
        $this->assertSame(StatType::Strength, $bonus->getStatType());
        $this->assertSame(5, $bonus->getPoints());
    }

    public function testAllStatTypes(): void
    {
        $bonus = new SkillStatBonus();

        foreach (StatType::cases() as $statType) {
            $bonus->setStatType($statType);
            $this->assertSame($statType, $bonus->getStatType());
        }
    }

    public function testSetterChaining(): void
    {
        $bonus = new SkillStatBonus();
        $skill = new Skill();

        $result = $bonus->setSkill($skill)
            ->setStatType(StatType::Dexterity)
            ->setPoints(3);

        $this->assertSame($bonus, $result);
    }
}
