<?php

declare(strict_types=1);

namespace App\Domain\PsychProfile\Entity;

use App\Domain\PsychProfile\Enum\MoodQuadrant;
use App\Domain\PsychProfile\Enum\PsychStatus;
use App\Domain\PsychProfile\Enum\UserIntent;
use App\Domain\User\Entity\User;
use App\Infrastructure\PsychProfile\Repository\PsychCheckInRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * One submitted (or skipped) daily psych check-in.
 *
 * Domain layer (PsychProfile bounded context). Each row represents the
 * answers to the three-question flow (spec 2026-04-18 §2) for a single
 * user on a single local-day. `checkedInOn` is a date (no time component)
 * normalised to the user's local timezone at the moment of submission.
 *
 * When `skipped` is true, mood/energy/intent are null and `assignedStatus`
 * is inherited-with-decay by CheckInService.
 */
#[ORM\Entity(repositoryClass: PsychCheckInRepository::class)]
#[ORM\Table(name: 'psych_check_ins')]
#[ORM\UniqueConstraint(name: 'uniq_psych_checkin_user_day', columns: ['user_id', 'checked_in_on'])]
#[ORM\Index(name: 'idx_psych_checkin_user_date', columns: ['user_id', 'checked_in_on'])]
#[ORM\Index(name: 'idx_psych_checkin_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_psych_checkin_user_created', columns: ['user_id', 'created_at'])]
class PsychCheckIn
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', length: 20, nullable: true, enumType: MoodQuadrant::class)]
    private ?MoodQuadrant $moodQuadrant = null;

    /** 1..5 scale, nullable when skipped. */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $energyLevel = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true, enumType: UserIntent::class)]
    private ?UserIntent $intent = null;

    #[ORM\Column(type: 'string', length: 20, enumType: PsychStatus::class)]
    private PsychStatus $assignedStatus;

    #[ORM\Column(type: 'boolean')]
    private bool $skipped = false;

    /** Date in the user's local timezone; used for "one check-in per day" guards. */
    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $checkedInOn;

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

    public function getMoodQuadrant(): ?MoodQuadrant
    {
        return $this->moodQuadrant;
    }

    public function setMoodQuadrant(?MoodQuadrant $moodQuadrant): self
    {
        $this->moodQuadrant = $moodQuadrant;

        return $this;
    }

    public function getEnergyLevel(): ?int
    {
        return $this->energyLevel;
    }

    public function setEnergyLevel(?int $energyLevel): self
    {
        $this->energyLevel = $energyLevel;

        return $this;
    }

    public function getIntent(): ?UserIntent
    {
        return $this->intent;
    }

    public function setIntent(?UserIntent $intent): self
    {
        $this->intent = $intent;

        return $this;
    }

    public function getAssignedStatus(): PsychStatus
    {
        return $this->assignedStatus;
    }

    public function setAssignedStatus(PsychStatus $status): self
    {
        $this->assignedStatus = $status;

        return $this;
    }

    public function isSkipped(): bool
    {
        return $this->skipped;
    }

    public function setSkipped(bool $skipped): self
    {
        $this->skipped = $skipped;

        return $this;
    }

    public function getCheckedInOn(): \DateTimeImmutable
    {
        return $this->checkedInOn;
    }

    public function setCheckedInOn(\DateTimeImmutable $checkedInOn): self
    {
        $this->checkedInOn = $checkedInOn;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
