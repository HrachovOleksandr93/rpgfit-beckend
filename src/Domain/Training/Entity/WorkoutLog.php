<?php

declare(strict_types=1);

namespace App\Domain\Training\Entity;

use App\Domain\Health\Entity\HealthDataPoint;
use App\Domain\User\Entity\User;
use App\Infrastructure\Training\Repository\WorkoutLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Records a single training session performed by a user.
 *
 * Domain layer (Training bounded context). Each row is one workout session with
 * its type, duration, calories burned, and optional distance.
 *
 * Data sources:
 * - Automatically created from health data synced via Apple HealthKit / Google Health Connect
 *   (linked via the healthDataPoint FK to the original HealthDataPoint record)
 * - Can also be manually entered via the admin panel
 *
 * The extraDetails JSON field stores any additional metadata that doesn't fit
 * the structured columns (e.g. heart rate zones, specific exercise details).
 *
 * Future: completing a workout will trigger XP gain (ExperienceLog) and stat
 * increases (CharacterStats) based on the ExerciseStatReward configuration.
 *
 * Indexed by (user_id, performed_at) for efficient user workout history queries.
 */
#[ORM\Entity(repositoryClass: WorkoutLogRepository::class)]
#[ORM\Table(name: 'workout_logs')]
#[ORM\Index(name: 'idx_workout_user_performed', columns: ['user_id', 'performed_at'])]
class WorkoutLog
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 100)]
    private string $workoutType;

    #[ORM\Column(type: 'float')]
    private float $durationMinutes;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $caloriesBurned = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $distance = null;

    #[ORM\ManyToOne(targetEntity: HealthDataPoint::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?HealthDataPoint $healthDataPoint = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $extraDetails = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $performedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
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

    public function getWorkoutType(): string
    {
        return $this->workoutType;
    }

    public function setWorkoutType(string $workoutType): self
    {
        $this->workoutType = $workoutType;

        return $this;
    }

    public function getDurationMinutes(): float
    {
        return $this->durationMinutes;
    }

    public function setDurationMinutes(float $durationMinutes): self
    {
        $this->durationMinutes = $durationMinutes;

        return $this;
    }

    public function getCaloriesBurned(): ?float
    {
        return $this->caloriesBurned;
    }

    public function setCaloriesBurned(?float $caloriesBurned): self
    {
        $this->caloriesBurned = $caloriesBurned;

        return $this;
    }

    public function getDistance(): ?float
    {
        return $this->distance;
    }

    public function setDistance(?float $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    public function getHealthDataPoint(): ?HealthDataPoint
    {
        return $this->healthDataPoint;
    }

    public function setHealthDataPoint(?HealthDataPoint $healthDataPoint): self
    {
        $this->healthDataPoint = $healthDataPoint;

        return $this;
    }

    public function getExtraDetails(): ?array
    {
        return $this->extraDetails;
    }

    public function setExtraDetails(?array $extraDetails): self
    {
        $this->extraDetails = $extraDetails;

        return $this;
    }

    public function getPerformedAt(): \DateTimeImmutable
    {
        return $this->performedAt;
    }

    public function setPerformedAt(\DateTimeImmutable $performedAt): self
    {
        $this->performedAt = $performedAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
