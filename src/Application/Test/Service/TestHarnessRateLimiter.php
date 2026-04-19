<?php

declare(strict_types=1);

namespace App\Application\Test\Service;

use App\Domain\User\Entity\User;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * Simple fixed-window rate limiter for the test harness.
 *
 * The project's composer.json does not yet require `symfony/rate-limiter`,
 * so we implement a pared-down limiter on top of the PSR-6 cache pool
 * (already wired to APCu / filesystem in dev and to Redis in prod via
 * `config/packages/cache.yaml`).
 *
 * Policy: 60 requests per minute per authenticated user. When exceeded we
 * throw `TooManyRequestsHttpException` with a `Retry-After` header-friendly
 * delay. Anonymous callers should never reach the harness (security stops
 * them at ROLE_TESTER) — we still key on the user login to be safe.
 *
 * This service can be replaced 1-for-1 with the Symfony RateLimiter
 * component when it lands; the public `consume()` signature mirrors the
 * intended trait-based usage from the spec.
 */
final class TestHarnessRateLimiter
{
    private const WINDOW_SECONDS = 60;
    private const MAX_HITS = 60;

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    /**
     * Register one hit for the caller. Throws 429 when the bucket is full.
     *
     * @throws TooManyRequestsHttpException When the 60 req/min quota is exhausted.
     */
    public function consume(User $user, string $endpoint): void
    {
        $now = time();
        $windowStart = $now - ($now % self::WINDOW_SECONDS);
        $key = sprintf(
            'test_harness.ratelimit.%s.%d',
            preg_replace('/[^A-Za-z0-9_.-]/', '_', $user->getLogin()) ?? 'anon',
            $windowStart,
        );

        $item = $this->cache->getItem($key);
        /** @var int $hits */
        $hits = $item->isHit() ? (int) $item->get() : 0;
        $hits++;

        if ($hits > self::MAX_HITS) {
            $retryAfter = (self::WINDOW_SECONDS - ($now - $windowStart)) ?: 1;
            throw new TooManyRequestsHttpException(
                $retryAfter,
                sprintf('Test-harness rate limit exceeded (%d/min).', self::MAX_HITS),
            );
        }

        $item->set($hits);
        $item->expiresAfter(self::WINDOW_SECONDS);
        $this->cache->save($item);
    }
}
