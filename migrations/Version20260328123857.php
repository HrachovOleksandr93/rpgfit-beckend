<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328123857 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE health_data_points (id BINARY(16) NOT NULL, external_uuid VARCHAR(255) DEFAULT NULL, data_type VARCHAR(50) NOT NULL, value DOUBLE PRECISION NOT NULL, unit VARCHAR(50) NOT NULL, date_from DATETIME NOT NULL, date_to DATETIME NOT NULL, platform VARCHAR(20) NOT NULL, source_app VARCHAR(255) DEFAULT NULL, recording_method VARCHAR(20) NOT NULL, synced_at DATETIME NOT NULL, user_id BINARY(16) NOT NULL, INDEX IDX_45371C4EA76ED395 (user_id), INDEX idx_user_type_date (user_id, data_type, date_from), UNIQUE INDEX unique_user_external_uuid (user_id, external_uuid), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE health_sync_logs (id BINARY(16) NOT NULL, data_type VARCHAR(50) NOT NULL, last_synced_at DATETIME NOT NULL, points_count INT NOT NULL, user_id BINARY(16) NOT NULL, INDEX IDX_93DFC181A76ED395 (user_id), UNIQUE INDEX unique_user_data_type (user_id, data_type), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE health_data_points ADD CONSTRAINT FK_45371C4EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE health_sync_logs ADD CONSTRAINT FK_93DFC181A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE health_data_points DROP FOREIGN KEY FK_45371C4EA76ED395');
        $this->addSql('ALTER TABLE health_sync_logs DROP FOREIGN KEY FK_93DFC181A76ED395');
        $this->addSql('DROP TABLE health_data_points');
        $this->addSql('DROP TABLE health_sync_logs');
    }
}
