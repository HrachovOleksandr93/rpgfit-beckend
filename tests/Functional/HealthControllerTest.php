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

class HealthControllerTest extends WebTestCase
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

    private function getValidSyncPayload(array $overrides = []): array
    {
        return array_merge([
            'platform' => 'ios',
            'dataPoints' => [
                [
                    'externalUuid' => 'ext-uuid-001',
                    'type' => 'STEPS',
                    'value' => 8432.0,
                    'unit' => 'count',
                    'dateFrom' => '2026-03-28T08:00:00+00:00',
                    'dateTo' => '2026-03-28T09:00:00+00:00',
                    'sourceApp' => 'com.apple.health',
                    'recordingMethod' => 'automatic',
                ],
                [
                    'externalUuid' => 'ext-uuid-002',
                    'type' => 'HEART_RATE',
                    'value' => 72.5,
                    'unit' => 'bpm',
                    'dateFrom' => '2026-03-28T08:00:00+00:00',
                    'dateTo' => '2026-03-28T08:01:00+00:00',
                    'sourceApp' => 'com.apple.health',
                    'recordingMethod' => 'automatic',
                ],
                [
                    'externalUuid' => 'ext-uuid-003',
                    'type' => 'ACTIVE_ENERGY_BURNED',
                    'value' => 312.5,
                    'unit' => 'kcal',
                    'dateFrom' => '2026-03-28T08:00:00+00:00',
                    'dateTo' => '2026-03-28T09:00:00+00:00',
                    'sourceApp' => 'com.apple.health',
                    'recordingMethod' => 'automatic',
                ],
                [
                    'externalUuid' => 'ext-uuid-004',
                    'type' => 'DISTANCE_DELTA',
                    'value' => 5200.0,
                    'unit' => 'meters',
                    'dateFrom' => '2026-03-28T08:00:00+00:00',
                    'dateTo' => '2026-03-28T09:00:00+00:00',
                    'sourceApp' => 'com.apple.health',
                    'recordingMethod' => 'automatic',
                ],
                [
                    'externalUuid' => 'ext-uuid-005',
                    'type' => 'SLEEP_DEEP',
                    'value' => 120.0,
                    'unit' => 'minutes',
                    'dateFrom' => '2026-03-28T00:00:00+00:00',
                    'dateTo' => '2026-03-28T02:00:00+00:00',
                    'sourceApp' => 'com.apple.health',
                    'recordingMethod' => 'automatic',
                ],
                [
                    'externalUuid' => 'ext-uuid-006',
                    'type' => 'SLEEP_REM',
                    'value' => 90.0,
                    'unit' => 'minutes',
                    'dateFrom' => '2026-03-28T02:00:00+00:00',
                    'dateTo' => '2026-03-28T03:30:00+00:00',
                    'sourceApp' => 'com.apple.health',
                    'recordingMethod' => 'automatic',
                ],
                [
                    'externalUuid' => 'ext-uuid-007',
                    'type' => 'WORKOUT',
                    'value' => 45.0,
                    'unit' => 'minutes',
                    'dateFrom' => '2026-03-28T07:00:00+00:00',
                    'dateTo' => '2026-03-28T07:45:00+00:00',
                    'sourceApp' => 'com.apple.health',
                    'recordingMethod' => 'automatic',
                ],
            ],
        ], $overrides);
    }

    public function testSyncWithValidData(): void
    {
        $this->createTestUser();
        $token = $this->getToken();

        $this->client->request(
            'POST',
            '/api/health/sync',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode($this->getValidSyncPayload()),
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('accepted', $response);
        $this->assertArrayHasKey('duplicates_skipped', $response);
        $this->assertSame(7, $response['accepted']);
        $this->assertSame(0, $response['duplicates_skipped']);
    }

    public function testSyncWithoutAuthReturns401(): void
    {
        $this->client->request(
            'POST',
            '/api/health/sync',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($this->getValidSyncPayload()),
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testSyncWithInvalidDataReturns422(): void
    {
        $this->createTestUser();
        $token = $this->getToken();

        // Missing platform, empty data points
        $this->client->request(
            'POST',
            '/api/health/sync',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'platform' => null,
                'dataPoints' => [],
            ]),
        );

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $response);
    }

    public function testSyncWithInvalidPlatformReturns422(): void
    {
        $this->createTestUser();
        $token = $this->getToken();

        $payload = $this->getValidSyncPayload(['platform' => 'windows']);

        $this->client->request(
            'POST',
            '/api/health/sync',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(422);
    }

    public function testSyncWithInvalidDataTypeReturns422(): void
    {
        $this->createTestUser();
        $token = $this->getToken();

        $payload = $this->getValidSyncPayload();
        $payload['dataPoints'][0]['type'] = 'INVALID_TYPE';

        $this->client->request(
            'POST',
            '/api/health/sync',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(422);
    }

    public function testDuplicateHandling(): void
    {
        $this->createTestUser();
        $token = $this->getToken();

        $payload = $this->getValidSyncPayload();

        // First sync
        $this->client->request(
            'POST',
            '/api/health/sync',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(200);
        $response1 = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(7, $response1['accepted']);
        $this->assertSame(0, $response1['duplicates_skipped']);

        // Second sync with same data
        $this->client->request(
            'POST',
            '/api/health/sync',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(200);
        $response2 = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(0, $response2['accepted']);
        $this->assertSame(7, $response2['duplicates_skipped']);
    }

    public function testSummaryEndpoint(): void
    {
        $this->createTestUser();
        $token = $this->getToken();

        // First send some data
        $this->client->request(
            'POST',
            '/api/health/sync',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode($this->getValidSyncPayload()),
        );
        $this->assertResponseStatusCodeSame(200);

        // Then get summary
        $this->client->request(
            'GET',
            '/api/health/summary?date=2026-03-28',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('2026-03-28', $response['date']);
        $this->assertEquals(8432.0, $response['steps']);
        $this->assertEquals(312.5, $response['active_energy']);
        $this->assertEquals(5200.0, $response['distance']);
        $this->assertEquals(210.0, $response['sleep_minutes']); // 120 deep + 90 rem
        $this->assertEquals(72.5, $response['average_heart_rate']);
        $this->assertEquals(45.0, $response['workout_minutes']);
    }

    public function testSummaryWithoutAuthReturns401(): void
    {
        $this->client->request('GET', '/api/health/summary?date=2026-03-28');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testSyncStatusEndpoint(): void
    {
        $this->createTestUser();
        $token = $this->getToken();

        // First send some data
        $this->client->request(
            'POST',
            '/api/health/sync',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode($this->getValidSyncPayload()),
        );
        $this->assertResponseStatusCodeSame(200);

        // Then check sync status
        $this->client->request(
            'GET',
            '/api/health/sync-status',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('STEPS', $response);
        $this->assertArrayHasKey('last_synced_at', $response['STEPS']);
        $this->assertArrayHasKey('points_count', $response['STEPS']);
        $this->assertSame(1, $response['STEPS']['points_count']);

        $this->assertArrayHasKey('HEART_RATE', $response);
        $this->assertSame(1, $response['HEART_RATE']['points_count']);
    }

    public function testSyncStatusWithoutAuthReturns401(): void
    {
        $this->client->request('GET', '/api/health/sync-status');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testSyncWithInvalidJsonReturns422(): void
    {
        $this->createTestUser();
        $token = $this->getToken();

        $this->client->request(
            'POST',
            '/api/health/sync',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            'not-valid-json',
        );

        $this->assertResponseStatusCodeSame(422);
    }

    public function testSyncWithMissingDataPointFieldsReturns422(): void
    {
        $this->createTestUser();
        $token = $this->getToken();

        $this->client->request(
            'POST',
            '/api/health/sync',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'platform' => 'ios',
                'dataPoints' => [
                    [
                        // Missing all required fields
                    ],
                ],
            ]),
        );

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $response);
    }
}
