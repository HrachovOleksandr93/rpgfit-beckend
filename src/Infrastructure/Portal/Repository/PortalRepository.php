<?php

declare(strict_types=1);

namespace App\Infrastructure\Portal\Repository;

use App\Domain\Portal\Entity\Portal;
use App\Domain\Portal\Enum\PortalType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for Portal entities.
 *
 * Infrastructure layer (Portal bounded context). Provides geo-aware queries
 * (Haversine + optional MySQL ST_Distance_Sphere) alongside standard lookups.
 *
 * @extends ServiceEntityRepository<Portal>
 */
class PortalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Portal::class);
    }

    public function save(Portal $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function remove(Portal $entity): void
    {
        $em = $this->getEntityManager();
        $em->remove($entity);
        $em->flush();
    }

    public function findBySlug(string $slug): ?Portal
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * All static portals (no geo filter). Caller is expected to cache.
     *
     * @return list<Portal>
     */
    public function findAllStatic(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.type = :type')
            ->setParameter('type', PortalType::Static)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Geo query: portals within $radiusKm of ($lat, $lng), sorted by distance.
     *
     * Strategy:
     *  1. Narrow the candidate set with a bounding box on (latitude, longitude)
     *     — this hits the composite index and keeps the row scan small.
     *  2. Run Haversine on the candidates for accurate great-circle distance.
     *
     * We avoid `ST_Distance_Sphere` as the primary filter because MySQL's
     * query planner often refuses to use the lat/lng index when a
     * spatial function is the predicate. The Haversine-only approach
     * still works on MySQL 5.7+ without any spatial extension.
     *
     * @return list<array{portal: Portal, distanceKm: float}>
     */
    public function findWithinRadius(float $lat, float $lng, float $radiusKm, int $limit = 20): array
    {
        // Clamp inputs defensively (controller also clamps, but service calls are possible).
        $radiusKm = max(0.01, $radiusKm);
        $limit = max(1, min($limit, 100));

        // Rough bounding box: 1 deg of latitude ~= 111.32 km.
        $latDelta = $radiusKm / 111.32;
        // Longitude degrees shrink with latitude; guard against cos(0) = 0 at the equator still being >0.
        $cosLat = max(0.000001, cos(deg2rad($lat)));
        $lngDelta = $radiusKm / (111.32 * $cosLat);

        $conn = $this->getEntityManager()->getConnection();

        // Fetch candidate IDs + precomputed distance in km using Haversine.
        $sql = <<<'SQL'
            SELECT
                BIN_TO_UUID(p.id) AS id,
                (
                    6371 * ACOS(
                        LEAST(1.0, GREATEST(-1.0,
                            COS(RADIANS(:lat)) * COS(RADIANS(p.latitude)) *
                            COS(RADIANS(p.longitude) - RADIANS(:lng)) +
                            SIN(RADIANS(:lat)) * SIN(RADIANS(p.latitude))
                        ))
                    )
                ) AS distance_km
            FROM portals p
            WHERE p.latitude BETWEEN :minLat AND :maxLat
              AND p.longitude BETWEEN :minLng AND :maxLng
              AND (p.expires_at IS NULL OR p.expires_at > :now)
            HAVING distance_km <= :radius
            ORDER BY distance_km ASC
            LIMIT :limit
        SQL;

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('lat', $lat);
        $stmt->bindValue('lng', $lng);
        $stmt->bindValue('minLat', $lat - $latDelta);
        $stmt->bindValue('maxLat', $lat + $latDelta);
        $stmt->bindValue('minLng', $lng - $lngDelta);
        $stmt->bindValue('maxLng', $lng + $lngDelta);
        $stmt->bindValue('radius', $radiusKm);
        $stmt->bindValue('now', (new \DateTimeImmutable())->format('Y-m-d H:i:s'));
        $stmt->bindValue('limit', $limit, ParameterType::INTEGER);

        $rows = $stmt->executeQuery()->fetchAllAssociative();
        if ($rows === []) {
            return [];
        }

        // Load entities in one query, preserving order + distance.
        $ids = array_column($rows, 'id');
        $entities = $this->createQueryBuilder('p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $byId = [];
        foreach ($entities as $entity) {
            $byId[$entity->getId()->toRfc4122()] = $entity;
        }

        $result = [];
        foreach ($rows as $row) {
            $portal = $byId[$row['id']] ?? null;
            if ($portal !== null) {
                $result[] = ['portal' => $portal, 'distanceKm' => (float) $row['distance_km']];
            }
        }

        return $result;
    }
}
