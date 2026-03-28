<?php

declare(strict_types=1);

namespace App\Application\Health\Service;

use App\Application\Health\DTO\HealthSyncDTO;
use App\Domain\Health\Entity\HealthDataPoint;
use App\Domain\Health\Enum\HealthDataType;
use App\Domain\Health\Enum\Platform;
use App\Domain\Health\Enum\RecordingMethod;
use App\Domain\User\Entity\User;
use App\Infrastructure\Health\Repository\HealthDataPointRepository;
use App\Infrastructure\Health\Repository\HealthSyncLogRepository;

/**
 * Application service that processes batched health data from the mobile app.
 *
 * Application layer (Health bounded context). This is the core sync engine:
 * 1. Iterates through each data point in the batch
 * 2. Deduplicates by externalUuid (the ID from Apple HealthKit / Google Health Connect)
 * 3. Creates HealthDataPoint entities for new (non-duplicate) data
 * 4. Batch-saves all new entities to the database (flushing every 50 for performance)
 * 5. Updates HealthSyncLog per data type (for incremental sync tracking)
 *
 * Called by: HealthController::sync() after input validation
 * Data source: HealthSyncDTO (mapped from mobile app JSON batch)
 * Data destination: health_data_points table (via HealthDataPointRepository),
 *                   health_sync_logs table (via HealthSyncLogRepository)
 */
final class HealthSyncService
{
    public function __construct(
        private readonly HealthDataPointRepository $dataPointRepository,
        private readonly HealthSyncLogRepository $syncLogRepository,
    ) {
    }

    /**
     * Process and persist a batch of health data points from the mobile app.
     *
     * @return array{accepted: int, duplicates_skipped: int} Summary of sync results
     */
    public function syncHealthData(User $user, HealthSyncDTO $dto): array
    {
        $platform = Platform::from($dto->platform);
        $accepted = 0;
        $duplicatesSkipped = 0;
        $entities = [];
        $typeCounts = [];

        foreach ($dto->dataPoints as $pointDTO) {
            // Deduplication: skip if this externalUuid was already synced for this user.
            // This prevents storing the same HealthKit/Health Connect record twice
            // (e.g. when the mobile app retries a failed sync).
            if ($pointDTO->externalUuid !== null) {
                $existing = $this->dataPointRepository->findByUserAndExternalUuid(
                    $user,
                    $pointDTO->externalUuid,
                );

                if ($existing !== null) {
                    $duplicatesSkipped++;
                    continue;
                }
            }

            $dataType = HealthDataType::from($pointDTO->type);

            // Create a new HealthDataPoint entity from the DTO data
            $entity = new HealthDataPoint();
            $entity->setUser($user);
            $entity->setExternalUuid($pointDTO->externalUuid);
            $entity->setDataType($dataType);
            $entity->setValue($pointDTO->value);
            $entity->setUnit($pointDTO->unit);
            $entity->setDateFrom(new \DateTimeImmutable($pointDTO->dateFrom));
            $entity->setDateTo(new \DateTimeImmutable($pointDTO->dateTo));
            $entity->setPlatform($platform);
            $entity->setSourceApp($pointDTO->sourceApp);
            $entity->setRecordingMethod(RecordingMethod::from($pointDTO->recordingMethod));

            $entities[] = $entity;
            $accepted++;

            // Track counts per data type for sync log update
            $typeKey = $dataType->value;
            if (!isset($typeCounts[$typeKey])) {
                $typeCounts[$typeKey] = 0;
            }
            $typeCounts[$typeKey]++;
        }

        // Batch save all new entities (flushes every 50 for memory efficiency)
        if (count($entities) > 0) {
            $this->dataPointRepository->batchSave($entities);
        }

        // Update sync log per data type so the mobile app knows the latest sync state
        $now = new \DateTimeImmutable();
        foreach ($typeCounts as $typeValue => $count) {
            $this->syncLogRepository->upsert(
                $user,
                HealthDataType::from($typeValue),
                $now,
                $count,
            );
        }

        return [
            'accepted' => $accepted,
            'duplicates_skipped' => $duplicatesSkipped,
        ];
    }
}
