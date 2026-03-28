<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Character\Service\LevelingService;
use App\Domain\User\Entity\User;
use App\Infrastructure\Character\Repository\CharacterStatsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * API controller for leveling information.
 *
 * Provides the full XP-per-level table (public) and the authenticated user's
 * current level progress. Used by the mobile app's profile and progression screens.
 */
class LevelController extends AbstractController
{
    public function __construct(
        private readonly LevelingService $levelingService,
        private readonly CharacterStatsRepository $characterStatsRepository,
    ) {
    }

    /**
     * Return the full XP table for all 100 levels.
     *
     * Public endpoint -- no authentication required. The mobile app caches this
     * to display level thresholds without repeated network calls.
     */
    #[Route('/api/levels/table', name: 'api_levels_table', methods: ['GET'])]
    public function table(): JsonResponse
    {
        return $this->json([
            'levels' => $this->levelingService->getFullLevelTable(),
        ]);
    }

    /**
     * Return the current authenticated user's level progress.
     *
     * Includes current level, total XP, XP within the current bracket,
     * XP needed for next level, and a percentage progress indicator.
     */
    #[Route('/api/levels/progress', name: 'api_levels_progress', methods: ['GET'])]
    public function progress(#[CurrentUser] User $user): JsonResponse
    {
        $stats = $this->characterStatsRepository->findByUser($user);

        if ($stats === null) {
            return $this->json([
                'level' => 1,
                'totalXp' => 0,
                'currentLevelXp' => 0,
                'xpToNextLevel' => $this->levelingService->getXpForLevel(2),
                'progressPercent' => 0.0,
            ]);
        }

        $progress = $this->levelingService->getLevelProgress($stats->getTotalXp());

        return $this->json([
            'level' => $progress['level'],
            'totalXp' => $stats->getTotalXp(),
            'currentLevelXp' => $progress['currentLevelXp'],
            'xpToNextLevel' => $progress['xpToNextLevel'],
            'progressPercent' => $progress['progressPercent'],
        ]);
    }
}
