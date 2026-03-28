<?php

declare(strict_types=1);

namespace App\Infrastructure\Character\Repository;

use App\Domain\Character\Entity\CharacterStats;
use App\Domain\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for CharacterStats entities.
 *
 * Infrastructure layer (Character bounded context). Provides data access for the
 * RPG character stats (STR/DEX/CON). Each user has at most one CharacterStats record.
 *
 * Used by future game logic services to read/update stats when workouts are completed,
 * and by the admin panel for manual stat management.
 *
 * @extends ServiceEntityRepository<CharacterStats>
 */
class CharacterStatsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CharacterStats::class);
    }

    public function save(CharacterStats $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function findByUser(User $user): ?CharacterStats
    {
        return $this->findOneBy(['user' => $user]);
    }
}
