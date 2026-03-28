<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328203408 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE exercises (id BINARY(16) NOT NULL, name VARCHAR(150) NOT NULL, slug VARCHAR(150) NOT NULL, primary_muscle VARCHAR(20) NOT NULL, secondary_muscles JSON NOT NULL, equipment VARCHAR(20) NOT NULL, difficulty VARCHAR(20) NOT NULL, movement_type VARCHAR(20) NOT NULL, priority INT NOT NULL, is_base_exercise TINYINT NOT NULL, description LONGTEXT DEFAULT NULL, default_sets INT NOT NULL, default_reps_min INT NOT NULL, default_reps_max INT NOT NULL, default_rest_seconds INT NOT NULL, image_id BINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_FA14991989D9B62 (slug), INDEX IDX_FA149913DA5256D (image_id), INDEX idx_exercise_primary_muscle (primary_muscle), INDEX idx_exercise_difficulty (difficulty), INDEX idx_exercise_priority (priority), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE split_templates (id BINARY(16) NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, split_type VARCHAR(20) NOT NULL, days_per_week INT NOT NULL, day_configs JSON NOT NULL, description LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_84EAA0DF989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE workout_plan_exercise_logs (id BINARY(16) NOT NULL, set_number INT NOT NULL, reps INT DEFAULT NULL, weight DOUBLE PRECISION DEFAULT NULL, duration INT DEFAULT NULL, notes VARCHAR(255) DEFAULT NULL, completed_at DATETIME NOT NULL, plan_exercise_id BINARY(16) NOT NULL, INDEX IDX_17ECF9331D8D4D71 (plan_exercise_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE workout_plan_exercises (id BINARY(16) NOT NULL, order_index INT NOT NULL, sets INT NOT NULL, reps_min INT NOT NULL, reps_max INT NOT NULL, rest_seconds INT NOT NULL, notes VARCHAR(255) DEFAULT NULL, workout_plan_id BINARY(16) NOT NULL, exercise_id BINARY(16) NOT NULL, INDEX IDX_F3B1E4F3945F6E33 (workout_plan_id), INDEX IDX_F3B1E4F3E934951A (exercise_id), INDEX idx_plan_exercise_order (workout_plan_id, order_index), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE workout_plans (id BINARY(16) NOT NULL, name VARCHAR(150) NOT NULL, status VARCHAR(20) NOT NULL, activity_type VARCHAR(100) DEFAULT NULL, target_muscle_groups JSON DEFAULT NULL, planned_at DATETIME NOT NULL, started_at DATETIME DEFAULT NULL, completed_at DATETIME DEFAULT NULL, target_distance DOUBLE PRECISION DEFAULT NULL, target_duration INT DEFAULT NULL, target_calories DOUBLE PRECISION DEFAULT NULL, reward_tiers JSON DEFAULT NULL, created_at DATETIME NOT NULL, user_id BINARY(16) NOT NULL, INDEX IDX_6CAC2BC5A76ED395 (user_id), INDEX idx_plan_user_planned (user_id, planned_at), INDEX idx_plan_user_status (user_id, status), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE exercises ADD CONSTRAINT FK_FA149913DA5256D FOREIGN KEY (image_id) REFERENCES media_files (id)');
        $this->addSql('ALTER TABLE workout_plan_exercise_logs ADD CONSTRAINT FK_17ECF9331D8D4D71 FOREIGN KEY (plan_exercise_id) REFERENCES workout_plan_exercises (id)');
        $this->addSql('ALTER TABLE workout_plan_exercises ADD CONSTRAINT FK_F3B1E4F3945F6E33 FOREIGN KEY (workout_plan_id) REFERENCES workout_plans (id)');
        $this->addSql('ALTER TABLE workout_plan_exercises ADD CONSTRAINT FK_F3B1E4F3E934951A FOREIGN KEY (exercise_id) REFERENCES exercises (id)');
        $this->addSql('ALTER TABLE workout_plans ADD CONSTRAINT FK_6CAC2BC5A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE exercises DROP FOREIGN KEY FK_FA149913DA5256D');
        $this->addSql('ALTER TABLE workout_plan_exercise_logs DROP FOREIGN KEY FK_17ECF9331D8D4D71');
        $this->addSql('ALTER TABLE workout_plan_exercises DROP FOREIGN KEY FK_F3B1E4F3945F6E33');
        $this->addSql('ALTER TABLE workout_plan_exercises DROP FOREIGN KEY FK_F3B1E4F3E934951A');
        $this->addSql('ALTER TABLE workout_plans DROP FOREIGN KEY FK_6CAC2BC5A76ED395');
        $this->addSql('DROP TABLE exercises');
        $this->addSql('DROP TABLE split_templates');
        $this->addSql('DROP TABLE workout_plan_exercise_logs');
        $this->addSql('DROP TABLE workout_plan_exercises');
        $this->addSql('DROP TABLE workout_plans');
    }
}
