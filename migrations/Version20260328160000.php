<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates game_settings table with seed data and adds level/totalXp columns to character_stats.
 */
final class Version20260328160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create game_settings table with initial seed data; add level and total_xp to character_stats';
    }

    public function up(Schema $schema): void
    {
        // Create game_settings table
        $this->addSql(
            'CREATE TABLE game_settings ('
            . 'id BINARY(16) NOT NULL, '
            . 'category VARCHAR(50) NOT NULL, '
            . '`key` VARCHAR(100) NOT NULL, '
            . 'value VARCHAR(255) NOT NULL, '
            . 'description VARCHAR(500) DEFAULT NULL, '
            . 'UNIQUE INDEX UNIQ_game_settings_key (`key`), '
            . 'INDEX idx_game_setting_category (category), '
            . 'PRIMARY KEY (id)'
            . ') DEFAULT CHARACTER SET utf8mb4'
        );

        // Add level and total_xp columns to character_stats
        $this->addSql('ALTER TABLE character_stats ADD level INT DEFAULT 1 NOT NULL, ADD total_xp INT DEFAULT 0 NOT NULL');

        // Seed XP rates
        $this->insertSetting('xp_rates', 'xp_rate_steps', '10', 'XP awarded per 1,000 steps walked/run');
        $this->insertSetting('xp_rates', 'xp_rate_active_energy', '25', 'XP awarded per 100 kcal of active energy burned');
        $this->insertSetting('xp_rates', 'xp_rate_workout', '15', 'XP awarded per 10 minutes of workout duration');
        $this->insertSetting('xp_rates', 'xp_rate_distance', '10', 'XP awarded per kilometre of distance covered');
        $this->insertSetting('xp_rates', 'xp_rate_sleep', '10', 'XP awarded per hour of sleep (any sleep stage)');
        $this->insertSetting('xp_rates', 'xp_rate_flights', '5', 'XP awarded per flight of stairs climbed');

        // Seed XP caps
        $this->insertSetting('xp_caps', 'xp_daily_cap', '3000', 'Maximum XP a user can earn in a single day (anti-cheat)');
        $this->insertSetting('xp_caps', 'xp_sleep_max_hours', '9', 'Maximum sleep hours that count towards XP (cap for sleep data)');

        // Seed leveling curve parameters
        $this->insertSetting('leveling', 'level_max', '100', 'Maximum achievable character level');
        $this->insertSetting('leveling', 'level_formula_quad', '4.2', 'Quadratic coefficient in the XP curve: xp(L) = quad*L^2 + linear*L');
        $this->insertSetting('leveling', 'level_formula_linear', '28', 'Linear coefficient in the XP curve: xp(L) = quad*L^2 + linear*L');
        $this->insertSetting('leveling', 'level_total_points', '30', 'Initial stat points granted to a new character');

        // Seed streak bonuses
        $this->insertSetting('bonuses', 'streak_3_days', '1.1', 'XP multiplier for a 3-day activity streak');
        $this->insertSetting('bonuses', 'streak_7_days', '1.2', 'XP multiplier for a 7-day activity streak');
        $this->insertSetting('bonuses', 'streak_14_days', '1.3', 'XP multiplier for a 14-day activity streak');
        $this->insertSetting('bonuses', 'streak_30_days', '1.5', 'XP multiplier for a 30-day activity streak');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE game_settings');
        $this->addSql('ALTER TABLE character_stats DROP level, DROP total_xp');
    }

    /** Helper: insert a single game setting row with a random UUID. */
    private function insertSetting(string $category, string $key, string $value, string $description): void
    {
        // Generate a random 16-byte binary UUID
        $uuid = random_bytes(16);
        $this->addSql(
            'INSERT INTO game_settings (id, category, `key`, value, description) VALUES (?, ?, ?, ?, ?)',
            [$uuid, $category, $key, $value, $description],
            ['binary', 'string', 'string', 'string', 'string'],
        );
    }
}
