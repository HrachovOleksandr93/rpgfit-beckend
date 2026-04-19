<?php

declare(strict_types=1);

namespace App\Domain\Test\Entity;

use App\Domain\Test\Enum\ReasonEnum;
use App\Domain\User\Entity\User;
use App\Infrastructure\Test\Repository\AdminActionLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Persistent audit trail of admin / tester actions taken against another
 * user's state via the test harness.
 *
 * One row per mutating call. `actor` is the authenticated principal who
 * initiated the action. `target` is the user whose state is being mutated
 * — may be the same as the actor (tester acting on self) or null for a
 * generic admin event that has no user context.
 *
 * `payload` captures any extra structured context (endpoint path, request
 * body fingerprint, affected resource ids). Kept deliberately open so
 * future action types do not require a schema migration.
 */
#[ORM\Entity(repositoryClass: AdminActionLogRepository::class)]
#[ORM\Table(name: 'admin_action_logs')]
#[ORM\Index(columns: ['actor_id', 'created_at'], name: 'idx_admin_log_actor_date')]
#[ORM\Index(columns: ['target_id', 'created_at'], name: 'idx_admin_log_target_date')]
class AdminActionLog
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    // Eager so audit queries produce a self-contained read without N+1 lazy loads.
    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'actor_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $actor;

    // Nullable for events where actor == target (no distinct target) or for global admin actions.
    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'target_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $target = null;

    #[ORM\Column(type: 'string', length: 120)]
    private string $action;

    #[ORM\Column(type: 'string', length: 40, nullable: true, enumType: ReasonEnum::class)]
    private ?ReasonEnum $reason = null;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $payload = [];

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

    public function getActor(): User
    {
        return $this->actor;
    }

    public function setActor(User $actor): self
    {
        $this->actor = $actor;

        return $this;
    }

    public function getTarget(): ?User
    {
        return $this->target;
    }

    public function setTarget(?User $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getReason(): ?ReasonEnum
    {
        return $this->reason;
    }

    public function setReason(?ReasonEnum $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /** @param array<string, mixed> $payload */
    public function setPayload(array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
