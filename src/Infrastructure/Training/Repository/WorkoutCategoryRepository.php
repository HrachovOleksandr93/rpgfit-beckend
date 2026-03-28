<?php

declare(strict_types=1);

namespace App\Infrastructure\Training\Repository;

use App\Domain\Training\Entity\WorkoutCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for WorkoutCategory entities.
 *
 * Infrastructure layer (Training bounded context). Provides persistence for
 * admin-managed workout categories (e.g. "Cardio", "Strength", "Flexibility").
 *
 * @extends ServiceEntityRepository<WorkoutCategory>
 */
class WorkoutCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutCategory::class);
    }

    public function save(WorkoutCategory $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }
}
