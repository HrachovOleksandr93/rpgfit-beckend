<?php

declare(strict_types=1);

namespace App\Domain\Skill\Entity;

use App\Domain\User\Entity\User;
use App\Infrastructure\Skill\Repository\UserSkillRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Join entity tracking which skills a user has unlocked.
 *
 * Domain layer (Skill bounded context). Each row represents one unlocked skill
 * for a specific user, with a timestamp of when it was unlocked. A unique constraint
 * on (user_id, skill_id) prevents duplicate unlocks.
 *
 * Skills are typically unlocked by consuming scroll items from the inventory system
 * or as level-up rewards. The stat bonuses from the associated Skill entity are
 * applied to the user's CharacterStats upon unlock.
 */
#[ORM\Entity(repositoryClass: UserSkillRepository::class)]
#[ORM\Table(name: 'user_skills')]
#[ORM\UniqueConstraint(name: 'uniq_user_skill', columns: ['user_id', 'skill_id'])]
class UserSkill
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Skill::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Skill $skill;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $unlockedAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->unlockedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getSkill(): Skill
    {
        return $this->skill;
    }

    public function setSkill(Skill $skill): self
    {
        $this->skill = $skill;

        return $this;
    }

    public function getUnlockedAt(): \DateTimeImmutable
    {
        return $this->unlockedAt;
    }
}
