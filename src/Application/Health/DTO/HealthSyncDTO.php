<?php

declare(strict_types=1);

namespace App\Application\Health\DTO;

use App\Domain\Health\Enum\Platform;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for a batch health data sync request from the mobile app.
 *
 * The mobile app collects health data from Apple HealthKit (iOS) or Google Health Connect
 * (Android) and sends it as a single batch with a platform identifier and an array of
 * individual data points.
 *
 * Used by: HealthController::sync() (populates from JSON) -> HealthSyncService (processes batch)
 *
 * The #[Assert\Valid] on dataPoints ensures nested HealthDataPointDTO validation is cascaded.
 */
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
