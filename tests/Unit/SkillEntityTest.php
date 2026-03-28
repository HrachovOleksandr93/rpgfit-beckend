<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Character\Enum\StatType;
use App\Domain\Skill\Entity\Skill;
use App\Domain\Skill\Entity\SkillStatBonus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class SkillEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $skill = new Skill();

        $this->assertInstanceOf(Uuid::class, $skill->getId());
    }

    public function testSettersAndGetters(): void
    {
        $skill = new Skill();

        $skill->setName('Fireball');
        $skill->setSlug('fireball');
        $skill->setDescription('A powerful fire spell');
        $skill->setIcon('fireball.png');
        $skill->setRequiredLevel(5);

        $this->assertSame('Fireball', $skill->getName());
        $this->assertSame('fireball', $skill->getSlug());
        $this->assertSame('A powerful fire spell', $skill->getDescription());
        $this->assertSame('fireball.png', $skill->getIcon());
        $this->assertSame(5, $skill->getRequiredLevel());
    }

    public function testDefaultRequiredLevel(): void
    {
        $skill = new Skill();

        $this->assertSame(1, $skill->getRequiredLevel());
    }

    public function testDescriptionIsNullable(): void
    {
        $skill = new Skill();

        $skill->setDescription(null);

        $this->assertNull($skill->getDescription());
    }

    public function testIconIsNullable(): void
    {
        $skill = new Skill();

        $skill->setIcon(null);

        $this->assertNull($skill->getIcon());
    }

    public function testToStringReturnsName(): void
    {
        $skill = new Skill();
        $skill->setName('Fireball');

        $this->assertSame('Fireball', (string) $skill);
    }

    public function testStatBonusesCollection(): void
    {
        $skill = new Skill();

        $this->assertCount(0, $skill->getStatBonuses());

        $bonus = new SkillStatBonus();
        $bonus->setStatType(StatType::Strength);
        $bonus->setPoints(3);
        $skill->addStatBonus($bonus);

        $this->assertCount(1, $skill->getStatBonuses());
        $this->assertTrue($skill->getStatBonuses()->contains($bonus));
        $this->assertSame($skill, $bonus->getSkill());

        $skill->removeStatBonus($bonus);

        $this->assertCount(0, $skill->getStatBonuses());
    }

    public function testSetterChaining(): void
    {
        $skill = new Skill();

        $result = $skill->setName('Fireball')
            ->setSlug('fireball')
            ->setDescription('Fire spell')
            ->setIcon('icon.png')
            ->setRequiredLevel(3);

        $this->assertSame($skill, $result);
    }
}
