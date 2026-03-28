<?php

declare(strict_types=1);

namespace App\Infrastructure\Inventory\Repository;

use App\Domain\Inventory\Entity\ItemCatalog;
use App\Domain\Inventory\Entity\ItemStatBonus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for ItemStatBonus entities.
 *
 * Infrastructure layer (Inventory bounded context). Provides data access for the
 * stat bonus definitions associated with catalog items. Each bonus maps an item to a
 * stat type (STR/DEX/CON) with a point value.
 *
 * @extends ServiceEntityRepository<ItemStatBonus>
 */
class ItemStatBonusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItemStatBonus::class);
    }

    public function save(ItemStatBonus $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * @return ItemStatBonus[]
     */
    public function findByItem(ItemCatalog $item): array
    {
        return $this->findBy(['itemCatalog' => $item]);
    }
}
