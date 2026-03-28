<?php

declare(strict_types=1);

namespace App\Infrastructure\Workout\Repository;

use App\Domain\User\Entity\User;
use App\Domain\Workout\Entity\WorkoutPlan;
use App\Domain\Workout\Enum\WorkoutPlanStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for WorkoutPlan entities.
 *
 * Infrastructure layer (Workout bounded context). Provides data access for
 * user workout plans including status-based and date-based queries.
 *
 * @extends ServiceEntityRepository<WorkoutPlan>
 */
class WorkoutPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutPlan::class);
    }

    public function save(WorkoutPlan $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * Find all workout plans for the given user, ordered by planned date descending.
     *
     * @return WorkoutPlan[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['plannedAt' => 'DESC']);
    }

    /**
     * Find plans for a user on a specific date.
     *
     * @return WorkoutPlan[]
     */
    public function findByUserAndDate(User $user, \DateTimeImmutable $date): array
    {
        $startOfDay = $date->setTime(0, 0, 0);
        $endOfDay = $date->setTime(23, 59, 59);

        return $this->createQueryBuilder('wp')
            ->where('wp.user = :user')
            ->andWhere('wp.plannedAt >= :start')
            ->andWhere('wp.plannedAt <= :end')
            ->setParameter('user', $user)
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay)
            ->orderBy('wp.plannedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all active (pending or in-progress) plans for the given user.
     *
     * @return WorkoutPlan[]
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('wp')
            ->where('wp.user = :user')
            ->andWhere('wp.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', [WorkoutPlanStatus::Pending, WorkoutPlanStatus::InProgress])
            ->orderBy('wp.plannedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
