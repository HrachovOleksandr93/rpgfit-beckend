<?php

declare(strict_types=1);

namespace App\Application\Test\Service;

use App\Domain\Config\Entity\GameSetting;
use App\Infrastructure\Config\Repository\GameSettingRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Resolve whether the test-harness API is currently enabled.
 *
 * The gate resolves in this order:
 *
 *   1. Env var `APP_TESTING_ENABLED` — when truthy the harness is always
 *      enabled regardless of DB state. Used by `.env.dev` / `.env.test` so
 *      CI and local dev get the harness without any admin action.
 *   2. `GameSetting` row `test_harness.enabled=true` combined with
 *      `test_harness.expires_at` in the future. This is the runtime
 *      override path used by `POST /api/test/meta/enable?ttl_min=60` so a
 *      superadmin can briefly open the harness in production without a
 *      redeploy.
 *
 * Anything else (no env, no setting, or expired setting) resolves to OFF
 * and `TestHarnessKillSwitchListener` returns 404 for every /api/test/*
 * request.
 *
 * Not marked `final` to match the "mockable application service" pattern
 * already used by `TargetUserResolver` and `AdminActionLogService`.
 */
class TestHarnessGate
{
    /** GameSetting key for the boolean runtime override. */
    public const string SETTING_KEY_ENABLED = 'test_harness.enabled';

    /** GameSetting key for the auto-revert deadline (ISO-8601 datetime). */
    public const string SETTING_KEY_EXPIRES_AT = 'test_harness.expires_at';

    /** Category used for the two settings above. */
    private const SETTING_CATEGORY = 'test_harness';

    public function __construct(
        private readonly GameSettingRepository $gameSettingRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * True iff the env var is enabled OR a non-expired GameSetting override
     * is present.
     */
    public function isEnabled(): bool
    {
        if ($this->isEnvEnabled()) {
            return true;
        }

        return $this->isSettingActive();
    }

    /** Env-only check, used to surface the `source` field in `/meta/status`. */
    public function isEnvEnabled(): bool
    {
        $raw = (string) ($_SERVER['APP_TESTING_ENABLED']
            ?? $_ENV['APP_TESTING_ENABLED']
            ?? '');

        return in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Return the setting expiry if the runtime override is currently active,
     * otherwise null.
     */
    public function getSettingExpiresAt(): ?\DateTimeImmutable
    {
        if (!$this->isSettingActive()) {
            return null;
        }

        return $this->readExpiresAt();
    }

    /**
     * Flip the GameSetting override on for `$ttlMinutes` minutes.
     *
     * Returns the absolute expiry timestamp so callers can display it.
     */
    public function enableForTtl(int $ttlMinutes): \DateTimeImmutable
    {
        $ttlMinutes = max(1, min(24 * 60, $ttlMinutes));
        $expiresAt = (new \DateTimeImmutable())->modify(sprintf('+%d minutes', $ttlMinutes));

        $this->writeSetting(self::SETTING_KEY_ENABLED, 'true');
        $this->writeSetting(self::SETTING_KEY_EXPIRES_AT, $expiresAt->format(\DateTimeInterface::ATOM));

        return $expiresAt;
    }

    /** Immediately turn the runtime override off. Idempotent. */
    public function disable(): void
    {
        $this->writeSetting(self::SETTING_KEY_ENABLED, 'false');
        $this->writeSetting(self::SETTING_KEY_EXPIRES_AT, '');
    }

    /**
     * Revert the override if its TTL has passed. Idempotent; intended for the
     * scheduled `app:testing-check` command.
     */
    public function revertIfExpired(): bool
    {
        $expiresAt = $this->readExpiresAt();
        if ($expiresAt === null) {
            return false;
        }

        if ($expiresAt > new \DateTimeImmutable()) {
            return false;
        }

        $this->disable();

        return true;
    }

    private function isSettingActive(): bool
    {
        $enabledRow = $this->gameSettingRepository->findByKey(self::SETTING_KEY_ENABLED);
        if ($enabledRow === null) {
            return false;
        }
        if (!$this->stringToBool($enabledRow->getValue())) {
            return false;
        }

        $expiresAt = $this->readExpiresAt();
        if ($expiresAt === null) {
            // Enabled without a deadline is treated as an indefinite manual flip
            // by an admin — still respected but surfaced with `expires_at = null`.
            return true;
        }

        return $expiresAt > new \DateTimeImmutable();
    }

    private function readExpiresAt(): ?\DateTimeImmutable
    {
        $row = $this->gameSettingRepository->findByKey(self::SETTING_KEY_EXPIRES_AT);
        if ($row === null) {
            return null;
        }
        $raw = trim($row->getValue());
        if ($raw === '') {
            return null;
        }
        try {
            return new \DateTimeImmutable($raw);
        } catch (\Exception) {
            return null;
        }
    }

    private function writeSetting(string $key, string $value): void
    {
        $existing = $this->gameSettingRepository->findByKey($key);
        if ($existing === null) {
            $setting = new GameSetting();
            $setting->setCategory(self::SETTING_CATEGORY);
            $setting->setKey($key);
            $setting->setValue($value);
            $setting->setDescription('Test harness kill-switch override (auto-managed).');
            $this->entityManager->persist($setting);
        } else {
            $existing->setValue($value);
        }

        $this->entityManager->flush();
    }

    private function stringToBool(string $raw): bool
    {
        return in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true);
    }
}
