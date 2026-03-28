<?php

declare(strict_types=1);

namespace App\Domain\Health\Entity;

use App\Domain\Health\Enum\HealthDataType;
use App\Domain\Health\Enum\Platform;
use App\Domain\Health\Enum\RecordingMethod;
use App\Domain\User\Entity\User;
use App\Infrastructure\Health\Repository\HealthDataPointRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * A single raw health data measurement synced from the mobile app.
 *
 * Domain layer (Health bounded context). Each row stores one measurement originally
 * recorded by Apple HealthKit (iOS) or Google Health Connect (Android).
 *
 * Data flow: Health Platform SDK -> Mobile App -> POST /api/health/sync -> HealthSyncService -> this entity
 *
 * Key fields:
 * - externalUuid: the original record ID from HealthKit/Health Connect, used for deduplication
 *   (unique per user to prevent the same measurement from being stored twice)
 * - dataType: what is being measured (steps, heart rate, sleep, workout, etc.)
 * - value + unit: the measurement (e.g. 5000 steps, 72 bpm, 45 minutes)
 * - dateFrom/dateTo: the time range of the measurement
 * - platform: which mobile OS the data came from (ios/android)
 * - recordingMethod: whether the data was recorded automatically by sensors or manually by user
 *
 * Indexed by (user_id, data_type, date_from) for efficient daily aggregation queries
 * used by HealthSummaryService.
 */
#[ORM\Entity(repositoryClass: HealthDataPointRepository::class)]
#[ORM\Table(name: 'health_data_points')]
#[ORM\UniqueConstraint(name: 'unique_user_external_uuid', columns: ['user_id', 'external_uuid'])]
#[ORM\Index(name: 'idx_user_type_date', columns: ['user_id', 'data_type', 'date_from'])]
class HealthDataPoint
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $externalUuid;

    #[ORM\Column(type: 'string', length: 50, enumType: HealthDataType::class)]
    private HealthDataType $dataType;

    #[ORM\Column(type: 'float')]
    private float $value;

    #[ORM\Column(type: 'string', length: 50)]
    private string $unit;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $dateFrom;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $dateTo;

    #[ORM\Column(type: 'string', length: 20, enumType: Platform::class)]
    private Platform $platform;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $sourceApp;

    #[ORM\Column(type: 'string', length: 20, enumType: RecordingMethod::class)]
    private RecordingMethod $recordingMethod;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $syncedAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->syncedAt = new \DateTimeImmutable();
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

    public function getExternalUuid(): ?string
    {
        return $this->externalUuid;
    }

    public function setExternalUuid(?string $externalUuid): self
    {
        $this->externalUuid = $externalUuid;

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

    public function getValue(): float
    {
        return $this->value;
    }

    public function setValue(float $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    public function getDateFrom(): \DateTimeImmutable
    {
        return $this->dateFrom;
    }

    public function setDateFrom(\DateTimeImmutable $dateFrom): self
    {
        $this->dateFrom = $dateFrom;

        return $this;
    }

    public function getDateTo(): \DateTimeImmutable
    {
        return $this->dateTo;
    }

    public function setDateTo(\DateTimeImmutable $dateTo): self
    {
        $this->dateTo = $dateTo;

        return $this;
    }

    public function getPlatform(): Platform
    {
        return $this->platform;
    }

    public function setPlatform(Platform $platform): self
    {
        $this->platform = $platform;

        return $this;
    }

    public function getSourceApp(): ?string
    {
        return $this->sourceApp;
    }

    public function setSourceApp(?string $sourceApp): self
    {
        $this->sourceApp = $sourceApp;

        return $this;
    }

    public function getRecordingMethod(): RecordingMethod
    {
        return $this->recordingMethod;
    }

    public function setRecordingMethod(RecordingMethod $recordingMethod): self
    {
        $this->recordingMethod = $recordingMethod;

        return $this;
    }

    public function getSyncedAt(): \DateTimeImmutable
    {
        return $this->syncedAt;
    }
}
