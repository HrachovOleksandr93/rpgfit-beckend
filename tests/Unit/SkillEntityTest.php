<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Character\Enum\StatType;
use App\Domain\Skill\Entity\Skill;
use App\Domain\Skill\Entity\SkillStatBonus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/** Unit tests for the Skill entity including new fields for types, cooldowns, and race/profession links. */
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
            ->setRequiredLevel(3)
            ->setSkillType('active')
            ->setDuration(30)
            ->setCooldown(60)
            ->setTier(1)
            ->setIsUniversal(true)
            ->setIsRaceSkill(false)
            ->setRaceRestriction('human');

        $this->assertSame($skill, $result);
    }

    /** Test default values for the new skill type field. */
    public function testDefaultSkillType(): void
    {
        $skill = new Skill();

        $this->assertSame('passive', $skill->getSkillType());
    }

    /** Test setting and getting the skill type. */
    public function testSkillTypeSetterAndGetter(): void
    {
        $skill = new Skill();

        $skill->setSkillType('active');
        $this->assertSame('active', $skill->getSkillType());

        $skill->setSkillType('passive');
        $this->assertSame('passive', $skill->getSkillType());
    }

    /** Test duration field for active skills. */
    public function testDurationField(): void
    {
        $skill = new Skill();

        $this->assertNull($skill->getDuration());

        $skill->setDuration(60);
        $this->assertSame(60, $skill->getDuration());

        $skill->setDuration(null);
        $this->assertNull($skill->getDuration());
    }

    /** Test cooldown field for active skills. */
    public function testCooldownField(): void
    {
        $skill = new Skill();

        $this->assertNull($skill->getCooldown());

        $skill->setCooldown(120);
        $this->assertSame(120, $skill->getCooldown());

        $skill->setCooldown(null);
        $this->assertNull($skill->getCooldown());
    }

    /** Test legacy race restriction string field (races removed 2026-04-18). */
    public function testRaceRestrictionField(): void
    {
        $skill = new Skill();

        $this->assertNull($skill->getRaceRestriction());

        $skill->setRaceRestriction('orc');
        $this->assertSame('orc', $skill->getRaceRestriction());

        $skill->setRaceRestriction(null);
        $this->assertNull($skill->getRaceRestriction());
    }

    /** Test tier field for profession skills. */
    public function testTierField(): void
    {
        $skill = new Skill();

        $this->assertNull($skill->getTier());

        $skill->setTier(2);
        $this->assertSame(2, $skill->getTier());

        $skill->setTier(null);
        $this->assertNull($skill->getTier());
    }

    /** Test default value and setter for the isUniversal flag. */
    public function testIsUniversalField(): void
    {
        $skill = new Skill();

        $this->assertFalse($skill->getIsUniversal());

        $skill->setIsUniversal(true);
        $this->assertTrue($skill->getIsUniversal());
    }

    /** Test default value and setter for the isRaceSkill flag. */
    public function testIsRaceSkillField(): void
    {
        $skill = new Skill();

        $this->assertFalse($skill->getIsRaceSkill());

        $skill->setIsRaceSkill(true);
        $this->assertTrue($skill->getIsRaceSkill());
    }
}
