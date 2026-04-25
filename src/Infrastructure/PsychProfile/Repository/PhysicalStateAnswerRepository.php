<?php

declare(strict_types=1);

namespace App\Infrastructure\PsychProfile\Repository;

use App\Domain\PsychProfile\Entity\PhysicalStateAnswer;
use App\Domain\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for PhysicalStateAnswer entities (Psych v2 Q4).
 *
 * Infrastructure layer (PsychProfile bounded context). Supports the
 * 2-hour merge window used by CheckInService + PhysicalStateService and
 * the GDPR purge/export hooks.
 *
 * @extends ServiceEntityRepository<PhysicalStateAnswer>
 */
class PhysicalStateAnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhysicalStateAnswer::class);
    }

    public function save(PhysicalStateAnswer $entity, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Latest Q4 answer for the user within the given number of hours (default 2h).
     * Used by CheckInService to merge the answer into the daily check-in.
     */
    public function findLatestForUserWithin(User $user, int $hours = 2): ?PhysicalStateAnswer
    {
        $cutoff = (new \DateTimeImmutable())->modify(sprintf('-%d hours', max(1, $hours)));

        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.createdAt >= :cutoff')
            ->setParameter('user', $user)
            ->setParameter('cutoff', $cutoff)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Latest Q4 answer for the user regardless of age — used by the
     * workout adapter to bucket "last Q4" into the matrix.
     */
    public function findLatestForUser(User $user): ?PhysicalStateAnswer
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** Purge all Q4 rows for a user (GDPR Art. 17). Returns affected row count. */
    public function deleteAllForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('p')
            ->delete()
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /** Retention purge: drop rows older than the cutoff (by createdAt). */
    public function deleteOlderThan(\DateTimeImmutable $cutoff): int
    {
        return (int) $this->createQueryBuilder('p')
            ->delete()
            ->where('p.createdAt < :cutoff')
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->execute();
    }
}
