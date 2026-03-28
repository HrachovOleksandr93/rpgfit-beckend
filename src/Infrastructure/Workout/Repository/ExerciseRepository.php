<?php

declare(strict_types=1);

namespace App\Infrastructure\Workout\Repository;

use App\Domain\Workout\Entity\Exercise;
use App\Domain\Workout\Enum\ExerciseDifficulty;
use App\Domain\Workout\Enum\MuscleGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for Exercise entities.
 *
 * Infrastructure layer (Workout bounded context). Provides data access for the
 * exercise catalog used by the workout plan generator and admin panel.
 *
 * @extends ServiceEntityRepository<Exercise>
 */
class ExerciseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exercise::class);
    }

    public function save(Exercise $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function findBySlug(string $slug): ?Exercise
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * Find all exercises targeting the given primary muscle group.
     *
     * @return Exercise[]
     */
    public function findByMuscleGroup(MuscleGroup $muscleGroup): array
    {
        return $this->findBy(['primaryMuscle' => $muscleGroup], ['priority' => 'ASC']);
    }

    /**
     * Find exercises by primary muscle group and difficulty level.
     *
     * @return Exercise[]
     */
    public function findByMuscleGroupAndDifficulty(MuscleGroup $muscleGroup, ExerciseDifficulty $difficulty): array
    {
        return $this->findBy(
            ['primaryMuscle' => $muscleGroup, 'difficulty' => $difficulty],
            ['priority' => 'ASC']
        );
    }
}
