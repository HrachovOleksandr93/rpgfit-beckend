<?php

declare(strict_types=1);

namespace App\Infrastructure\Health\Repository;

use App\Domain\Health\Entity\HealthSyncLog;
use App\Domain\Health\Enum\HealthDataType;
use App\Domain\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
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
