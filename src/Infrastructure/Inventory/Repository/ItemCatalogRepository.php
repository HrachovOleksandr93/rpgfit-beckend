<?php

declare(strict_types=1);

namespace App\Infrastructure\Inventory\Repository;

use App\Domain\Inventory\Entity\ItemCatalog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for ItemCatalog entities.
 *
 * Infrastructure layer (Inventory bounded context). Provides data access for the
 * master item definitions. Each catalog entry defines an item's properties, type,
 * rarity, and associated stat bonuses.
 *
 * @extends ServiceEntityRepository<ItemCatalog>
 */
class ItemCatalogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItemCatalog::class);
    }

    public function save(ItemCatalog $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /** Find an item catalog entry by its unique slug identifier, or null if not found. */
    public function findBySlug(string $slug): ?ItemCatalog
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
