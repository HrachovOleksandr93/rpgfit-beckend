<?php

declare(strict_types=1);

namespace App\Infrastructure\Portal\Repository;

use App\Domain\Portal\Entity\Portal;
use App\Domain\Portal\Enum\PortalType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
        // Longitude degrees shrink with latitude; guard against near-zero cosine at poles.
        $cosLat = max(0.000001, cos(deg2rad($lat)));
        $lngDelta = $radiusKm / (111.32 * $cosLat);

        // Step 1 — bounding-box filter via Doctrine QueryBuilder. This hits the
        // (latitude, longitude) composite index on MySQL and works identically on
        // SQLite (used in functional tests). Avoiding raw SQL keeps the query
        // cross-platform; ST_Distance_Sphere / BIN_TO_UUID aren't portable.
        $now = new \DateTimeImmutable();
        $qb = $this->createQueryBuilder('p')
            ->where('p.latitude BETWEEN :minLat AND :maxLat')
            ->andWhere('p.longitude BETWEEN :minLng AND :maxLng')
            ->andWhere('p.expiresAt IS NULL OR p.expiresAt > :now')
            ->setParameter('minLat', $lat - $latDelta)
            ->setParameter('maxLat', $lat + $latDelta)
            ->setParameter('minLng', $lng - $lngDelta)
            ->setParameter('maxLng', $lng + $lngDelta)
            ->setParameter('now', $now);

        $candidates = $qb->getQuery()->getResult();
        if ($candidates === []) {
            return [];
        }

        // Step 2 — precise Haversine in PHP. Portal counts stay tiny (<10k
        // static + a few hundred dynamic per region) so this is O(n) on a
        // small set post-bbox. Simpler than coping with DB-specific math.
        $earthKm = 6371.0;
        $result = [];
        foreach ($candidates as $portal) {
            $pLat = (float) $portal->getLatitude();
            $pLng = (float) $portal->getLongitude();
            $dLat = deg2rad($pLat - $lat);
            $dLng = deg2rad($pLng - $lng);
            $a = sin($dLat / 2) ** 2
                + cos(deg2rad($lat)) * cos(deg2rad($pLat)) * sin($dLng / 2) ** 2;
            $a = max(0.0, min(1.0, $a));
            $distance = 2 * $earthKm * asin(sqrt($a));
            if ($distance <= $radiusKm) {
                $result[] = ['portal' => $portal, 'distanceKm' => $distance];
            }
        }

        usort($result, static fn(array $a, array $b): int => $a['distanceKm'] <=> $b['distanceKm']);

        return array_slice($result, 0, $limit);
    }
}
