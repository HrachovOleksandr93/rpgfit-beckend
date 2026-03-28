<?php

declare(strict_types=1);

namespace App\Infrastructure\Mob\Repository;

use App\Domain\Mob\Entity\Mob;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for Mob entities.
 *
 * Infrastructure layer (Mob bounded context). Provides data access for mob
 * definitions including lookup by level range and unique slug identifier.
 *
 * @extends ServiceEntityRepository<Mob>
 */
class MobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mob::class);
    }

    /** Persist a mob entity and flush changes to the database. */
    public function save(Mob $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * Find all mobs at a specific level.
     *
     * @return Mob[]
     */
    public function findByLevel(int $level): array
    {
        return $this->findBy(['level' => $level]);
    }

    /** Find a single mob by its unique slug identifier, or null if not found. */
    public function findBySlug(string $slug): ?Mob
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
