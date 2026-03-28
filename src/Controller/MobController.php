<?php

declare(strict_types=1);

namespace App\Controller;

use App\Infrastructure\Mob\Repository\MobRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API controller for mob data.
 *
 * Provides endpoints to query the mob catalog.
 * All mob endpoints are public (game data, no user-specific info).
 *
 * Data source: mobs table (populated via CSV import or Sonata admin)
 * Data destination: JSON response to mobile app
 */
class MobController extends AbstractController
{
    public function __construct(
        private readonly MobRepository $mobRepository,
    ) {
    }

    /**
     * GET /api/mobs — list mobs with optional filters.
     *
     * Query params:
     *   - level (int) — filter by exact level
     *   - level_min (int) — filter by minimum level
     *   - level_max (int) — filter by maximum level
     *   - rarity (string) — filter by rarity tier
     *   - limit (int, default 50, max 200)
     *   - offset (int, default 0)
     */
    #[Route('/api/mobs', name: 'api_mobs_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $qb = $this->mobRepository->createQueryBuilder('m');

        // Filter by exact level
        $level = $request->query->getInt('level', 0);
        if ($level > 0) {
            $qb->andWhere('m.level = :level')->setParameter('level', $level);
        }

        // Filter by level range
        $levelMin = $request->query->getInt('level_min', 0);
        if ($levelMin > 0) {
            $qb->andWhere('m.level >= :levelMin')->setParameter('levelMin', $levelMin);
        }

        $levelMax = $request->query->getInt('level_max', 0);
        if ($levelMax > 0) {
            $qb->andWhere('m.level <= :levelMax')->setParameter('levelMax', $levelMax);
        }

        // Filter by rarity
        $rarity = $request->query->get('rarity');
        if ($rarity !== null && $rarity !== '') {
            $qb->andWhere('m.rarity = :rarity')->setParameter('rarity', $rarity);
        }

        // Pagination
        $limit = min($request->query->getInt('limit', 50), 200);
        $offset = max($request->query->getInt('offset', 0), 0);

        $qb->orderBy('m.level', 'ASC')
            ->addOrderBy('m.name', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $mobs = $qb->getQuery()->getResult();

        return $this->json([
            'count' => count($mobs),
            'mobs' => array_map(fn($mob) => $this->serializeMob($mob), $mobs),
        ]);
    }

    /**
     * GET /api/mobs/{slug} — get a single mob by slug.
     */
    #[Route('/api/mobs/{slug}', name: 'api_mobs_show', methods: ['GET'])]
    public function show(string $slug): JsonResponse
    {
        $mob = $this->mobRepository->findBySlug($slug);

        if ($mob === null) {
            return $this->json(['error' => 'Mob not found'], 404);
        }

        return $this->json($this->serializeMob($mob));
    }

    /**
     * Serialize a Mob entity to array for JSON response.
     */
    private function serializeMob(object $mob): array
    {
        return [
            'id' => (string) $mob->getId(),
            'name' => $mob->getName(),
            'slug' => $mob->getSlug(),
            'level' => $mob->getLevel(),
            'hp' => $mob->getHp(),
            'xpReward' => $mob->getXpReward(),
            'rarity' => $mob->getRarity()?->value,
            'description' => $mob->getDescription(),
            'image' => $mob->getImage()?->getPublicUrl(),
        ];
    }
}
