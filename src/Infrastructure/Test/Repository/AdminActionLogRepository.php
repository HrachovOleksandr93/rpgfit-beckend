<?php

declare(strict_types=1);

namespace App\Infrastructure\Test\Repository;

use App\Domain\Test\Entity\AdminActionLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for `AdminActionLog`.
 *
 * Persists via `save()` (flush-on-demand) and exposes `findRecent()` as a
 * convenience for the admin dashboard / CLI inspection.
 *
 * @extends ServiceEntityRepository<AdminActionLog>
 */
class AdminActionLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminActionLog::class);
    }

    public function save(AdminActionLog $log, bool $flush = true): void
    {
        $this->getEntityManager()->persist($log);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Return the N most recent audit events, newest first.
     *
     * @return list<AdminActionLog>
     */
    public function findRecent(int $limit): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }
}
