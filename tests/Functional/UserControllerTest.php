<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Character\Entity\CharacterStats;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\ActivityLevel;
use App\Domain\User\Enum\DesiredGoal;
use App\Domain\User\Enum\Gender;
use App\Domain\User\Entity\UserTrainingPreference;
use App\Domain\User\Enum\Lifestyle;
use App\Domain\User\Enum\TrainingFrequency;
use App\Domain\User\Enum\WorkoutType;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Functional tests for GET /api/user.
 *
 * Tests the full user profile endpoint including stats, inventory, skills, and XP.
 */
class UserControllerTest extends AbstractFunctionalTest
{
    private const string USER_URL = '/api/user';

    private function createFullUser(): User
    {
        $container = self::getContainer();

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setLogin('hero@rpgfit.com');
        $user->setDisplayName('TestHero');
        $user->setHeight(180.0);
        $user->setWeight(75.5);
        $user->setGender(Gender::Male);
        $user->setWorkoutType(WorkoutType::Strength);
        $user->setActivityLevel(ActivityLevel::Active);
        $user->setDesiredGoal(DesiredGoal::GainMass);
        $user->setOnboardingCompleted(true);
        $user->setPassword($hasher->hashPassword($user, 'SecurePass123'));

        $em->persist($user);

        // Create training preferences (separate entity)
        $trainingPref = new UserTrainingPreference();
        $trainingPref->setUser($user);
        $trainingPref->setTrainingFrequency(TrainingFrequency::Moderate);
        $trainingPref->setLifestyle(Lifestyle::Moderate);
        $trainingPref->setPrimaryTrainingStyle(WorkoutType::Strength);
        $trainingPref->setPreferredWorkouts(['powerlifting', 'crossfit']);
        $em->persist($trainingPref);

        // Create character stats
        $stats = new CharacterStats();
        $stats->setUser($user);
        $stats->setStrength(12);
        $stats->setDexterity(8);
        $stats->setConstitution(10);
        $em->persist($stats);

        $em->flush();

        return $user;
    }

    private function createOAuthUser(): User
    {
        $container = self::getContainer();

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setLogin('oauth@rpgfit.com');
        $user->setPassword($hasher->hashPassword($user, 'TempPass123'));
        $user->setOnboardingCompleted(false);

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

    public function testGetUserReturnsFullProfile(): void
    {
        $user = $this->createFullUser();
        $token = $this->getJwtToken($user);

        $this->client->request(
            'GET',
            self::USER_URL,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Verify all profile fields
        $this->assertSame('hero@rpgfit.com', $response['login']);
        $this->assertSame('TestHero', $response['displayName']);
        $this->assertSame('male', $response['gender']);
        $this->assertEquals(180.0, $response['height']);
        $this->assertEquals(75.5, $response['weight']);
        $this->assertSame('strength', $response['workoutType']);
        $this->assertSame('active', $response['activityLevel']);
        $this->assertSame('gain_mass', $response['desiredGoal']);
        $this->assertTrue($response['onboardingCompleted']);

        // Verify training preferences are in separate key
        $this->assertArrayHasKey('trainingPreferences', $response);
        $this->assertNotNull($response['trainingPreferences']);
        $this->assertSame('moderate', $response['trainingPreferences']['trainingFrequency']);
        $this->assertSame('moderate', $response['trainingPreferences']['lifestyle']);
        $this->assertSame('strength', $response['trainingPreferences']['primaryTrainingStyle']);
        $this->assertSame(['powerlifting', 'crossfit'], $response['trainingPreferences']['preferredWorkouts']);

        // Verify stats
        $this->assertArrayHasKey('stats', $response);
        $this->assertSame(12, $response['stats']['strength']);
        $this->assertSame(8, $response['stats']['dexterity']);
        $this->assertSame(10, $response['stats']['constitution']);

        // Verify related data arrays exist
        $this->assertIsArray($response['inventory']);
        $this->assertIsArray($response['skills']);
        $this->assertArrayHasKey('totalXp', $response);
        $this->assertArrayHasKey('createdAt', $response);
        $this->assertArrayHasKey('updatedAt', $response);
    }

    public function testGetUserWithoutAuthReturns401(): void
    {
        $this->client->request('GET', self::USER_URL);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetUserBeforeOnboarding(): void
    {
        $user = $this->createOAuthUser();
        $token = $this->getJwtToken($user);

        $this->client->request(
            'GET',
            self::USER_URL,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Onboarding not completed
        $this->assertFalse($response['onboardingCompleted']);
        $this->assertNull($response['displayName']);
        $this->assertNull($response['stats']);
        $this->assertNull($response['gender']);
        $this->assertNull($response['height']);
        $this->assertNull($response['weight']);
    }
}
