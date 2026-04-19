<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\User\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * API controller for the authenticated user's profile.
 *
 * Returns the current user's account and RPG character data to the mobile app.
 * Requires JWT authentication -- the #[CurrentUser] attribute resolves the
 * authenticated User entity from the security token.
 *
 * Flow: Mobile App (with JWT) -> GET /api/profile -> User entity from token -> JSON response
 */
class ProfileController extends AbstractController
{
    /**
     * Return the authenticated user's full profile as JSON.
     *
     * Used by the mobile app to populate the profile screen and RPG character view.
     * Handles nullable fields for users who have not completed onboarding.
     */
    #[Route('/api/profile', name: 'api_profile', methods: ['GET'])]
    public function profile(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json([
            'id' => $user->getId()->toRfc4122(),
            'login' => $user->getLogin(),
            'displayName' => $user->getDisplayName(),
            'height' => $user->getHeight(),
            'weight' => $user->getWeight(),
            'workoutType' => $user->getWorkoutType()?->value,
            'activityLevel' => $user->getActivityLevel()?->value,
            'desiredGoal' => $user->getDesiredGoal()?->value,
            'onboardingCompleted' => $user->isOnboardingCompleted(),
            'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $user->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }
}
