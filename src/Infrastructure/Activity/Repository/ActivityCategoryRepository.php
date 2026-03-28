<?php

declare(strict_types=1);

namespace App\Infrastructure\Activity\Repository;

use App\Domain\Activity\Entity\ActivityCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for ActivityCategory entities.
 *
 * Infrastructure layer (Activity bounded context). Provides data access for the
 * 16 RPG activity categories that group related activity types and professions.
 *
 * @extends ServiceEntityRepository<ActivityCategory>
 */
class ActivityCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityCategory::class);
    }

    /** Persist an ActivityCategory entity to the database. */
    public function save(ActivityCategory $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /** Find a category by its unique slug. */
    public function findBySlug(string $slug): ?ActivityCategory
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
