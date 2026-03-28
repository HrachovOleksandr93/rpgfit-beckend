<?php

declare(strict_types=1);

namespace App\Infrastructure\Activity\Repository;

use App\Domain\Activity\Entity\ActivityCategory;
use App\Domain\Activity\Entity\Profession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for Profession entities.
 *
 * Infrastructure layer (Activity bounded context). Provides data access for the
 * 48 RPG professions (3 tiers per 16 categories).
 *
 * @extends ServiceEntityRepository<Profession>
 */
class ProfessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Profession::class);
    }

    /** Persist a Profession entity to the database. */
    public function save(Profession $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /** Find a profession by its unique slug. */
    public function findBySlug(string $slug): ?Profession
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /** Find a profession by category and tier. */
    public function findByCategoryAndTier(ActivityCategory $category, int $tier): ?Profession
    {
        return $this->findOneBy(['category' => $category, 'tier' => $tier]);
    }
}
