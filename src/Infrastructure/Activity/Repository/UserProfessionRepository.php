<?php

declare(strict_types=1);

namespace App\Infrastructure\Activity\Repository;

use App\Domain\Activity\Entity\UserProfession;
use App\Domain\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for UserProfession entities.
 *
 * Infrastructure layer (Activity bounded context). Provides data access for tracking
 * which professions users have unlocked and which are currently active.
 *
 * @extends ServiceEntityRepository<UserProfession>
 */
class UserProfessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserProfession::class);
    }

    /** Persist a UserProfession entity to the database. */
    public function save(UserProfession $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * Find all professions unlocked by a user.
     *
     * @return UserProfession[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    /**
     * Find all active professions for a user.
     *
     * @return UserProfession[]
     */
    public function findActiveByUser(User $user): array
    {
        return $this->findBy(['user' => $user, 'active' => true]);
    }

    /**
     * Find a user's profession in a specific category (by joining through profession -> category).
     *
     * @return UserProfession[]
     */
    public function findByUserAndCategory(User $user, string $categorySlug): array
    {
        return $this->createQueryBuilder('up')
            ->join('up.profession', 'p')
            ->join('p.category', 'c')
            ->where('up.user = :user')
            ->andWhere('c.slug = :categorySlug')
            ->setParameter('user', $user)
            ->setParameter('categorySlug', $categorySlug)
            ->getQuery()
            ->getResult();
    }
}
