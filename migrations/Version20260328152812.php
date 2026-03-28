<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328152812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE media_files (id BINARY(16) NOT NULL, original_filename VARCHAR(255) NOT NULL, storage_path VARCHAR(500) NOT NULL, mime_type VARCHAR(50) NOT NULL, file_size INT NOT NULL, entity_type VARCHAR(50) NOT NULL, entity_id VARCHAR(36) DEFAULT NULL, uploaded_at DATETIME NOT NULL, INDEX idx_media_entity (entity_type, entity_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE item_catalog ADD two_handed TINYINT DEFAULT 0 NOT NULL, ADD image_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE item_catalog ADD CONSTRAINT FK_107114133DA5256D FOREIGN KEY (image_id) REFERENCES media_files (id)');
        $this->addSql('CREATE INDEX IDX_107114133DA5256D ON item_catalog (image_id)');
        $this->addSql('ALTER TABLE skills ADD image_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE skills ADD CONSTRAINT FK_D53116703DA5256D FOREIGN KEY (image_id) REFERENCES media_files (id)');
        $this->addSql('CREATE INDEX IDX_D53116703DA5256D ON skills (image_id)');
        $this->addSql('ALTER TABLE user_inventory ADD equipped_slot VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE media_files');
        $this->addSql('ALTER TABLE item_catalog DROP FOREIGN KEY FK_107114133DA5256D');
        $this->addSql('DROP INDEX IDX_107114133DA5256D ON item_catalog');
        $this->addSql('ALTER TABLE item_catalog DROP two_handed, DROP image_id');
        $this->addSql('ALTER TABLE skills DROP FOREIGN KEY FK_D53116703DA5256D');
        $this->addSql('DROP INDEX IDX_D53116703DA5256D ON skills');
        $this->addSql('ALTER TABLE skills DROP image_id');
        $this->addSql('ALTER TABLE user_inventory DROP equipped_slot');
    }
}
