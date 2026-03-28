<?php

declare(strict_types=1);

namespace App\Infrastructure\Health\Repository;

use App\Domain\Health\Entity\HealthSyncLog;
use App\Domain\Health\Enum\HealthDataType;
use App\Domain\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for HealthSyncLog entities.
 *
 * Infrastructure layer (Health bounded context). Manages the sync tracking log
 * that records when each health data type was last synced for each user.
 *
 * The upsert() method creates or updates the sync log, providing an idempotent
 * way to track sync state. Used by HealthSyncService after processing a batch
 * and by HealthController::syncStatus() for the mobile app to check sync state.
 *
 * @extends ServiceEntityRepository<HealthSyncLog>
 */
class HealthSyncLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HealthSyncLog::class);
    }

    public function findByUserAndType(User $user, HealthDataType $type): ?HealthSyncLog
    {
        return $this->findOneBy([
            'user' => $user,
            'dataType' => $type,
        ]);
    }

    /** Create or update the sync log for a given user and data type. Idempotent upsert pattern. */
    public function upsert(User $user, HealthDataType $type, \DateTimeImmutable $syncedAt, int $count): void
    {
        $em = $this->getEntityManager();
        $log = $this->findByUserAndType($user, $type);

        if ($log === null) {
            $log = new HealthSyncLog();
            $log->setUser($user);
            $log->setDataType($type);
        }

        $log->setLastSyncedAt($syncedAt);
        $log->setPointsCount($count);

        $em->persist($log);
        $em->flush();
    }

    /**
     * Returns all sync logs for the given user.
     *
     * @return HealthSyncLog[]
     */
    public function getSyncStatus(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }
}
