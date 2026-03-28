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

final class HealthSyncService
{
    public function __construct(
        private readonly HealthDataPointRepository $dataPointRepository,
        private readonly HealthSyncLogRepository $syncLogRepository,
    ) {
    }

    /**
     * Sync health data from the mobile app.
     *
     * @return array{accepted: int, duplicates_skipped: int}
     */
    public function syncHealthData(User $user, HealthSyncDTO $dto): array
    {
        $platform = Platform::from($dto->platform);
        $accepted = 0;
        $duplicatesSkipped = 0;
        $entities = [];
        $typeCounts = [];

        foreach ($dto->dataPoints as $pointDTO) {
            // Skip duplicates by externalUuid
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

            // Track counts per data type
            $typeKey = $dataType->value;
            if (!isset($typeCounts[$typeKey])) {
                $typeCounts[$typeKey] = 0;
            }
            $typeCounts[$typeKey]++;
        }

        // Batch save all entities
        if (count($entities) > 0) {
            $this->dataPointRepository->batchSave($entities);
        }

        // Update sync logs per data type
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
