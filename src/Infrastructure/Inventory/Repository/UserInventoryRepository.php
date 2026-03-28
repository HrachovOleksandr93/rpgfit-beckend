<?php

declare(strict_types=1);

namespace App\Infrastructure\Inventory\Repository;

use App\Domain\Inventory\Entity\UserInventory;
use App\Domain\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for UserInventory entities.
 *
 * Infrastructure layer (Inventory bounded context). Provides data access for user
 * item ownership records. Supports querying active (non-soft-deleted) items and
 * equipped items for a given user.
 *
 * @extends ServiceEntityRepository<UserInventory>
 */
class UserInventoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserInventory::class);
    }

    public function save(UserInventory $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * Returns all non-soft-deleted inventory items for a user.
     *
     * @return UserInventory[]
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('ui')
            ->where('ui.user = :user')
            ->andWhere('ui.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns all equipped items for a user.
     *
     * @return UserInventory[]
     */
    public function findEquippedByUser(User $user): array
    {
        return $this->findBy(['user' => $user, 'equipped' => true]);
    }
}
