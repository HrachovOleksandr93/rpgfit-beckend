<?php

declare(strict_types=1);

namespace App\Infrastructure\Training\Repository;

use App\Domain\Training\Entity\WorkoutLog;
use App\Domain\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for WorkoutLog entities.
 *
 * Infrastructure layer (Training bounded context). Provides data access for training
 * session logs. Workout logs can originate from health data sync (HealthKit/Health Connect)
 * or be created manually via the admin panel.
 *
 * @extends ServiceEntityRepository<WorkoutLog>
 */
class WorkoutLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutLog::class);
    }

    public function save(WorkoutLog $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * @return WorkoutLog[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['performedAt' => 'DESC']);
    }
}
