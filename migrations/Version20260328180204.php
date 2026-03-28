<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328180204 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create mobs table for mob entity definitions';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mobs (id BINARY(16) NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, level INT NOT NULL, hp INT NOT NULL, xp_reward INT NOT NULL, description LONGTEXT DEFAULT NULL, rarity VARCHAR(20) DEFAULT NULL, created_at DATETIME NOT NULL, image_id BINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_3544557C989D9B62 (slug), INDEX IDX_3544557C3DA5256D (image_id), INDEX idx_mob_level (level), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE mobs ADD CONSTRAINT FK_3544557C3DA5256D FOREIGN KEY (image_id) REFERENCES media_files (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mobs DROP FOREIGN KEY FK_3544557C3DA5256D');
        $this->addSql('DROP TABLE mobs');
    }
}
