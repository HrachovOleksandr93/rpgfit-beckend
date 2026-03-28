<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add battle result calculation fields to entities.
 *
 * - exercises: defaultWeight, defaultPace, defaultDuration (benchmark values)
 * - workout_sessions: completionPercent, performanceTier, bonusXpPercent, lootEarned, superLootEarned
 * - workout_plans: difficultyModifier
 */
final class Version20260328230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add battle result calculation fields: exercise benchmarks, session performance tracking, plan difficulty modifier';
    }

    public function up(Schema $schema): void
    {
        // Exercise benchmark fields
        $this->addSql('ALTER TABLE exercises ADD default_weight DOUBLE PRECISION DEFAULT NULL, ADD default_pace DOUBLE PRECISION DEFAULT NULL, ADD default_duration INT DEFAULT NULL');

        // WorkoutSession performance tracking fields
        $this->addSql('ALTER TABLE workout_sessions ADD completion_percent DOUBLE PRECISION DEFAULT NULL, ADD performance_tier VARCHAR(20) DEFAULT NULL, ADD bonus_xp_percent DOUBLE PRECISION DEFAULT 0 NOT NULL, ADD loot_earned TINYINT(1) DEFAULT 0 NOT NULL, ADD super_loot_earned TINYINT(1) DEFAULT 0 NOT NULL');

        // WorkoutPlan difficulty modifier
        $this->addSql('ALTER TABLE workout_plans ADD difficulty_modifier DOUBLE PRECISION DEFAULT 1.0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE exercises DROP default_weight, DROP default_pace, DROP default_duration');
        $this->addSql('ALTER TABLE workout_sessions DROP completion_percent, DROP performance_tier, DROP bonus_xp_percent, DROP loot_earned, DROP super_loot_earned');
        $this->addSql('ALTER TABLE workout_plans DROP difficulty_modifier');
    }
}
