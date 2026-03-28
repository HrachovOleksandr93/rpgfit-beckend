<?php

declare(strict_types=1);

namespace App\Infrastructure\Config\Repository;

use App\Domain\Config\Entity\GameSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for GameSetting entities.
 *
 * Infrastructure layer (Config bounded context). Provides data access for game
 * configuration settings. Supports lookup by key, filtering by category, and
 * bulk retrieval as a flat key-value map for efficient service consumption.
 *
 * @extends ServiceEntityRepository<GameSetting>
 */
class GameSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameSetting::class);
    }

    /** Persist a GameSetting entity. */
    public function save(GameSetting $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /** Find a single setting by its unique key. */
    public function findByKey(string $key): ?GameSetting
    {
        return $this->findOneBy(['key' => $key]);
    }

    /**
     * Return all settings belonging to a given category.
     *
     * @return GameSetting[]
     */
    public function findByCategory(string $category): array
    {
        return $this->findBy(['category' => $category]);
    }

    /**
     * Return every setting as a flat associative array: ['key' => 'value'].
     *
     * Useful for services that need multiple settings at once without
     * issuing individual queries per key.
     *
     * @return array<string, string>
     */
    public function getAllAsMap(): array
    {
        $rows = $this->createQueryBuilder('g')
            ->select('g.key', 'g.value')
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['key']] = $row['value'];
        }

        return $map;
    }
}
