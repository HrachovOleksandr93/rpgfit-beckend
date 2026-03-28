<?php

declare(strict_types=1);

namespace App\Infrastructure\Workout\Repository;

use App\Domain\Workout\Entity\WorkoutPlanExercise;
use App\Domain\Workout\Entity\WorkoutPlanExerciseLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for WorkoutPlanExerciseLog entities.
 *
 * Infrastructure layer (Workout bounded context). Provides data access for
 * individual set performance logs within a planned exercise.
 *
 * @extends ServiceEntityRepository<WorkoutPlanExerciseLog>
 */
class WorkoutPlanExerciseLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutPlanExerciseLog::class);
    }

    public function save(WorkoutPlanExerciseLog $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * Find all set logs for the given plan exercise, ordered by set number.
     *
     * @return WorkoutPlanExerciseLog[]
     */
    public function findByPlanExercise(WorkoutPlanExercise $planExercise): array
    {
        return $this->findBy(['planExercise' => $planExercise], ['setNumber' => 'ASC']);
    }
}
