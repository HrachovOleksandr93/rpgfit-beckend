<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Application\PsychProfile\Service\StatusAssignmentService;
use App\Controller\PsychProfileController;
use App\Domain\Config\Entity\GameSetting;
use App\Domain\PsychProfile\Enum\PsychStatus;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\ActivityLevel;
use App\Domain\User\Enum\DesiredGoal;
use App\Domain\User\Enum\WorkoutType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * End-to-end coverage of the Psych Profiler HTTP surface.
 *
 * - Feature-flag 404 when the env is off.
 * - Opt-in gate makes `/today` return `not_opted_in`.
 * - Happy path check-in + status-valid-until.
 * - Trend + export + history delete.
 */
class PsychProfileControllerTest extends AbstractFunctionalTest
{
    private const LOGIN = 'psych-hero@rpgfit.com';
    private const PASSWORD = 'SecurePass123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPsychSettings();
    }

    private function seedPsychSettings(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();

        $rules = (string) json_encode([
            ['when' => ['mood' => ['ON_EDGE']], 'assign' => 'SCATTERED'],
            ['when' => ['mood' => ['DRAINED']], 'assign' => 'WEARY'],
            ['when' => ['mood' => ['AT_EASE', 'NEUTRAL'], 'intent' => ['REST']], 'assign' => 'DORMANT'],
            ['when' => ['mood' => ['ENERGIZED'], 'intent' => ['PUSH'], 'energy_min' => 4], 'assign' => 'CHARGED'],
            ['when' => [], 'assign' => 'STEADY'],
        ]);

        $rulesSetting = (new GameSetting())
            ->setCategory('psych')
            ->setKey(StatusAssignmentService::SETTING_KEY)
            ->setValue($rules);
        $em->persist($rulesSetting);

        $multipliers = (new GameSetting())
            ->setCategory('psych')
            ->setKey('psych.xp_multipliers')
            ->setValue((string) json_encode([
                'CHARGED' => 1.15,
                'STEADY' => 1.0,
                'DORMANT' => 1.20,
                'WEARY' => 1.0,
                'SCATTERED' => 1.0,
            ]));
        $em->persist($multipliers);

        $em->flush();
    }

    private function createTestUser(): User
    {
        $container = self::getContainer();

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);
        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setLogin(self::LOGIN);
        $user->setDisplayName('PsychHero');
        $user->setHeight(180.0);
        $user->setWeight(75.0);
        $user->setWorkoutType(WorkoutType::Cardio);
        $user->setActivityLevel(ActivityLevel::Active);
        $user->setDesiredGoal(DesiredGoal::LoseWeight);
        $user->setPassword($hasher->hashPassword($user, self::PASSWORD));

        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function getToken(): string
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            (string) json_encode(['login' => self::LOGIN, 'password' => self::PASSWORD]),
        );

        $body = json_decode((string) $this->client->getResponse()->getContent(), true);

        return (string) $body['token'];
    }

    private function authHeaders(string $token): array
    {
        return [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json',
        ];
    }

    private function setFlag(bool $enabled): ?string
    {
        $original = $_SERVER[PsychProfileController::ENV_FLAG] ?? $_ENV[PsychProfileController::ENV_FLAG] ?? null;
        $value = $enabled ? 'true' : 'false';
        $_SERVER[PsychProfileController::ENV_FLAG] = $value;
        $_ENV[PsychProfileController::ENV_FLAG] = $value;

        return is_string($original) ? $original : null;
    }

    private function restoreFlag(?string $original): void
    {
        if ($original === null) {
            unset($_SERVER[PsychProfileController::ENV_FLAG], $_ENV[PsychProfileController::ENV_FLAG]);

            return;
        }
        $_SERVER[PsychProfileController::ENV_FLAG] = $original;
        $_ENV[PsychProfileController::ENV_FLAG] = $original;
    }

    public function testFeatureFlagOffReturns404(): void
    {
        $this->createTestUser();
        $original = $this->setFlag(false);

        try {
            $token = $this->getToken();
            $this->client->request('GET', '/api/psych/today', [], [], $this->authHeaders($token));
            self::assertSame(404, $this->client->getResponse()->getStatusCode());
        } finally {
            $this->restoreFlag($original);
        }
    }

    public function testTodayBeforeOptInReturnsNotOptedIn(): void
    {
        $this->createTestUser();
        $original = $this->setFlag(true);

        try {
            $token = $this->getToken();
            $this->client->request('GET', '/api/psych/today', [], [], $this->authHeaders($token));

            self::assertSame(200, $this->client->getResponse()->getStatusCode());
            $body = json_decode((string) $this->client->getResponse()->getContent(), true);
            self::assertFalse($body['isDue']);
            self::assertSame('not_opted_in', $body['reason']);
        } finally {
            $this->restoreFlag($original);
        }
    }

    public function testOptInThenCheckInHappyPath(): void
    {
        $this->createTestUser();
        $original = $this->setFlag(true);

        try {
            $token = $this->getToken();
            $headers = $this->authHeaders($token);

            $this->client->request('POST', '/api/psych/opt-in', [], [], $headers);
            self::assertSame(200, $this->client->getResponse()->getStatusCode());
            $optInBody = json_decode((string) $this->client->getResponse()->getContent(), true);
            self::assertTrue($optInBody['featureOptedIn']);

            // Submit a CHARGED-qualifying check-in.
            $payload = (string) json_encode([
                'mood' => 'ENERGIZED',
                'energy' => 4,
                'intent' => 'PUSH',
                'skipped' => false,
            ]);
            $this->client->request('POST', '/api/psych/check-in', [], [], $headers, $payload);
            self::assertSame(201, $this->client->getResponse()->getStatusCode());
            $checkInBody = json_decode((string) $this->client->getResponse()->getContent(), true);
            self::assertSame(PsychStatus::CHARGED->value, $checkInBody['assignedStatus']);
            self::assertArrayHasKey('badgeCopyUa', $checkInBody);
            self::assertArrayHasKey('statusValidUntil', $checkInBody);

            // /today now reports the stored status.
            $this->client->request('GET', '/api/psych/today', [], [], $headers);
            $todayBody = json_decode((string) $this->client->getResponse()->getContent(), true);
            self::assertSame(PsychStatus::CHARGED->value, $todayBody['lastStatus']);
            self::assertFalse($todayBody['isDue']);
        } finally {
            $this->restoreFlag($original);
        }
    }

    public function testTrendEndpointReturnsPoints(): void
    {
        $this->createTestUser();
        $original = $this->setFlag(true);

        try {
            $token = $this->getToken();
            $headers = $this->authHeaders($token);

            $this->client->request('POST', '/api/psych/opt-in', [], [], $headers);
            $payload = (string) json_encode([
                'mood' => 'ENERGIZED',
                'energy' => 4,
                'intent' => 'PUSH',
                'skipped' => false,
            ]);
            $this->client->request('POST', '/api/psych/check-in', [], [], $headers, $payload);

            $this->client->request('GET', '/api/psych/trend?window=7', [], [], $headers);
            self::assertSame(200, $this->client->getResponse()->getStatusCode());
            $body = json_decode((string) $this->client->getResponse()->getContent(), true);
            self::assertSame(7, $body['window']);
            self::assertNotEmpty($body['points']);
            self::assertSame(PsychStatus::CHARGED->value, $body['dominantStatus']);
        } finally {
            $this->restoreFlag($original);
        }
    }

    public function testExportReturnsProfileAndCheckIns(): void
    {
        $this->createTestUser();
        $original = $this->setFlag(true);

        try {
            $token = $this->getToken();
            $headers = $this->authHeaders($token);

            $this->client->request('POST', '/api/psych/opt-in', [], [], $headers);
            $this->client->request(
                'POST',
                '/api/psych/check-in',
                [],
                [],
                $headers,
                (string) json_encode(['mood' => 'DRAINED', 'energy' => 2, 'intent' => 'REST', 'skipped' => false]),
            );

            $this->client->request('GET', '/api/psych/export', [], [], $headers);
            self::assertSame(200, $this->client->getResponse()->getStatusCode());
            $body = json_decode((string) $this->client->getResponse()->getContent(), true);
            self::assertNotNull($body['profile']);
            self::assertCount(1, $body['checkIns']);
            self::assertSame(PsychStatus::WEARY->value, $body['checkIns'][0]['assignedStatus']);
        } finally {
            $this->restoreFlag($original);
        }
    }

    public function testDeleteHistoryReturns204AndWipesRows(): void
    {
        $this->createTestUser();
        $original = $this->setFlag(true);

        try {
            $token = $this->getToken();
            $headers = $this->authHeaders($token);

            $this->client->request('POST', '/api/psych/opt-in', [], [], $headers);
            $this->client->request(
                'POST',
                '/api/psych/check-in',
                [],
                [],
                $headers,
                (string) json_encode(['mood' => 'ON_EDGE', 'energy' => 5, 'intent' => 'PUSH', 'skipped' => false]),
            );

            $this->client->request('DELETE', '/api/psych/history', [], [], $headers);
            self::assertSame(204, $this->client->getResponse()->getStatusCode());

            $this->client->request('GET', '/api/psych/export', [], [], $headers);
            $body = json_decode((string) $this->client->getResponse()->getContent(), true);
            self::assertSame([], $body['checkIns']);
        } finally {
            $this->restoreFlag($original);
        }
    }

    public function testCheckInWithoutMoodWhenNotSkippedFails(): void
    {
        $this->createTestUser();
        $original = $this->setFlag(true);

        try {
            $token = $this->getToken();
            $headers = $this->authHeaders($token);

            $this->client->request('POST', '/api/psych/opt-in', [], [], $headers);
            $this->client->request(
                'POST',
                '/api/psych/check-in',
                [],
                [],
                $headers,
                (string) json_encode(['skipped' => false]),
            );
            self::assertSame(400, $this->client->getResponse()->getStatusCode());
        } finally {
            $this->restoreFlag($original);
        }
    }
}
