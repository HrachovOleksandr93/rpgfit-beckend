<?php

declare(strict_types=1);

namespace App\Application\Test\Service;

use App\Application\Health\DTO\HealthDataPointDTO;
use App\Application\Health\DTO\HealthSyncDTO;
use App\Application\Health\Service\HealthSyncService;
use App\Domain\Health\Entity\HealthDataPoint;
use App\Domain\Health\Enum\HealthDataType;
use App\Domain\Health\Enum\Platform;
use App\Domain\Health\Enum\RecordingMethod;
use App\Domain\User\Entity\User;
use App\Infrastructure\Health\Repository\HealthDataPointRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Injects and clears synthetic health data.
 *
 * Every injected point carries `sourceApp = "test-harness"` and a
 * `test-<uuid>` external id so they are round-trippable and, more
 * importantly, the `clear` path can safely delete test data without
 * touching real HealthKit-synced rows.
 */
final class HealthTestService
{
    public const string TEST_SOURCE_APP = 'test-harness';

    public function __construct(
        private readonly HealthSyncService $healthSyncService,
        private readonly HealthDataPointRepository $dataPointRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Inject a batch of synthetic points reusing the same sync path as
     * the real mobile-app endpoint — all dedup / XP / sync-log rules apply.
     *
     * @param list<array<string, mixed>> $rawPoints
     *
     * @return array<string, mixed>
     */
    public function injectPoints(User $user, string $platform, array $rawPoints): array
    {
        $dto = new HealthSyncDTO();
        $dto->platform = in_array($platform, ['ios', 'android'], true) ? $platform : Platform::Ios->value;
        $dto->dataPoints = [];

        foreach ($rawPoints as $raw) {
            $point = new HealthDataPointDTO();
            $point->externalUuid = isset($raw['externalUuid']) && is_string($raw['externalUuid']) && $raw['externalUuid'] !== ''
                ? $raw['externalUuid']
                : 'test-' . bin2hex(random_bytes(8));
            $point->type = isset($raw['type']) ? (string) $raw['type'] : HealthDataType::Steps->value;
            $point->value = isset($raw['value']) ? (float) $raw['value'] : 0.0;
            $point->unit = isset($raw['unit']) ? (string) $raw['unit'] : 'count';
            $point->dateFrom = (string) ($raw['dateFrom'] ?? (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM));
            $point->dateTo = (string) ($raw['dateTo'] ?? (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM));
            $point->sourceApp = self::TEST_SOURCE_APP;
            $point->recordingMethod = RecordingMethod::Manual->value;

            $dto->dataPoints[] = $point;
        }

        if ($dto->dataPoints === []) {
            return [
                'insertedCount' => 0,
                'skippedDuplicates' => 0,
                'xpAwarded' => 0,
            ];
        }

        $result = $this->healthSyncService->syncHealthData($user, $dto);

        return [
            'insertedCount' => $result['accepted'],
            'skippedDuplicates' => $result['duplicates_skipped'],
            'xpAwarded' => $result['xp']['awarded'] ?? 0,
        ];
    }

    /**
     * Hard-delete only health points with `source_app = test-harness`.
     *
     * Never touches real HealthKit data — this is the safety rail the spec
     * §3.3 calls out explicitly.
     */
    public function clearTestPoints(User $user, ?string $dataType = null): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('h')
            ->from(HealthDataPoint::class, 'h')
            ->where('h.user = :user')
            ->andWhere('h.sourceApp = :source')
            ->setParameter('user', $user)
            ->setParameter('source', self::TEST_SOURCE_APP);

        if ($dataType !== null && $dataType !== '') {
            $typeEnum = HealthDataType::tryFrom($dataType);
            if ($typeEnum !== null) {
                $qb->andWhere('h.dataType = :type')
                    ->setParameter('type', $typeEnum->value);
            }
        }

        $deleted = 0;
        foreach ($qb->getQuery()->getResult() as $point) {
            $this->entityManager->remove($point);
            $deleted++;
        }

        $this->entityManager->flush();

        return $deleted;
    }
}
