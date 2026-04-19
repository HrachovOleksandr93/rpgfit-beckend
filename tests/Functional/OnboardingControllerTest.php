<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\User\Entity\User;
use App\Domain\User\Enum\ActivityLevel;
use App\Domain\User\Enum\DesiredGoal;
use App\Domain\User\Enum\WorkoutType;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Functional tests for POST /api/onboarding.
 *
 * Tests the full onboarding flow including validation, stat calculation,
 * duplicate display name handling, and auth requirements.
 */
class OnboardingControllerTest extends AbstractFunctionalTest
{
    private const string ONBOARDING_URL = '/api/onboarding';

    /**
     * Create a test user that has NOT completed onboarding (simulates OAuth-created user).
     */
    private function createOAuthUser(string $login = 'oauth@rpgfit.com'): User
    {
        $container = self::getContainer();

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setLogin($login);
        $user->setPassword($hasher->hashPassword($user, 'TempPass123'));
        $user->setOnboardingCompleted(false);

        $em->persist($user);
        $em->flush();

        return $user;
    }

    /**
     * Create a fully registered user (onboarding completed via registration endpoint).
     */
    private function createOnboardedUser(string $login = 'onboarded@rpgfit.com', string $displayName = 'ExistingHero'): User
    {
        $container = self::getContainer();

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setLogin($login);
        $user->setDisplayName($displayName);
        $user->setHeight(180.0);
        $user->setWeight(75.0);
        $user->setWorkoutType(WorkoutType::Cardio);
        $user->setActivityLevel(ActivityLevel::Active);
        $user->setDesiredGoal(DesiredGoal::LoseWeight);
        $user->setOnboardingCompleted(true);
        $user->setPassword($hasher->hashPassword($user, 'TempPass123'));

        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function getJwtToken(User $user): string
    {
        /** @var JWTTokenManagerInterface $jwtManager */
        $jwtManager = self::getContainer()->get(JWTTokenManagerInterface::class);

        return $jwtManager->create($user);
    }

    private function getValidOnboardingPayload(array $overrides = []): array
    {
        return array_merge([
            'displayName' => 'NewHero',
            'height' => 180.5,
            'weight' => 75.0,
            'gender' => 'male',
            'trainingFrequency' => 'moderate',
            'workoutType' => 'strength',
            'lifestyle' => 'moderate',
            'preferredWorkouts' => ['powerlifting', 'crossfit'],
        ], $overrides);
    }

    public function testSuccessfulOnboarding(): void
    {
        $user = $this->createOAuthUser();
        $token = $this->getJwtToken($user);

        $this->client->request(
            'POST',
            self::ONBOARDING_URL,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode($this->getValidOnboardingPayload()),
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Verify user data is returned
        $this->assertSame('NewHero', $response['displayName']);
        $this->assertSame('male', $response['gender']);
        $this->assertEquals(180.5, $response['height']);
        $this->assertEquals(75.0, $response['weight']);
        $this->assertSame('strength', $response['workoutType']);
        $this->assertTrue($response['onboardingCompleted']);

        // Verify training preferences are returned in separate key
        $this->assertArrayHasKey('trainingPreferences', $response);
        $this->assertNotNull($response['trainingPreferences']);
        $this->assertSame('moderate', $response['trainingPreferences']['trainingFrequency']);
        $this->assertSame('moderate', $response['trainingPreferences']['lifestyle']);
        $this->assertSame('strength', $response['trainingPreferences']['primaryTrainingStyle']);
        $this->assertSame(['powerlifting', 'crossfit'], $response['trainingPreferences']['preferredWorkouts']);

        // Verify stats are returned and sum to 30
        $this->assertArrayHasKey('stats', $response);
        $this->assertNotNull($response['stats']);
        $statsTotal = $response['stats']['strength'] + $response['stats']['dexterity'] + $response['stats']['constitution'];
        $this->assertSame(30, $statsTotal, 'Stats must sum to exactly 30.');
    }

    public function testDuplicateDisplayNameReturns409(): void
    {
        // Create an existing user with a specific display name
        $this->createOnboardedUser('existing@rpgfit.com', 'TakenName');

        // Create OAuth user and try to use the same display name
        $oauthUser = $this->createOAuthUser();
        $token = $this->getJwtToken($oauthUser);

        $this->client->request(
            'POST',
            self::ONBOARDING_URL,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode($this->getValidOnboardingPayload(['displayName' => 'TakenName'])),
        );

        $this->assertResponseStatusCodeSame(409);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('display name', $response['error']);
    }

    public function testInvalidDisplayNameReturns422(): void
    {
        $user = $this->createOAuthUser();
        $token = $this->getJwtToken($user);

        // Non-Latin characters in display name
        $this->client->request(
            'POST',
            self::ONBOARDING_URL,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode($this->getValidOnboardingPayload(['displayName' => 'Heroi-nombre!'])),
        );

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('displayName', $response['errors']);
    }

    public function testAlreadyCompletedOnboardingReturns409(): void
    {
        $user = $this->createOnboardedUser();
        $token = $this->getJwtToken($user);

        $this->client->request(
            'POST',
            self::ONBOARDING_URL,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode($this->getValidOnboardingPayload()),
        );

        $this->assertResponseStatusCodeSame(409);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('already completed', $response['error']);
    }

    public function testMissingFieldsReturns422(): void
    {
        $user = $this->createOAuthUser();
        $token = $this->getJwtToken($user);

        $this->client->request(
            'POST',
            self::ONBOARDING_URL,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode([]),
        );

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $response);
    }

    public function testWithoutAuthReturns401(): void
    {
        $this->client->request(
            'POST',
            self::ONBOARDING_URL,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($this->getValidOnboardingPayload()),
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
