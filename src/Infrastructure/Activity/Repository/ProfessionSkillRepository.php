<?php

declare(strict_types=1);

namespace App\Infrastructure\Activity\Repository;

use App\Domain\Activity\Entity\ProfessionSkill;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for ProfessionSkill junction entities.
 *
 * Infrastructure layer (Activity bounded context). Provides data access for
 * profession-to-skill assignments. Skills are shared across professions.
 *
 * @extends ServiceEntityRepository<ProfessionSkill>
 */
class ProfessionSkillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProfessionSkill::class);
    }

    /** Persist a ProfessionSkill entity to the database. */
    public function save(ProfessionSkill $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }
}
