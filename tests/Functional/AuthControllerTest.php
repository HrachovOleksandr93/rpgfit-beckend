<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\User\Entity\User;
use App\Domain\User\Enum\ActivityLevel;
use App\Domain\User\Enum\CharacterRace;
use App\Domain\User\Enum\DesiredGoal;
use App\Domain\User\Enum\WorkoutType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function createTestUser(string $login = 'hero@rpgfit.com', string $password = 'SecurePass123'): User
    {
        $container = self::getContainer();

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setLogin($login);
        $user->setDisplayName('TestHero');
        $user->setHeight(180.0);
        $user->setWeight(75.5);
        $user->setWorkoutType(WorkoutType::Cardio);
        $user->setActivityLevel(ActivityLevel::Active);
        $user->setDesiredGoal(DesiredGoal::LoseWeight);
        $user->setCharacterRace(CharacterRace::Orc);
        $user->setPassword($hasher->hashPassword($user, $password));

        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function getToken(string $login = 'hero@rpgfit.com', string $password = 'SecurePass123'): string
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['login' => $login, 'password' => $password]),
        );

        $response = json_decode($this->client->getResponse()->getContent(), true);

        return $response['token'];
    }

    public function testLoginWithValidCredentials(): void
    {
        $this->createTestUser();

        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['login' => 'hero@rpgfit.com', 'password' => 'SecurePass123']),
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $response);
        $this->assertNotEmpty($response['token']);
    }

    public function testLoginWithInvalidPassword(): void
    {
        $this->createTestUser();

        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['login' => 'hero@rpgfit.com', 'password' => 'WrongPassword']),
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginWithNonExistentUser(): void
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['login' => 'nonexistent@rpgfit.com', 'password' => 'SomePassword']),
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAccessProtectedEndpointWithoutToken(): void
    {
        $this->client->request('GET', '/api/profile');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAccessProtectedEndpointWithValidToken(): void
    {
        $this->createTestUser();
        $token = $this->getToken();

        $this->client->request(
            'GET',
            '/api/profile',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('hero@rpgfit.com', $response['login']);
        $this->assertSame('TestHero', $response['displayName']);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('height', $response);
        $this->assertArrayHasKey('weight', $response);
    }

    public function testAccessProtectedEndpointWithInvalidToken(): void
    {
        $this->client->request(
            'GET',
            '/api/profile',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer invalid.token.here'],
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testRegistrationStillWorksWithoutAuth(): void
    {
        $payload = [
            'login' => 'newuser@rpgfit.com',
            'password' => 'SecurePass123',
            'displayName' => 'NewHero',
            'height' => 175.0,
            'weight' => 70.0,
            'workoutType' => 'cardio',
            'activityLevel' => 'active',
            'desiredGoal' => 'lose_weight',
            'characterRace' => 'orc',
        ];

        $this->client->request(
            'POST',
            '/api/registration',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(201);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('newuser@rpgfit.com', $response['login']);
    }
}
