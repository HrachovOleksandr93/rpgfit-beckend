<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for OAuth, onboarding, and initial stats features.
 *
 * Changes:
 * - Create linked_accounts table for OAuth provider links
 * - Add new nullable columns to users table: gender, onboarding_completed,
 *   preferred_workouts, training_frequency, lifestyle
 * - Make existing users columns nullable: display_name, height, weight,
 *   workout_type, activity_level, desired_goal, character_race
 */
final class Version20260328150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add linked_accounts table and new user columns for OAuth and onboarding flow';
    }

    public function up(Schema $schema): void
    {
        // Create linked_accounts table
        if (!$schema->hasTable('linked_accounts')) {
            $this->addSql('CREATE TABLE linked_accounts (
                id BINARY(16) NOT NULL,
                provider VARCHAR(20) NOT NULL,
                provider_user_id VARCHAR(255) NOT NULL,
                email VARCHAR(180) NOT NULL,
                linked_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                user_id BINARY(16) NOT NULL,
                INDEX idx_linked_account_user (user_id),
                UNIQUE INDEX uniq_provider_user (provider, provider_user_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4');

            $this->addSql('ALTER TABLE linked_accounts ADD CONSTRAINT FK_3695E777A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        }

        $usersTable = $schema->getTable('users');

        // Make existing user columns nullable for OAuth flow
        if ($usersTable->hasColumn('display_name')) {
            $this->addSql('ALTER TABLE users MODIFY display_name VARCHAR(30) DEFAULT NULL');
        }
        if ($usersTable->hasColumn('height')) {
            $this->addSql('ALTER TABLE users MODIFY height DOUBLE PRECISION DEFAULT NULL');
        }
        if ($usersTable->hasColumn('weight')) {
            $this->addSql('ALTER TABLE users MODIFY weight DOUBLE PRECISION DEFAULT NULL');
        }
        if ($usersTable->hasColumn('workout_type')) {
            $this->addSql('ALTER TABLE users MODIFY workout_type VARCHAR(20) DEFAULT NULL');
        }
        if ($usersTable->hasColumn('activity_level')) {
            $this->addSql('ALTER TABLE users MODIFY activity_level VARCHAR(20) DEFAULT NULL');
        }
        if ($usersTable->hasColumn('desired_goal')) {
            $this->addSql('ALTER TABLE users MODIFY desired_goal VARCHAR(20) DEFAULT NULL');
        }
        if ($usersTable->hasColumn('character_race')) {
            $this->addSql('ALTER TABLE users MODIFY character_race VARCHAR(20) DEFAULT NULL');
        }

        // Add new columns to users table
        if (!$usersTable->hasColumn('gender')) {
            $this->addSql('ALTER TABLE users ADD gender VARCHAR(10) DEFAULT NULL');
        }
        if (!$usersTable->hasColumn('onboarding_completed')) {
            $this->addSql('ALTER TABLE users ADD onboarding_completed TINYINT(1) DEFAULT 0 NOT NULL');
        }
        if (!$usersTable->hasColumn('preferred_workouts')) {
            $this->addSql('ALTER TABLE users ADD preferred_workouts JSON DEFAULT NULL');
        }
        if (!$usersTable->hasColumn('training_frequency')) {
            $this->addSql('ALTER TABLE users ADD training_frequency VARCHAR(20) DEFAULT NULL');
        }
        if (!$usersTable->hasColumn('lifestyle')) {
            $this->addSql('ALTER TABLE users ADD lifestyle VARCHAR(20) DEFAULT NULL');
        }

        // Set onboarding_completed = true for all existing users (they registered via the full form)
        $this->addSql('UPDATE users SET onboarding_completed = 1 WHERE onboarding_completed = 0 AND display_name IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE linked_accounts DROP FOREIGN KEY FK_3695E777A76ED395');
        $this->addSql('DROP TABLE linked_accounts');

        $this->addSql('ALTER TABLE users DROP COLUMN gender');
        $this->addSql('ALTER TABLE users DROP COLUMN onboarding_completed');
        $this->addSql('ALTER TABLE users DROP COLUMN preferred_workouts');
        $this->addSql('ALTER TABLE users DROP COLUMN training_frequency');
        $this->addSql('ALTER TABLE users DROP COLUMN lifestyle');

        $this->addSql('ALTER TABLE users MODIFY display_name VARCHAR(30) NOT NULL');
        $this->addSql('ALTER TABLE users MODIFY height DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE users MODIFY weight DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE users MODIFY workout_type VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE users MODIFY activity_level VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE users MODIFY desired_goal VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE users MODIFY character_race VARCHAR(20) NOT NULL');
    }
}
