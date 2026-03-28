<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328151440 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_training_preferences (id BINARY(16) NOT NULL, training_frequency VARCHAR(20) DEFAULT NULL, lifestyle VARCHAR(20) DEFAULT NULL, primary_training_style VARCHAR(30) DEFAULT NULL, preferred_workouts JSON DEFAULT NULL, user_id BINARY(16) NOT NULL, UNIQUE INDEX UNIQ_F37B0F5DA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_training_preferences ADD CONSTRAINT FK_F37B0F5DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE users DROP preferred_workouts, DROP training_frequency, DROP lifestyle');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_training_preferences DROP FOREIGN KEY FK_F37B0F5DA76ED395');
        $this->addSql('DROP TABLE user_training_preferences');
        $this->addSql('ALTER TABLE users ADD preferred_workouts JSON DEFAULT NULL, ADD training_frequency VARCHAR(20) DEFAULT NULL, ADD lifestyle VARCHAR(20) DEFAULT NULL');
    }
}
