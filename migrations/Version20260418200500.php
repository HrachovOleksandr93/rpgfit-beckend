<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Psych Profiler (beta) schema + seed (spec 2026-04-18 §5.5).
 *
 * Creates `psych_check_ins` and `psych_user_profiles` tables, FK'd to
 * `users(id)` with cascade delete. Both tables use `BINARY(16)` for UUIDs
 * to match the existing `users.id` column (Doctrine maps UuidType to
 * BINARY(16) by default).
 *
 * JSON columns carry no DB-level default — MySQL rejects defaults on JSON,
 * and the PHP-side entity initialises the property to `[]` so a freshly
 * persisted row always has `[]`.
 *
 * Seeds five `game_settings` rows under the `psych` category:
 *  - `psych.status_rules`          (JSON — §3 rule table)
 *  - `psych.xp_multipliers`        (JSON — §4 multiplier map)
 *  - `psych.retention_days`        (180 — GDPR retention window)
 *  - `psych.crisis_threshold_days` (5   — §1 decision 1)
 *  - `psych.crisis_cooldown_days`  (30  — per-user re-log cooldown)
 *
 * The `down()` drops both tables and removes the seeded settings. The
 * per-user cooldown markers (`psych.crisis_last_flagged_*`) are written
 * at runtime and purged by the same `LIKE 'psych.%'` clean-up.
 */
final class Version20260418200500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Psych Profiler (beta) — create psych_check_ins + psych_user_profiles, seed game_settings';
    }

    public function up(Schema $schema): void
    {
        // psych_check_ins — one row per (user, local-day).
        $this->addSql(
            'CREATE TABLE psych_check_ins ('
            . 'id BINARY(16) NOT NULL, '
            . 'user_id BINARY(16) NOT NULL, '
            . 'mood_quadrant VARCHAR(20) DEFAULT NULL, '
            . 'energy_level INT DEFAULT NULL, '
            . 'intent VARCHAR(20) DEFAULT NULL, '
            . 'assigned_status VARCHAR(20) NOT NULL, '
            . 'skipped TINYINT(1) NOT NULL DEFAULT 0, '
            . "checked_in_on DATE NOT NULL COMMENT '(DC2Type:date_immutable)', "
            . "created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', "
            . 'UNIQUE INDEX uniq_psych_checkin_user_day (user_id, checked_in_on), '
            . 'INDEX idx_psych_checkin_user_date (user_id, checked_in_on), '
            . 'INDEX idx_psych_checkin_created_at (created_at), '
            . 'INDEX idx_psych_checkin_user_created (user_id, created_at), '
            . 'PRIMARY KEY (id)'
            . ') DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE psych_check_ins '
            . 'ADD CONSTRAINT FK_PSYCH_CHECKIN_USER '
            . 'FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE'
        );

        // psych_user_profiles — one row per user.
        $this->addSql(
            'CREATE TABLE psych_user_profiles ('
            . 'id BINARY(16) NOT NULL, '
            . 'user_id BINARY(16) NOT NULL, '
            . 'current_status VARCHAR(20) NOT NULL, '
            . "status_valid_until DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', "
            . 'consecutive_skips INT NOT NULL DEFAULT 0, '
            . 'feature_opted_in TINYINT(1) NOT NULL DEFAULT 0, '
            . "last_check_in_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', "
            . 'trends JSON NOT NULL, '
            . 'UNIQUE INDEX uniq_psych_user_profile_user (user_id), '
            . 'PRIMARY KEY (id)'
            . ') DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE psych_user_profiles '
            . 'ADD CONSTRAINT FK_PSYCH_USER_PROFILE_USER '
            . 'FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE'
        );

        // Widen game_settings.value so the psych.status_rules JSON blob
        // fits comfortably — the spec §3 seed is ~285 chars, past the
        // VARCHAR(255) default the table was created with.
        $this->addSql('ALTER TABLE game_settings MODIFY value VARCHAR(2048) NOT NULL');

        $this->seedStatusRules();
        $this->seedXpMultipliers();
        $this->insertSetting('psych', 'psych.retention_days', '180', 'Days to keep psych check-ins before retention purge.');
        $this->insertSetting('psych', 'psych.crisis_threshold_days', '5', 'Number of WEARY/SCATTERED days in a 7-day window that trigger the crisis-pattern log.');
        $this->insertSetting('psych', 'psych.crisis_cooldown_days', '30', 'Cooldown between repeated crisis-pattern logs for the same user.');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE psych_check_ins DROP FOREIGN KEY FK_PSYCH_CHECKIN_USER');
        $this->addSql('ALTER TABLE psych_user_profiles DROP FOREIGN KEY FK_PSYCH_USER_PROFILE_USER');
        $this->addSql('DROP TABLE psych_check_ins');
        $this->addSql('DROP TABLE psych_user_profiles');

        // Seeded + runtime-written psych settings (cooldown markers use the
        // `psych.crisis_last_flagged_*` prefix under the same category).
        $this->addSql("DELETE FROM game_settings WHERE `key` LIKE 'psych.%'");

        // Revert the value-column widening. No-op for rows that already fit.
        $this->addSql('ALTER TABLE game_settings MODIFY value VARCHAR(255) NOT NULL');
    }

    /**
     * Seed the §3 rule table in a single `psych.status_rules` JSON blob.
     *
     * Order matters — first match wins. Rules mirror
     * `StatusAssignmentService::fallbackRules()` verbatim.
     */
    private function seedStatusRules(): void
    {
        $rules = [
            ['when' => ['mood' => ['ON_EDGE']], 'assign' => 'SCATTERED'],
            ['when' => ['mood' => ['DRAINED']], 'assign' => 'WEARY'],
            ['when' => ['mood' => ['AT_EASE', 'NEUTRAL'], 'intent' => ['REST']], 'assign' => 'DORMANT'],
            ['when' => ['mood' => ['ENERGIZED'], 'intent' => ['PUSH'], 'energy_min' => 4], 'assign' => 'CHARGED'],
            ['when' => new \stdClass(), 'assign' => 'STEADY'],
        ];

        $json = json_encode($rules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode psych.status_rules seed.');
        }

        $this->insertSetting(
            'psych',
            'psych.status_rules',
            $json,
            'Deterministic status assignment rules (spec §3). First-match-wins JSON array.',
        );
    }

    /** Seed the §4 per-status XP multiplier map. */
    private function seedXpMultipliers(): void
    {
        $map = [
            'CHARGED' => 1.15,
            'STEADY' => 1.0,
            'DORMANT' => 1.20,
            'WEARY' => 1.0,
            'SCATTERED' => 1.0,
        ];

        $json = json_encode($map, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode psych.xp_multipliers seed.');
        }

        $this->insertSetting(
            'psych',
            'psych.xp_multipliers',
            $json,
            'Per-status XP multipliers (spec §4). Activity context gating in PsychStatusModifierService.',
        );
    }

    /** Helper: insert one `game_settings` row with a random BINARY(16) id. */
    private function insertSetting(string $category, string $key, string $value, string $description): void
    {
        // This migration widens game_settings.value to VARCHAR(2048) before
        // the seed runs so the psych.status_rules JSON blob (~285 chars)
        // fits without truncation.
        $this->addSql(
            'INSERT INTO game_settings (id, category, `key`, value, description) VALUES (?, ?, ?, ?, ?)',
            [random_bytes(16), $category, $key, $value, $description],
            ['binary', 'string', 'string', 'string', 'string'],
        );
    }
}
