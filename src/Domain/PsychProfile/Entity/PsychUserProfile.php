<?php

declare(strict_types=1);

namespace App\Domain\PsychProfile\Entity;

use App\Domain\PsychProfile\Enum\PsychStatus;
use App\Domain\User\Entity\User;
use App\Infrastructure\PsychProfile\Repository\PsychUserProfileRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Per-user psych state (one row per user).
 *
 * Domain layer (PsychProfile bounded context). Holds the currently assigned
 * status, its TTL (local midnight), consecutive-skip counter, opt-in flag,
 * and a denormalised trends JSON (7d / 30d / 90d dominant status).
 *
 * The JSON `trends` column has **no DB-level default** — MySQL rejects
 * defaults on JSON, and the property is PHP-side initialised to an empty
 * array, so a freshly persisted row always carries `[]`.
 */
#[ORM\Entity(repositoryClass: PsychUserProfileRepository::class)]
#[ORM\Table(name: 'psych_user_profiles')]
#[ORM\UniqueConstraint(name: 'uniq_psych_user_profile_user', columns: ['user_id'])]
class PsychUserProfile
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', length: 20, enumType: PsychStatus::class)]
    private PsychStatus $currentStatus;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $statusValidUntil;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $consecutiveSkips = 0;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $featureOptedIn = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastCheckInAt = null;

    /**
     * Rolling trends JSON. No DB-level default (JSON limitation on MySQL);
     * PHP initialises to empty array so a fresh insert always has `[]`.
     *
     * Shape (set by ProfileTrendService):
     * {
     *   "dominantStatus7d": "STEADY",
     *   "dominantStatus30d": "STEADY",
     *   "statusDistribution90d": {"STEADY": 42, "CHARGED": 10, ...}
     * }
     *
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json')]
    private array $trends = [];

    public function __construct()
    {
        $this->id = Uuid::v4();
        // Defaults: Steady and valid for 24h until CheckInService rewrites it.
        $this->currentStatus = PsychStatus::STEADY;
        $this->statusValidUntil = new \DateTimeImmutable('+1 day');
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

    public function getCurrentStatus(): PsychStatus
    {
        return $this->currentStatus;
    }

    public function setCurrentStatus(PsychStatus $currentStatus): self
    {
        $this->currentStatus = $currentStatus;

        return $this;
    }

    public function getStatusValidUntil(): \DateTimeImmutable
    {
        return $this->statusValidUntil;
    }

    public function setStatusValidUntil(\DateTimeImmutable $statusValidUntil): self
    {
        $this->statusValidUntil = $statusValidUntil;

        return $this;
    }

    public function getConsecutiveSkips(): int
    {
        return $this->consecutiveSkips;
    }

    public function setConsecutiveSkips(int $consecutiveSkips): self
    {
        $this->consecutiveSkips = max(0, $consecutiveSkips);

        return $this;
    }

    public function isFeatureOptedIn(): bool
    {
        return $this->featureOptedIn;
    }

    public function setFeatureOptedIn(bool $featureOptedIn): self
    {
        $this->featureOptedIn = $featureOptedIn;

        return $this;
    }

    public function getLastCheckInAt(): ?\DateTimeImmutable
    {
        return $this->lastCheckInAt;
    }

    public function setLastCheckInAt(?\DateTimeImmutable $lastCheckInAt): self
    {
        $this->lastCheckInAt = $lastCheckInAt;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getTrends(): array
    {
        return $this->trends;
    }

    /** @param array<string, mixed> $trends */
    public function setTrends(array $trends): self
    {
        $this->trends = $trends;

        return $this;
    }
}
