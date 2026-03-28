<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Skill\Entity\Skill;
use App\Domain\Skill\Entity\UserSkill;
use App\Domain\User\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class UserSkillEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $userSkill = new UserSkill();

        $this->assertInstanceOf(Uuid::class, $userSkill->getId());
    }

    public function testDefaultValues(): void
    {
        $userSkill = new UserSkill();

        $this->assertInstanceOf(\DateTimeImmutable::class, $userSkill->getUnlockedAt());
    }

    public function testSettersAndGetters(): void
    {
        $userSkill = new UserSkill();
        $user = new User();
        $skill = new Skill();

        $userSkill->setUser($user);
        $userSkill->setSkill($skill);

        $this->assertSame($user, $userSkill->getUser());
        $this->assertSame($skill, $userSkill->getSkill());
    }

    public function testSetterChaining(): void
    {
        $userSkill = new UserSkill();
        $user = new User();
        $skill = new Skill();

        $result = $userSkill->setUser($user)
            ->setSkill($skill);

        $this->assertSame($userSkill, $result);
    }
}
