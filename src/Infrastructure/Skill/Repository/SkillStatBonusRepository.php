<?php

declare(strict_types=1);

namespace App\Infrastructure\Skill\Repository;

use App\Domain\Skill\Entity\Skill;
use App\Domain\Skill\Entity\SkillStatBonus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for SkillStatBonus entities.
 *
 * Infrastructure layer (Skill bounded context). Provides data access for the
 * stat bonus definitions associated with skills. Each bonus maps a skill to a
 * stat type (STR/DEX/CON) with a point value.
 *
 * @extends ServiceEntityRepository<SkillStatBonus>
 */
class SkillStatBonusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SkillStatBonus::class);
    }

    public function save(SkillStatBonus $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * @return SkillStatBonus[]
     */
    public function findBySkill(Skill $skill): array
    {
        return $this->findBy(['skill' => $skill]);
    }
}
