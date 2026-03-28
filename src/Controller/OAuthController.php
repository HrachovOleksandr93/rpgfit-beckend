<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\User\Entity\LinkedAccount;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OAuthProvider;
use App\Infrastructure\User\Repository\LinkedAccountRepository;
use App\Infrastructure\User\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * API controller for OAuth authentication and account linking.
 *
 * Handles two endpoints:
 * - POST /api/auth/oauth (public): Authenticate via an external provider, returning a JWT.
 *   Creates a new user if needed, or links an existing user to the provider.
 * - POST /api/auth/link-account (authenticated): Link an additional OAuth provider
 *   to the currently authenticated user.
 *
 * Token verification is a placeholder -- actual provider token validation will be
 * implemented when provider API keys are obtained.
 */
class OAuthController extends AbstractController
{
    public function __construct(
        private readonly LinkedAccountRepository $linkedAccountRepository,
        private readonly UserRepository $userRepository,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * Authenticate via OAuth provider.
     *
     * Flow:
     * 1. Validate provider enum and token (placeholder: just check non-empty)
     * 2. Look up LinkedAccount by (provider, providerUserId)
     * 3. If found, return JWT for the linked user
     * 4. If not found, check if a User exists with this email
     *    - If user exists, create LinkedAccount and return JWT
     *    - If no user, create new User + LinkedAccount and return JWT
     */
    #[Route('/api/auth/oauth', name: 'api_auth_oauth', methods: ['POST'])]
    public function oauth(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(
                ['error' => 'Invalid JSON body.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        // Validate required fields
        $provider = $data['provider'] ?? '';
        $providerUserId = $data['providerUserId'] ?? '';
        $email = $data['email'] ?? '';
        $token = $data['token'] ?? '';

        if (empty($provider) || empty($providerUserId) || empty($email) || empty($token)) {
            return $this->json(
                ['error' => 'Missing required fields: provider, providerUserId, email, token.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        // Validate provider enum value
        $oauthProvider = OAuthProvider::tryFrom($provider);
        if ($oauthProvider === null) {
            return $this->json(
                ['error' => 'Invalid provider. Allowed: google, apple, facebook.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        // Placeholder token verification: just ensure the token is not empty
        // Actual verification will be implemented when provider API keys are obtained
        if (empty(trim($token))) {
            return $this->json(
                ['error' => 'Invalid token.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $isNewUser = false;

        // Step 1: Look up existing linked account
        $linkedAccount = $this->linkedAccountRepository->findByProviderAndUserId($oauthProvider, $providerUserId);

        if ($linkedAccount !== null) {
            // Existing linked account found -- return JWT for the linked user
            $user = $linkedAccount->getUser();
        } else {
            // Step 2: Check if a user already exists with this email
            $user = $this->userRepository->findByLogin($email);

            if ($user !== null) {
                // User exists but not linked to this provider -- auto-link
                $linkedAccount = new LinkedAccount();
                $linkedAccount->setUser($user);
                $linkedAccount->setProvider($oauthProvider);
                $linkedAccount->setProviderUserId($providerUserId);
                $linkedAccount->setEmail($email);
                $this->linkedAccountRepository->save($linkedAccount);
            } else {
                // Step 3: No user exists -- create new user and linked account
                $isNewUser = true;
                $user = new User();
                $user->setLogin($email);
                // Set a random password since OAuth users do not use password auth
                $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(16))));
                $user->setOnboardingCompleted(false);
                $this->userRepository->save($user);

                $linkedAccount = new LinkedAccount();
                $linkedAccount->setUser($user);
                $linkedAccount->setProvider($oauthProvider);
                $linkedAccount->setProviderUserId($providerUserId);
                $linkedAccount->setEmail($email);
                $this->linkedAccountRepository->save($linkedAccount);
            }
        }

        // Generate JWT token for the user
        $jwt = $this->jwtManager->create($user);

        return $this->json([
            'token' => $jwt,
            'onboardingCompleted' => $user->isOnboardingCompleted(),
            'isNewUser' => $isNewUser,
        ]);
    }

    /**
     * Link an additional OAuth provider to the currently authenticated user.
     *
     * Returns 200 on success, 409 if the provider account is already linked to another user.
     */
    #[Route('/api/auth/link-account', name: 'api_auth_link_account', methods: ['POST'])]
    public function linkAccount(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(
                ['error' => 'Invalid JSON body.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $provider = $data['provider'] ?? '';
        $providerUserId = $data['providerUserId'] ?? '';
        $email = $data['email'] ?? '';
        $token = $data['token'] ?? '';

        if (empty($provider) || empty($providerUserId) || empty($email) || empty($token)) {
            return $this->json(
                ['error' => 'Missing required fields: provider, providerUserId, email, token.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $oauthProvider = OAuthProvider::tryFrom($provider);
        if ($oauthProvider === null) {
            return $this->json(
                ['error' => 'Invalid provider. Allowed: google, apple, facebook.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        // Check if this provider account is already linked to another user
        $existingLink = $this->linkedAccountRepository->findByProviderAndUserId($oauthProvider, $providerUserId);
        if ($existingLink !== null) {
            if ($existingLink->getUser()->getId()->toRfc4122() !== $user->getId()->toRfc4122()) {
                // Already linked to a different user -- conflict
                return $this->json(
                    ['error' => 'This provider account is already linked to another user.'],
                    Response::HTTP_CONFLICT,
                );
            }
            // Already linked to this user -- idempotent success
            return $this->json(['message' => 'Account already linked.']);
        }

        // Create the new link
        $linkedAccount = new LinkedAccount();
        $linkedAccount->setUser($user);
        $linkedAccount->setProvider($oauthProvider);
        $linkedAccount->setProviderUserId($providerUserId);
        $linkedAccount->setEmail($email);
        $this->linkedAccountRepository->save($linkedAccount);

        return $this->json(['message' => 'Account linked successfully.']);
    }
}
