<?php

declare(strict_types=1);

namespace App\Infrastructure\Activity\Repository;

use App\Domain\Activity\Entity\ActivityCategory;
use App\Domain\Activity\Entity\ActivityType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for ActivityType entities.
 *
 * Infrastructure layer (Activity bounded context). Provides data access for the
 * 99 health-app activity types mapped from Flutter health package enums.
 *
 * @extends ServiceEntityRepository<ActivityType>
 */
class ActivityTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityType::class);
    }

    /** Persist an ActivityType entity to the database. */
    public function save(ActivityType $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /** Find an activity type by its unique slug. */
    public function findBySlug(string $slug): ?ActivityType
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /** Find an activity type by its Flutter enum value. */
    public function findByFlutterEnum(string $flutterEnum): ?ActivityType
    {
        return $this->findOneBy(['flutterEnum' => $flutterEnum]);
    }

    /**
     * Find all activity types belonging to a category.
     *
     * @return ActivityType[]
     */
    public function findByCategory(ActivityCategory $category): array
    {
        return $this->findBy(['category' => $category]);
    }
}
