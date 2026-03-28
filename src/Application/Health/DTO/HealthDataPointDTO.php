<?php

declare(strict_types=1);

namespace App\Application\Health\DTO;

use App\Domain\Health\Enum\HealthDataType;
use App\Domain\Health\Enum\RecordingMethod;
use Symfony\Component\Validator\Constraints as Assert;

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
