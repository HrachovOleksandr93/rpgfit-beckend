<?php

declare(strict_types=1);

namespace App\Domain\Skill\Entity;

use App\Domain\Character\Enum\StatType;
use App\Infrastructure\Skill\Repository\SkillStatBonusRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Defines how many RPG stat points a specific skill provides as a passive bonus.
 *
 * Domain layer (Skill bounded context). Each row maps a Skill to a StatType
 * (STR/DEX/CON) with a point value. One skill can provide bonuses to multiple stats.
 * For example: "Iron Will" -> CON +5, STR +2.
 *
 * When a user unlocks a skill (via UserSkill), the system applies these stat bonuses
 * to the character's stats. Follows the same pattern as ExerciseStatReward.
 *
 * Managed exclusively via the Sonata admin panel.
 */
#[ORM\Entity(repositoryClass: SkillStatBonusRepository::class)]
#[ORM\Table(name: 'skill_stat_bonuses')]
#[ORM\Index(name: 'idx_skill_stat_bonus_skill', columns: ['skill_id'])]
class SkillStatBonus
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Skill::class, inversedBy: 'statBonuses')]
    #[ORM\JoinColumn(nullable: false)]
    private Skill $skill;

    #[ORM\Column(type: 'string', length: 10, enumType: StatType::class)]
    private StatType $statType;

    #[ORM\Column(type: 'integer')]
    private int $points;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
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

    public function getStatType(): StatType
    {
        return $this->statType;
    }

    public function setStatType(StatType $statType): self
    {
        $this->statType = $statType;

        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): self
    {
        $this->points = $points;

        return $this;
    }
}
