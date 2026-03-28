<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328212840 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE workout_sessions (id BINARY(16) NOT NULL, mode VARCHAR(20) NOT NULL, mob_hp INT DEFAULT NULL, mob_xp_reward INT DEFAULT NULL, started_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, total_damage_dealt INT DEFAULT 0 NOT NULL, xp_awarded INT DEFAULT 0 NOT NULL, status VARCHAR(20) NOT NULL, health_data JSON DEFAULT NULL, user_id BINARY(16) NOT NULL, workout_plan_id BINARY(16) NOT NULL, mob_id BINARY(16) DEFAULT NULL, INDEX IDX_421170A5A76ED395 (user_id), INDEX IDX_421170A5945F6E33 (workout_plan_id), INDEX IDX_421170A516E57E11 (mob_id), INDEX idx_session_user_status (user_id, status), INDEX idx_session_user_started (user_id, started_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE workout_sessions ADD CONSTRAINT FK_421170A5A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE workout_sessions ADD CONSTRAINT FK_421170A5945F6E33 FOREIGN KEY (workout_plan_id) REFERENCES workout_plans (id)');
        $this->addSql('ALTER TABLE workout_sessions ADD CONSTRAINT FK_421170A516E57E11 FOREIGN KEY (mob_id) REFERENCES mobs (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workout_sessions DROP FOREIGN KEY FK_421170A5A76ED395');
        $this->addSql('ALTER TABLE workout_sessions DROP FOREIGN KEY FK_421170A5945F6E33');
        $this->addSql('ALTER TABLE workout_sessions DROP FOREIGN KEY FK_421170A516E57E11');
        $this->addSql('DROP TABLE workout_sessions');
    }
}
