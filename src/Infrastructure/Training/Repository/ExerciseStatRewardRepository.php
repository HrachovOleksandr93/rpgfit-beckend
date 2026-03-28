<?php

declare(strict_types=1);

namespace App\Infrastructure\Training\Repository;

use App\Domain\Training\Entity\ExerciseStatReward;
use App\Domain\Training\Entity\ExerciseType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for ExerciseStatReward entities.
 *
 * Infrastructure layer (Training bounded context). Provides data access for the
 * admin-configured stat reward rules. Each rule maps an exercise type to a stat
 * type (STR/DEX/CON) with a point value.
 *
 * Used by future game logic to look up how many stat points to award when a
 * user completes a specific exercise.
 *
 * @extends ServiceEntityRepository<ExerciseStatReward>
 */
class ExerciseStatRewardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExerciseStatReward::class);
    }

    public function save(ExerciseStatReward $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * @return ExerciseStatReward[]
     */
    public function findByExerciseType(ExerciseType $exerciseType): array
    {
        return $this->findBy(['exerciseType' => $exerciseType]);
    }
}
