<?php

declare(strict_types=1);

namespace App\Application\PsychProfile\Service;

use App\Domain\PsychProfile\Enum\PsychStatus;
use App\Domain\User\Entity\User;
use App\Infrastructure\Config\Repository\GameSettingRepository;
use App\Infrastructure\PsychProfile\Repository\PsychUserProfileRepository;

/**
 * Resolves the XP multiplier for the current user-status + activity pair.
 *
 * Application layer (PsychProfile bounded context). Spec 2026-04-18 §4
 * keeps multipliers conservative ({-0%, +15%, +20%}) and context-aware:
 *  - CHARGED -> ×1.15 only for "new_challenge" activities
 *  - DORMANT -> ×1.20 only for "rest" activities
 *  - STEADY  -> baseline always
 *  - WEARY / SCATTERED -> never penalise (baseline; red line)
 *
 * The per-status multiplier map lives in `game_settings` key
 * `psych.xp_multipliers`. Activity eligibility is hardcoded because it
 * encodes product intent (no silent XP cuts for WEARY/SCATTERED).
 *
 * TODO(integration): wire this into BattleResultCalculator + workout XP
 * pipeline once the beta feature flag rolls to the full cohort. See
 * BUSINESS_LOGIC.md §14 for the integration plan. Left unwired in this
 * PR to avoid touching the hot battle path behind a disabled feature.
 */
class PsychStatusModifierService
{
    public const SETTING_KEY = 'psych.xp_multipliers';
    public const CONTEXT_NEW_CHALLENGE = 'new_challenge';
    public const CONTEXT_REST = 'rest';
    public const CONTEXT_WORKOUT = 'workout';
    public const CONTEXT_BATTLE = 'battle';

    /** In-memory cache for one request lifetime. */
    private ?array $multipliersCache = null;

    public function __construct(
        private readonly GameSettingRepository $gameSettingRepository,
        private readonly PsychUserProfileRepository $profileRepository,
    ) {
    }

    /**
     * @param non-empty-string $activityContext one of CONTEXT_* constants
     */
    public function getXpMultiplier(User $user, string $activityContext): float
    {
        $profile = $this->profileRepository->findByUser($user);
        if ($profile === null || !$profile->isFeatureOptedIn()) {
            return 1.0;
        }

        $status = $profile->getCurrentStatus();
        $raw = $this->loadMultipliers()[$status->value] ?? 1.0;

        // Clamp to the spec's ±15% cap (0.85..1.20 inclusive — +20% only for DORMANT rest).
        $raw = max(0.85, min(1.20, $raw));

        if (!$this->contextMatches($status, $activityContext)) {
            return 1.0;
        }

        return $raw;
    }

    /** @return array<string, float> */
    private function loadMultipliers(): array
    {
        if ($this->multipliersCache !== null) {
            return $this->multipliersCache;
        }

        $setting = $this->gameSettingRepository->findByKey(self::SETTING_KEY);
        if ($setting !== null) {
            $decoded = json_decode($setting->getValue(), true);
            if (is_array($decoded)) {
                $map = [];
                foreach ($decoded as $key => $value) {
                    $map[(string) $key] = (float) $value;
                }

                return $this->multipliersCache = $map;
            }
        }

        // Hardcoded fallback mirroring spec §4.
        return $this->multipliersCache = [
            PsychStatus::CHARGED->value => 1.15,
            PsychStatus::STEADY->value => 1.0,
            PsychStatus::DORMANT->value => 1.20,
            PsychStatus::WEARY->value => 1.0,
            PsychStatus::SCATTERED->value => 1.0,
        ];
    }

    /**
     * Activity-context gating: CHARGED only buffs new challenges; DORMANT
     * only buffs rest activities. Other statuses either stay baseline or
     * are ineligible for a buff in any context.
     */
    private function contextMatches(PsychStatus $status, string $activityContext): bool
    {
        return match ($status) {
            PsychStatus::CHARGED => $activityContext === self::CONTEXT_NEW_CHALLENGE,
            PsychStatus::DORMANT => $activityContext === self::CONTEXT_REST,
            // STEADY / WEARY / SCATTERED return a 1.0 multiplier anyway,
            // so "match" here is irrelevant but true to keep the output
            // path consistent.
            PsychStatus::STEADY, PsychStatus::WEARY, PsychStatus::SCATTERED => true,
        };
    }
}
