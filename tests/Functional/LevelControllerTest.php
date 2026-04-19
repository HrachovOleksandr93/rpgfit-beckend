<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Config\Entity\GameSetting;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\ActivityLevel;
use App\Domain\User\Enum\DesiredGoal;
use App\Domain\User\Enum\WorkoutType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Functional tests for the LevelController endpoints.
 */
class LevelControllerTest extends AbstractFunctionalTest
{
    protected function setUp(): void
    {
        parent::setUp();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();

        // Seed the minimal game settings needed for leveling
        $this->seedGameSettings($em);
    }

    private function seedGameSettings(EntityManagerInterface $em): void
    {
        $settings = [
            ['leveling', 'level_formula_quad', '4.2'],
            ['leveling', 'level_formula_linear', '28'],
            ['leveling', 'level_max', '100'],
        ];

        foreach ($settings as [$category, $key, $value]) {
            $s = new GameSetting();
            $s->setCategory($category);
            $s->setKey($key);
            $s->setValue($value);
            $em->persist($s);
        }

        $em->flush();
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

    public function testTableReturns100Levels(): void
    {
        $this->client->request('GET', '/api/levels/table');

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('levels', $response);
        $this->assertCount(100, $response['levels']);

        // Verify structure of first and last entries
        $first = $response['levels'][0];
        $this->assertSame(1, $first['level']);
        $this->assertArrayHasKey('xpRequired', $first);
        $this->assertArrayHasKey('totalXpRequired', $first);

        $last = $response['levels'][99];
        $this->assertSame(100, $last['level']);
    }

    public function testTableIsPublicNoAuthRequired(): void
    {
        // No Authorization header -- should still succeed
        $this->client->request('GET', '/api/levels/table');

        $this->assertResponseStatusCodeSame(200);
    }

    public function testProgressReturnsUserData(): void
    {
        $this->createTestUser();
        $token = $this->getToken();

        $this->client->request(
            'GET',
            '/api/levels/progress',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('level', $response);
        $this->assertArrayHasKey('totalXp', $response);
        $this->assertArrayHasKey('currentLevelXp', $response);
        $this->assertArrayHasKey('xpToNextLevel', $response);
        $this->assertArrayHasKey('progressPercent', $response);
    }

    public function testProgressWithoutAuthReturns401(): void
    {
        $this->client->request('GET', '/api/levels/progress');

        $this->assertResponseStatusCodeSame(401);
    }
}
