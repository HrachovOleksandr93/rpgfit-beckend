<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Activity\Entity\Profession;
use App\Domain\Activity\Entity\ProfessionSkill;
use App\Domain\Skill\Entity\Skill;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/** Unit tests for the ProfessionSkill junction entity. */
class ProfessionSkillEntityTest extends TestCase
{
    /** Test that construction generates a UUID. */
    public function testCreationGeneratesUuid(): void
    {
        $professionSkill = new ProfessionSkill();

        $this->assertInstanceOf(Uuid::class, $professionSkill->getId());
    }

    /** Test profession setter and getter. */
    public function testProfessionSetterAndGetter(): void
    {
        $professionSkill = new ProfessionSkill();
        $profession = new Profession();
        $profession->setName('Fighter');

        $result = $professionSkill->setProfession($profession);

        $this->assertSame($profession, $professionSkill->getProfession());
        $this->assertSame($professionSkill, $result);
    }

    /** Test skill setter and getter. */
    public function testSkillSetterAndGetter(): void
    {
        $professionSkill = new ProfessionSkill();
        $skill = new Skill();
        $skill->setName('Power Strike');

        $result = $professionSkill->setSkill($skill);

        $this->assertSame($skill, $professionSkill->getSkill());
        $this->assertSame($professionSkill, $result);
    }

    /** Test toString returns profession-skill combination. */
    public function testToStringReturnsCombination(): void
    {
        $professionSkill = new ProfessionSkill();

        $profession = new Profession();
        $profession->setName('Fighter');
        $professionSkill->setProfession($profession);

        $skill = new Skill();
        $skill->setName('Power Strike');
        $professionSkill->setSkill($skill);

        $this->assertSame('Fighter - Power Strike', (string) $professionSkill);
    }
}
