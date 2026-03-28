<?php

declare(strict_types=1);

namespace App\Infrastructure\Skill\Repository;

use App\Domain\Skill\Entity\Skill;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for Skill entities.
 *
 * Infrastructure layer (Skill bounded context). Provides data access for the
 * admin-configured skill definitions. Each skill can have multiple stat bonuses
 * and a required level for unlock.
 *
 * @extends ServiceEntityRepository<Skill>
 */
class SkillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Skill::class);
    }

    public function save(Skill $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /** Find a skill by its unique slug identifier, or null if not found. */
    public function findBySlug(string $slug): ?Skill
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
