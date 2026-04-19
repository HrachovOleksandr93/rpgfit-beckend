<?php

declare(strict_types=1);

namespace App\Infrastructure\PsychProfile\Repository;

use App\Domain\PsychProfile\Entity\PsychCheckIn;
use App\Domain\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for PsychCheckIn entities.
 *
 * Infrastructure layer (PsychProfile bounded context). Provides the queries
 * CheckInService / TodayService / ProfileTrendService / CrisisDetectionService
 * need: last-for-user, range, today-for-user, bulk delete older than.
 *
 * @extends ServiceEntityRepository<PsychCheckIn>
 */
class PsychCheckInRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PsychCheckIn::class);
    }

    public function save(PsychCheckIn $entity, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        if ($flush) {
            $em->flush();
        }
    }

    public function remove(PsychCheckIn $entity): void
    {
        $em = $this->getEntityManager();
        $em->remove($entity);
        $em->flush();
    }

    public function findLastByUser(User $user): ?PsychCheckIn
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.checkedInOn', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findForUserOnDate(User $user, \DateTimeImmutable $date): ?PsychCheckIn
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.checkedInOn = :date')
            ->setParameter('user', $user)
            ->setParameter('date', $date->format('Y-m-d'))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * All check-ins for a user between two dates inclusive, oldest first.
     *
     * @return list<PsychCheckIn>
     */
    public function findInRange(
        User $user,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
    ): array {
        /** @var list<PsychCheckIn> $rows */
        $rows = $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.checkedInOn BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
            ->orderBy('c.checkedInOn', 'ASC')
            ->getQuery()
            ->getResult();

        return $rows;
    }

    /**
     * @return list<PsychCheckIn> latest first, at most $limit rows.
     */
    public function findLatestForUser(User $user, int $limit = 30): array
    {
        /** @var list<PsychCheckIn> $rows */
        $rows = $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.checkedInOn', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();

        return $rows;
    }

    /** Purge all check-ins for a user (GDPR Art. 17). Returns affected row count. */
    public function deleteAllForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('c')
            ->delete()
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /** Retention purge: drop rows older than the cutoff (by createdAt). */
    public function deleteOlderThan(\DateTimeImmutable $cutoff): int
    {
        return (int) $this->createQueryBuilder('c')
            ->delete()
            ->where('c.createdAt < :cutoff')
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->execute();
    }
}
