<?php

declare(strict_types=1);

namespace App\Domain\Health\Entity;

use App\Domain\Health\Enum\HealthDataType;
use App\Domain\User\Entity\User;
use App\Infrastructure\Health\Repository\HealthSyncLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: HealthSyncLogRepository::class)]
#[ORM\Table(name: 'health_sync_logs')]
#[ORM\UniqueConstraint(name: 'unique_user_data_type', columns: ['user_id', 'data_type'])]
class HealthSyncLog
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 50, enumType: HealthDataType::class)]
    private HealthDataType $dataType;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $lastSyncedAt;

    #[ORM\Column(type: 'integer')]
    private int $pointsCount;

    public function __construct()
    {
        $this->id = Uuid::v4();
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

    public function getDataType(): HealthDataType
    {
        return $this->dataType;
    }

    public function setDataType(HealthDataType $dataType): self
    {
        $this->dataType = $dataType;

        return $this;
    }

    public function getLastSyncedAt(): \DateTimeImmutable
    {
        return $this->lastSyncedAt;
    }

    public function setLastSyncedAt(\DateTimeImmutable $lastSyncedAt): self
    {
        $this->lastSyncedAt = $lastSyncedAt;

        return $this;
    }

    public function getPointsCount(): int
    {
        return $this->pointsCount;
    }

    public function setPointsCount(int $pointsCount): self
    {
        $this->pointsCount = $pointsCount;

        return $this;
    }
}
