<?php

declare(strict_types=1);

namespace App\Tests\Functional\Test;

use App\Domain\Config\Entity\GameSetting;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\UserRole;
use App\Tests\Functional\AbstractFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Shared helpers for `tests/Functional/Test/*ControllerTest`:
 *   - seed game settings that XP / level services depend on
 *   - create a user with a specific role tier
 *   - fetch a JWT via the real `/api/login` pipeline
 */
abstract class AbstractTestHarnessFunctionalTest extends AbstractFunctionalTest
{
    protected function setUp(): void
    {
        parent::setUp();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();
        $this->seedGameSettings($em);
        $this->resetRateLimiterBuckets();
    }

    /**
     * The rate limiter stores hits in the PSR-6 cache pool, which by default
     * is filesystem-backed and persists between test runs. Clearing the pool
     * prevents a test from inheriting a near-full bucket from the previous
     * test in the same wall-clock minute.
     */
    protected function resetRateLimiterBuckets(): void
    {
        $container = self::getContainer();

        // The rate limiter autowires `Psr\Cache\CacheItemPoolInterface`,
        // which Symfony binds to `cache.app`. Fetching the interface here
        // mirrors the same binding the service uses in production.
        try {
            /** @var \Psr\Cache\CacheItemPoolInterface $cache */
            $cache = $container->get(\Psr\Cache\CacheItemPoolInterface::class);
            $cache->clear();
        } catch (\Throwable) {
            // Cache not wired in test env — rate limiter will no-op anyway.
        }
    }

    protected function seedGameSettings(EntityManagerInterface $em): void
    {
        $settings = [
            ['xp_rates', 'xp_rate_steps', '10'],
            ['xp_rates', 'xp_rate_active_energy', '25'],
            ['xp_rates', 'xp_rate_workout', '15'],
            ['xp_rates', 'xp_rate_distance', '10'],
            ['xp_rates', 'xp_rate_sleep', '10'],
            ['xp_rates', 'xp_rate_flights', '5'],
            ['xp_caps', 'xp_daily_cap', '3000'],
            ['xp_caps', 'xp_sleep_max_hours', '9'],
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

    protected function createUserWithRole(
        string $login,
        UserRole $role,
        string $password = 'Password123!',
    ): User {
        $container = self::getContainer();
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);
        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setLogin($login);
        $user->setDisplayName(substr(str_replace(['@', '.'], ['_', '_'], $login), 0, 25));
        $user->setPassword($hasher->hashPassword($user, $password));

        if ($role !== UserRole::USER) {
            $user->addRole($role);
        }

        $em->persist($user);
        $em->flush();

        return $user;
    }

    protected function login(string $login, string $password = 'Password123!'): string
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
        self::assertIsArray($response);
        self::assertArrayHasKey('token', $response, 'JWT login failed');

        return $response['token'];
    }

    protected function jsonRequest(
        string $method,
        string $path,
        string $token,
        array $body = [],
    ): array {
        $this->client->request(
            $method,
            $path,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            $body === [] ? null : json_encode($body),
        );

        $response = $this->client->getResponse();
        $raw = $response->getContent();
        $decoded = $raw === '' ? [] : json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
