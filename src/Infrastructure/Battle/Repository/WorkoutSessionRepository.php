<?php

declare(strict_types=1);

namespace App\Infrastructure\Battle\Repository;

use App\Domain\Battle\Entity\WorkoutSession;
use App\Domain\Battle\Enum\SessionStatus;
use App\Domain\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for WorkoutSession entities.
 *
 * Infrastructure layer (Battle bounded context). Provides data access for battle
 * sessions including active session lookup (only one active per user) and
 * user session history queries.
 *
 * @extends ServiceEntityRepository<WorkoutSession>
 */
class WorkoutSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutSession::class);
    }

    /** Persist a workout session entity and flush changes to the database. */
    public function save(WorkoutSession $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /** Find the currently active battle session for a user, or null if none. */
    public function findActiveByUser(User $user): ?WorkoutSession
    {
        return $this->findOneBy([
            'user' => $user,
            'status' => SessionStatus::Active,
        ]);
    }

    /**
     * Find recent battle sessions for a user, ordered by start time descending.
     *
     * @return WorkoutSession[]
     */
    public function findByUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.startedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** Find a session by its UUID string, or null if not found. */
    public function findById(string $id): ?WorkoutSession
    {
        return $this->find($id);
    }
}
