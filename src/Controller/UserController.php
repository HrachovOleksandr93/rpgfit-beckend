<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\User\Entity\User;
use App\Infrastructure\Character\Repository\CharacterStatsRepository;
use App\Infrastructure\Character\Repository\ExperienceLogRepository;
use App\Infrastructure\Inventory\Repository\UserInventoryRepository;
use App\Infrastructure\Skill\Repository\UserSkillRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * API controller for the authenticated user's full profile.
 *
 * Returns the current user's complete data including RPG stats, inventory,
 * skills, and total XP. Requires JWT authentication.
 *
 * Security: always uses #[CurrentUser] -- no user ID in URL.
 * One user can ONLY see their own data.
 */
class UserController extends AbstractController
{
    public function __construct(
        private readonly CharacterStatsRepository $characterStatsRepository,
        private readonly UserInventoryRepository $userInventoryRepository,
        private readonly UserSkillRepository $userSkillRepository,
        private readonly ExperienceLogRepository $experienceLogRepository,
    ) {
    }

    /**
     * Return the authenticated user's comprehensive profile as JSON.
     *
     * Includes: basic profile, RPG stats, active inventory items, unlocked skills, and total XP.
     */
    #[Route('/api/user', name: 'api_user', methods: ['GET'])]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        // Load character stats (1:1 with User, may not exist before onboarding)
        $stats = $this->characterStatsRepository->findByUser($user);

        // Load active (non-soft-deleted) inventory items
        $inventoryItems = $this->userInventoryRepository->findActiveByUser($user);

        // Load unlocked skills
        $userSkills = $this->userSkillRepository->findByUser($user);

        // Calculate total XP from all experience log entries
        $totalXp = $this->experienceLogRepository->getTotalXpByUser($user);

        // Build inventory array with item details
        $inventoryData = [];
        foreach ($inventoryItems as $item) {
            $inventoryData[] = [
                'id' => $item->getId()->toRfc4122(),
                'itemName' => $item->getItemCatalog()->getName(),
                'quantity' => $item->getQuantity(),
                'equipped' => $item->isEquipped(),
            ];
        }

        // Build skills array with skill details
        $skillsData = [];
        foreach ($userSkills as $userSkill) {
            $skillsData[] = [
                'id' => $userSkill->getId()->toRfc4122(),
                'skillName' => $userSkill->getSkill()->getName(),
                'unlockedAt' => $userSkill->getUnlockedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        return $this->json([
            'id' => $user->getId()->toRfc4122(),
            'login' => $user->getLogin(),
            'displayName' => $user->getDisplayName(),
            'gender' => $user->getGender()?->value,
            'height' => $user->getHeight(),
            'weight' => $user->getWeight(),
            'characterRace' => $user->getCharacterRace()?->value,
            'workoutType' => $user->getWorkoutType()?->value,
            'trainingFrequency' => $user->getTrainingFrequency()?->value,
            'lifestyle' => $user->getLifestyle()?->value,
            'activityLevel' => $user->getActivityLevel()?->value,
            'desiredGoal' => $user->getDesiredGoal()?->value,
            'preferredWorkouts' => $user->getPreferredWorkouts(),
            'onboardingCompleted' => $user->isOnboardingCompleted(),
            'stats' => $stats !== null ? [
                'strength' => $stats->getStrength(),
                'dexterity' => $stats->getDexterity(),
                'constitution' => $stats->getConstitution(),
            ] : null,
            'inventory' => $inventoryData,
            'skills' => $skillsData,
            'totalXp' => $totalXp,
            'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $user->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }
}
