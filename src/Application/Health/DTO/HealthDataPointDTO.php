<?php

declare(strict_types=1);

namespace App\Application\Health\DTO;

use App\Domain\Health\Enum\HealthDataType;
use App\Domain\Health\Enum\RecordingMethod;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for a single health data point within a sync batch.
 *
 * Represents one measurement from Apple HealthKit or Google Health Connect.
 * The externalUuid is the original record ID from the health platform,
 * used by HealthSyncService for deduplication (prevents storing the same record twice).
 *
 * Validated by Symfony constraints: all fields except externalUuid and sourceApp are required.
 * Dates must be in ISO 8601 format (DateTimeInterface::ATOM).
 */
final class HealthDataPointDTO
{
    public ?string $externalUuid = null;

    #[Assert\NotBlank(message: 'Data type is required.')]
    public ?string $type = null;

    #[Assert\NotNull(message: 'Value is required.')]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'Value must be zero or positive.')]
    public ?float $value = null;

    #[Assert\NotBlank(message: 'Unit is required.')]
    public string $unit = '';

    #[Assert\NotBlank(message: 'Date from is required.')]
    #[Assert\DateTime(format: \DateTimeInterface::ATOM, message: 'Invalid dateFrom format. Use ISO 8601.')]
    public string $dateFrom = '';

    #[Assert\NotBlank(message: 'Date to is required.')]
    #[Assert\DateTime(format: \DateTimeInterface::ATOM, message: 'Invalid dateTo format. Use ISO 8601.')]
    public string $dateTo = '';

    public ?string $sourceApp = null;

    #[Assert\NotBlank(message: 'Recording method is required.')]
    public ?string $recordingMethod = null;
}
