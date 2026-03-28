<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\User\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ProfileController extends AbstractController
{
    #[Route('/api/profile', name: 'api_profile', methods: ['GET'])]
    public function profile(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json([
            'id' => $user->getId()->toRfc4122(),
            'login' => $user->getLogin(),
            'displayName' => $user->getDisplayName(),
            'height' => $user->getHeight(),
            'weight' => $user->getWeight(),
            'workoutType' => $user->getWorkoutType()->value,
            'activityLevel' => $user->getActivityLevel()->value,
            'desiredGoal' => $user->getDesiredGoal()->value,
            'characterRace' => $user->getCharacterRace()->value,
            'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $user->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }
}
