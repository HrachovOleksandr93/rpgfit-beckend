<?php

declare(strict_types=1);

namespace App\Domain\Battle\Entity;

use App\Domain\Battle\Enum\BattleMode;
use App\Domain\Battle\Enum\SessionStatus;
use App\Domain\Mob\Entity\Mob;
use App\Domain\User\Entity\User;
use App\Domain\Workout\Entity\WorkoutPlan;
use App\Infrastructure\Battle\Repository\WorkoutSessionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Tracks an active battle session linking a user, workout plan, and mob encounter.
 *
 * Domain layer (Battle bounded context). Represents a single battle instance where
 * the user performs exercises to deal damage to a mob. The session stores adjusted
 * mob stats (HP/XP may be modified for raid mode), tracks total damage dealt,
 * and records XP awarded upon completion.
 *
 * Lifecycle: active -> completed (exercises submitted) or abandoned (user quits).
 * Only one active session per user is allowed at a time.
 *
 * Indexed by (user_id, status) for active session lookups
 * and (user_id, started_at) for history queries.
 */
#[ORM\Entity(repositoryClass: WorkoutSessionRepository::class)]
#[ORM\Table(name: 'workout_sessions')]
#[ORM\Index(name: 'idx_session_user_status', columns: ['user_id', 'status'])]
#[ORM\Index(name: 'idx_session_user_started', columns: ['user_id', 'started_at'])]
class WorkoutSession
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: WorkoutPlan::class)]
    #[ORM\JoinColumn(nullable: false)]
    private WorkoutPlan $workoutPlan;

    #[ORM\ManyToOne(targetEntity: Mob::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Mob $mob = null;

    /** Battle mode chosen by the user (custom, recommended, or raid). */
    #[ORM\Column(type: 'string', length: 20, enumType: BattleMode::class)]
    private BattleMode $mode;

    /** Actual mob HP for this session (base value or +30% for raid mode). */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $mobHp = null;

    /** Actual XP reward for this session (base value or +30% for raid mode). */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $mobXpReward = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    /** Cumulative damage dealt to the mob through exercise performance. */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $totalDamageDealt = 0;

    /** Total XP awarded to the user for this session. */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $xpAwarded = 0;

    #[ORM\Column(type: 'string', length: 20, enumType: SessionStatus::class)]
    private SessionStatus $status;

    /** Health data submitted by the mobile app (duration, calories, heart rate, etc.). */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $healthData = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->startedAt = new \DateTimeImmutable();
        $this->status = SessionStatus::Active;
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

    public function getWorkoutPlan(): WorkoutPlan
    {
        return $this->workoutPlan;
    }

    public function setWorkoutPlan(WorkoutPlan $workoutPlan): self
    {
        $this->workoutPlan = $workoutPlan;

        return $this;
    }

    public function getMob(): ?Mob
    {
        return $this->mob;
    }

    public function setMob(?Mob $mob): self
    {
        $this->mob = $mob;

        return $this;
    }

    public function getMode(): BattleMode
    {
        return $this->mode;
    }

    public function setMode(BattleMode $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function getMobHp(): ?int
    {
        return $this->mobHp;
    }

    public function setMobHp(?int $mobHp): self
    {
        $this->mobHp = $mobHp;

        return $this;
    }

    public function getMobXpReward(): ?int
    {
        return $this->mobXpReward;
    }

    public function setMobXpReward(?int $mobXpReward): self
    {
        $this->mobXpReward = $mobXpReward;

        return $this;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getTotalDamageDealt(): int
    {
        return $this->totalDamageDealt;
    }

    public function setTotalDamageDealt(int $totalDamageDealt): self
    {
        $this->totalDamageDealt = $totalDamageDealt;

        return $this;
    }

    public function getXpAwarded(): int
    {
        return $this->xpAwarded;
    }

    public function setXpAwarded(int $xpAwarded): self
    {
        $this->xpAwarded = $xpAwarded;

        return $this;
    }

    public function getStatus(): SessionStatus
    {
        return $this->status;
    }

    public function setStatus(SessionStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getHealthData(): ?array
    {
        return $this->healthData;
    }

    public function setHealthData(?array $healthData): self
    {
        $this->healthData = $healthData;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('Session %s (%s)', $this->id->toRfc4122(), $this->status->value);
    }
}
