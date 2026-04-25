<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Psych Profiler v2 — physical state (Q4 / session-RPE) + adapter matrix seed
 * + deload thresholds + completion-bonus knobs (spec 2026-04-19 §2.5-§2.6).
 *
 * Adds:
 *  - `physical_state_answers` table (BINARY(16) UUIDs, FK to users + workout_sessions).
 *  - `psych_check_ins.physical_state_answer_id` nullable FK column so a
 *    daily check-in can link to the Q4 merged into it.
 *  - `game_settings` rows for v2 knobs:
 *      psych.completion_bonus_pct              = 10
 *      psych.completion_bonus_weekly_cap       = 5
 *      psych.adapter_matrix                    = JSON (spec §1.3)
 *      psych.deload_card_start_day             = 5
 *      psych.deload_plan_start_day             = 7
 *      psych.deload_volume_reduction_pct       = 40
 *      psych.deload_intensity_reduction_pct    = 30
 *
 * JSON columns carry no DB-level default — MySQL rejects defaults on JSON.
 *
 * The `down()` reverses every change: drops the FK column, drops the
 * table, and removes the seeded v2 settings. Runtime-written per-user
 * bonus markers (`psych.bonus_*`) are cleaned up in `Version20260418200500`
 * with the `LIKE 'psych.%'` clause; we keep that same clause local here
 * so rolling back v2 doesn't wipe v1 settings.
 */
final class Version20260419200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Psych Profiler v2 — physical_state_answers + adapter matrix + deload thresholds';
    }

    public function up(Schema $schema): void
    {
        // Widen game_settings.value to TEXT so the psych.adapter_matrix
        // JSON blob (~1.2 KB UTF-8) fits with headroom. Existing VARCHAR
        // values upsize cleanly.
        $this->addSql('ALTER TABLE game_settings MODIFY value TEXT NOT NULL');

        // physical_state_answers — one row per Q4 submission.
        $this->addSql(
            'CREATE TABLE physical_state_answers ('
            . 'id BINARY(16) NOT NULL, '
            . 'user_id BINARY(16) NOT NULL, '
            . 'workout_session_id BINARY(16) DEFAULT NULL, '
            . 'rpe_score INT NOT NULL, '
            . "created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', "
            . 'INDEX idx_physical_state_user_created (user_id, created_at), '
            . 'INDEX idx_physical_state_session (workout_session_id), '
            . 'PRIMARY KEY (id)'
            . ') DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE physical_state_answers '
            . 'ADD CONSTRAINT FK_PHYSICAL_STATE_USER '
            . 'FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE physical_state_answers '
            . 'ADD CONSTRAINT FK_PHYSICAL_STATE_SESSION '
            . 'FOREIGN KEY (workout_session_id) REFERENCES workout_sessions (id) ON DELETE SET NULL'
        );

        // Extend psych_check_ins with the nullable FK to physical_state_answers.
        $this->addSql(
            'ALTER TABLE psych_check_ins '
            . 'ADD physical_state_answer_id BINARY(16) DEFAULT NULL'
        );
        $this->addSql(
            'ALTER TABLE psych_check_ins '
            . 'ADD CONSTRAINT FK_PSYCH_CHECKIN_PHYSICAL_STATE '
            . 'FOREIGN KEY (physical_state_answer_id) REFERENCES physical_state_answers (id) ON DELETE SET NULL'
        );
        $this->addSql(
            'CREATE INDEX idx_psych_checkin_physical_state '
            . 'ON psych_check_ins (physical_state_answer_id)'
        );

        // Seed v2 settings.
        $this->insertSetting('psych', 'psych.completion_bonus_pct', '10', 'Completion XP bonus percentage (spec §1.1).');
        $this->insertSetting('psych', 'psych.completion_bonus_weekly_cap', '5', 'Max days per rolling 7d window on which the completion bonus can fire (spec §1.1).');
        $this->insertSetting('psych', 'psych.deload_card_start_day', '5', 'Consecutive WEARY/SCATTERED day count at which the home deload card appears (spec §1.5).');
        $this->insertSetting('psych', 'psych.deload_plan_start_day', '7', 'Consecutive WEARY/SCATTERED day count at which the pre-generated deload plan is offered (spec §1.5).');
        $this->insertSetting('psych', 'psych.deload_volume_reduction_pct', '40', 'Volume reduction percentage for the deload week (spec §1.5).');
        $this->insertSetting('psych', 'psych.deload_intensity_reduction_pct', '30', 'Intensity reduction percentage for the deload week (spec §1.5).');
        $this->seedAdapterMatrix();
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE psych_check_ins DROP FOREIGN KEY FK_PSYCH_CHECKIN_PHYSICAL_STATE');
        $this->addSql('DROP INDEX idx_psych_checkin_physical_state ON psych_check_ins');
        $this->addSql('ALTER TABLE psych_check_ins DROP physical_state_answer_id');

        $this->addSql('ALTER TABLE physical_state_answers DROP FOREIGN KEY FK_PHYSICAL_STATE_USER');
        $this->addSql('ALTER TABLE physical_state_answers DROP FOREIGN KEY FK_PHYSICAL_STATE_SESSION');
        $this->addSql('DROP TABLE physical_state_answers');

        // Only remove keys introduced by v2. Leave v1 keys + runtime
        // crisis-flag markers (handled by Version20260418200500.down()) intact.
        $this->addSql(
            "DELETE FROM game_settings WHERE `key` IN (" .
            "'psych.completion_bonus_pct', " .
            "'psych.completion_bonus_weekly_cap', " .
            "'psych.adapter_matrix', " .
            "'psych.deload_card_start_day', " .
            "'psych.deload_plan_start_day', " .
            "'psych.deload_volume_reduction_pct', " .
            "'psych.deload_intensity_reduction_pct'" .
            ")"
        );

        // Runtime bonus markers follow the `psych.bonus_*` prefix.
        $this->addSql("DELETE FROM game_settings WHERE `key` LIKE 'psych.bonus_marker_%'");
        $this->addSql("DELETE FROM game_settings WHERE `key` LIKE 'psych.bonus_applied_%'");

        // Narrow value column back to the post-v1 width. No-op for rows
        // that already fit; v1 down() narrows further to 255.
        $this->addSql('ALTER TABLE game_settings MODIFY value VARCHAR(2048) NOT NULL');
    }

    /** Seed the §1.3 adapter matrix as a JSON blob. */
    private function seedAdapterMatrix(): void
    {
        $matrix = [
            [
                'status' => 'CHARGED',
                'rpeMin' => 1,
                'rpeMax' => 2,
                'intensity' => 0.10,
                'volume' => 0.10,
                'duration' => 0.0,
                'focus' => 'new-challenge',
                'warning' => null,
            ],
            [
                'status' => 'CHARGED',
                'intensity' => 0.0,
                'volume' => 0.0,
                'duration' => 0.0,
                'focus' => 'new-challenge',
                'warning' => null,
            ],
            [
                'status' => 'STEADY',
                'intensity' => 0.0,
                'volume' => 0.0,
                'duration' => 0.0,
                'focus' => 'baseline',
                'warning' => null,
            ],
            [
                'status' => 'DORMANT',
                'intensity' => -0.20,
                'volume' => -0.15,
                'duration' => -0.20,
                'focus' => 'mobility',
                'warning' => 'Ти відпочиваєш. Vector рекомендує легке.',
            ],
            [
                'status' => 'WEARY',
                'rpeMin' => 4,
                'rpeMax' => 5,
                'intensity' => -0.35,
                'volume' => -0.35,
                'duration' => -0.30,
                'focus' => 'recovery-only',
                'warning' => 'Сильна втома. Рекомендуємо лише легке розслаблення.',
            ],
            [
                'status' => 'WEARY',
                'intensity' => -0.25,
                'volume' => -0.25,
                'duration' => -0.20,
                'focus' => 'recovery',
                'warning' => 'Ми фіксуємо що ти стомлений. Бережи себе.',
            ],
            [
                'status' => 'SCATTERED',
                'intensity' => -0.15,
                'volume' => -0.15,
                'duration' => -0.10,
                'focus' => 'focus',
                'warning' => 'Сигнал рваний. Зосередься на дихальних вправах.',
            ],
        ];

        $json = json_encode($matrix, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode psych.adapter_matrix seed.');
        }

        $this->insertSetting(
            'psych',
            'psych.adapter_matrix',
            $json,
            'PsychWorkoutAdapterService matrix (spec §1.3). First-match-wins JSON array.',
        );
    }

    /** Helper: insert one `game_settings` row with a random BINARY(16) id. */
    private function insertSetting(string $category, string $key, string $value, string $description): void
    {
        $this->addSql(
            'INSERT INTO game_settings (id, category, `key`, value, description) VALUES (?, ?, ?, ?, ?)',
            [random_bytes(16), $category, $key, $value, $description],
            ['binary', 'string', 'string', 'string', 'string'],
        );
    }
}
