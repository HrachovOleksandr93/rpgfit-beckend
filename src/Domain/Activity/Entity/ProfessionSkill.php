<?php

declare(strict_types=1);

namespace App\Domain\Activity\Entity;

use App\Domain\Skill\Entity\Skill;
use App\Infrastructure\Activity\Repository\ProfessionSkillRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Junction entity linking a Profession to a Skill.
 *
 * Domain layer (Activity bounded context). Each row represents one skill
 * assigned to a profession. A unique constraint on (profession_id, skill_id)
 * prevents duplicate assignments. Skills may be shared across multiple professions.
 */
#[ORM\Entity(repositoryClass: ProfessionSkillRepository::class)]
#[ORM\Table(name: 'profession_skills')]
#[ORM\UniqueConstraint(name: 'uniq_profession_skill', columns: ['profession_id', 'skill_id'])]
class ProfessionSkill
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    /** The profession this skill is assigned to */
    #[ORM\ManyToOne(targetEntity: Profession::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Profession $profession;

    /** The skill assigned to the profession */
    #[ORM\ManyToOne(targetEntity: Skill::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Skill $skill;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getProfession(): Profession
    {
        return $this->profession;
    }

    public function setProfession(Profession $profession): self
    {
        $this->profession = $profession;

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

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->profession, $this->skill);
    }
}
