<?php

declare(strict_types=1);

namespace App\Domain\Training\Entity;

use App\Domain\Character\Enum\StatType;
use App\Infrastructure\Training\Repository\ExerciseStatRewardRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Configuration table: defines how many RPG stat points a specific exercise awards.
 *
 * Domain layer (Training bounded context). This is the bridge between the training
 * system and the RPG character stats system.
 *
 * Each row maps an ExerciseType to a StatType (STR/DEX/CON) with a point value.
 * For example: "Running" -> DEX +3, "Bench Press" -> STR +5.
 * One exercise can award multiple stats (e.g. "Swimming" -> STR +2, CON +3).
 *
 * Managed exclusively via the Sonata admin panel. Game designers configure these
 * values to balance the RPG progression system.
 *
 * Future: when a WorkoutLog is created, the system will look up the matching
 * ExerciseStatReward entries and apply the stat points to CharacterStats.
 */
#[ORM\Entity(repositoryClass: ExerciseStatRewardRepository::class)]
#[ORM\Table(name: 'exercise_stat_rewards')]
#[ORM\Index(name: 'idx_reward_exercise_type', columns: ['exercise_type_id'])]
class ExerciseStatReward
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: ExerciseType::class, inversedBy: 'statRewards')]
    #[ORM\JoinColumn(nullable: false)]
    private ExerciseType $exerciseType;

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

    public function getExerciseType(): ExerciseType
    {
        return $this->exerciseType;
    }

    public function setExerciseType(ExerciseType $exerciseType): self
    {
        $this->exerciseType = $exerciseType;

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
