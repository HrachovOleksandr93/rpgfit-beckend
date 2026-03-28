<?php

declare(strict_types=1);

namespace App\Application\Health\DTO;

use App\Domain\Health\Enum\Platform;
use Symfony\Component\Validator\Constraints as Assert;

final class HealthSyncDTO
{
    #[Assert\NotBlank(message: 'Platform is required.')]
    public ?string $platform = null;

    /** @var HealthDataPointDTO[] */
    #[Assert\NotBlank(message: 'Data points are required.')]
    #[Assert\Count(min: 1, minMessage: 'At least one data point is required.')]
    #[Assert\Valid]
    public array $dataPoints = [];
}
