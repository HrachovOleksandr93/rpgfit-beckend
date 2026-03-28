<?php

declare(strict_types=1);

namespace App\Infrastructure\Character\Repository;

use App\Domain\Character\Entity\ExperienceLog;
use App\Domain\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for ExperienceLog entities.
 *
 * Infrastructure layer (Character bounded context). Provides data access for XP gain
 * history. Each entry records a single XP award event (from workout, achievement, etc.).
 *
 * Supports querying user's XP history (sorted by date) and calculating total XP
 * (used for level progression calculations).
 *
 * @extends ServiceEntityRepository<ExperienceLog>
 */
class ExperienceLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExperienceLog::class);
    }

    public function save(ExperienceLog $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * @return ExperienceLog[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['earnedAt' => 'DESC']);
    }

    /** Calculate total accumulated XP for a user. Used for level determination. */
    public function getTotalXpByUser(User $user): int
    {
        $result = $this->createQueryBuilder('e')
            ->select('SUM(e.amount)')
            ->where('e.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }
}
