<?php

declare(strict_types=1);

namespace App\Application\PsychProfile\Service;

use App\Domain\PsychProfile\Enum\MoodQuadrant;
use App\Domain\PsychProfile\Enum\PsychStatus;
use App\Domain\PsychProfile\Enum\UserIntent;
use App\Infrastructure\Config\Repository\GameSettingRepository;

/**
 * Deterministic status assignment (spec 2026-04-18 §3).
 *
 * Application layer (PsychProfile bounded context). Rules live in the
 * `game_settings` row `psych.status_rules` as a JSON array so research
 * can retune without redeploy. Each rule has optional predicates:
 *
 *   {
 *     "when": {
 *        "mood": ["ON_EDGE"],
 *        "intent": ["PUSH"],
 *        "energy_min": 4,
 *        "energy_max": 5
 *     },
 *     "assign": "SCATTERED"
 *   }
 *
 * Rules are evaluated in order — **first match wins**. If no rule matches,
 * the service returns `STEADY` as the safe default. If the setting is
 * absent or malformed, a hardcoded fallback (mirroring §3) kicks in so
 * dev environments stay usable before seed migrations run.
 */
final class StatusAssignmentService
{
    public const SETTING_KEY = 'psych.status_rules';

    public function __construct(
        private readonly GameSettingRepository $gameSettingRepository,
    ) {
    }

    /**
     * Assign a PsychStatus given the three answers.
     *
     * `$energy` is clamped to 1..5 defensively; callers validate at the
     * controller layer but domain services stay self-correcting.
     */
    public function assign(MoodQuadrant $mood, int $energy, UserIntent $intent): PsychStatus
    {
        $energy = max(1, min(5, $energy));
        $rules = $this->loadRules();

        foreach ($rules as $rule) {
            if (!$this->ruleMatches($rule, $mood, $energy, $intent)) {
                continue;
            }
            $status = PsychStatus::tryFrom((string) ($rule['assign'] ?? ''));
            if ($status !== null) {
                return $status;
            }
        }

        return PsychStatus::STEADY;
    }

    /** @return list<array<string, mixed>> */
    private function loadRules(): array
    {
        $setting = $this->gameSettingRepository->findByKey(self::SETTING_KEY);
        if ($setting !== null) {
            $decoded = json_decode($setting->getValue(), true);
            if (is_array($decoded) && $decoded !== []) {
                /** @var list<array<string, mixed>> $decoded */
                return array_values($decoded);
            }
        }

        return self::fallbackRules();
    }

    /**
     * @param array<string, mixed> $rule
     */
    private function ruleMatches(array $rule, MoodQuadrant $mood, int $energy, UserIntent $intent): bool
    {
        $when = $rule['when'] ?? [];
        if (!is_array($when)) {
            return false;
        }

        // Mood predicate — "any of" semantics.
        if (isset($when['mood']) && is_array($when['mood']) && $when['mood'] !== []) {
            if (!in_array($mood->value, array_map('strval', $when['mood']), true)) {
                return false;
            }
        }

        // Intent predicate — "any of" semantics.
        if (isset($when['intent']) && is_array($when['intent']) && $when['intent'] !== []) {
            if (!in_array($intent->value, array_map('strval', $when['intent']), true)) {
                return false;
            }
        }

        // Energy range — inclusive bounds.
        if (isset($when['energy_min']) && $energy < (int) $when['energy_min']) {
            return false;
        }
        if (isset($when['energy_max']) && $energy > (int) $when['energy_max']) {
            return false;
        }

        return true;
    }

    /**
     * Hardcoded fallback mirroring spec §3 — used only when the
     * `psych.status_rules` setting is absent / unreadable.
     *
     * @return list<array<string, mixed>>
     */
    public static function fallbackRules(): array
    {
        return [
            ['when' => ['mood' => ['ON_EDGE']], 'assign' => PsychStatus::SCATTERED->value],
            ['when' => ['mood' => ['DRAINED']], 'assign' => PsychStatus::WEARY->value],
            ['when' => ['mood' => ['AT_EASE', 'NEUTRAL'], 'intent' => ['REST']], 'assign' => PsychStatus::DORMANT->value],
            ['when' => ['mood' => ['ENERGIZED'], 'intent' => ['PUSH'], 'energy_min' => 4], 'assign' => PsychStatus::CHARGED->value],
            ['when' => [], 'assign' => PsychStatus::STEADY->value],
        ];
    }
}
