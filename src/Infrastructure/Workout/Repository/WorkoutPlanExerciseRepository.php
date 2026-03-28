<?php

declare(strict_types=1);

namespace App\Infrastructure\Workout\Repository;

use App\Domain\Workout\Entity\WorkoutPlan;
use App\Domain\Workout\Entity\WorkoutPlanExercise;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for WorkoutPlanExercise entities.
 *
 * Infrastructure layer (Workout bounded context). Provides data access for
 * exercises within a workout plan, ordered by their position index.
 *
 * @extends ServiceEntityRepository<WorkoutPlanExercise>
 */
class WorkoutPlanExerciseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutPlanExercise::class);
    }

    public function save(WorkoutPlanExercise $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * Find all exercises for a workout plan, ordered by position.
     *
     * @return WorkoutPlanExercise[]
     */
    public function findByPlan(WorkoutPlan $plan): array
    {
        return $this->findBy(['workoutPlan' => $plan], ['orderIndex' => 'ASC']);
    }
}
