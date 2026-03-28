<?php

declare(strict_types=1);

namespace App\Infrastructure\Skill\Repository;

use App\Domain\Skill\Entity\UserSkill;
use App\Domain\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for UserSkill entities.
 *
 * Infrastructure layer (Skill bounded context). Provides data access for the
 * user-skill unlock records. Each record represents a skill unlocked by a user.
 *
 * @extends ServiceEntityRepository<UserSkill>
 */
class UserSkillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSkill::class);
    }

    public function save(UserSkill $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * @return UserSkill[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }
}
