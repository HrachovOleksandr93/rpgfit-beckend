<?php

declare(strict_types=1);

namespace App\Infrastructure\Training\Repository;

use App\Domain\Training\Entity\ExerciseType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for ExerciseType entities.
 *
 * Infrastructure layer (Training bounded context). Provides persistence for
 * admin-managed exercise types within workout categories (e.g. "Bench Press", "Running").
 *
 * @extends ServiceEntityRepository<ExerciseType>
 */
class ExerciseTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExerciseType::class);
    }

    public function save(ExerciseType $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }
}
