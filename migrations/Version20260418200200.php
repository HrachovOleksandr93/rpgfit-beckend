<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the `portals` table with composite indexes for geo-queries and
 * type/realm filters. Self-FK (virtual_replica_of_id) allows a dynamic or
 * user-created portal to reference a static one as its replica.
 */
final class Version20260418200200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create portals table (static/dynamic/user_created) with geo + expiry indexes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE portals (
            id BINARY(16) NOT NULL,
            virtual_replica_of_id BINARY(16) DEFAULT NULL,
            created_by_user_id BINARY(16) DEFAULT NULL,
            name VARCHAR(120) NOT NULL,
            slug VARCHAR(140) NOT NULL,
            type VARCHAR(20) NOT NULL,
            realm VARCHAR(20) NOT NULL,
            latitude DOUBLE PRECISION NOT NULL,
            longitude DOUBLE PRECISION NOT NULL,
            radius_m INT NOT NULL DEFAULT 100,
            tier INT NOT NULL DEFAULT 1,
            challenge_type VARCHAR(40) DEFAULT NULL,
            challenge_params JSON NOT NULL,
            reward_artifact_slug VARCHAR(100) DEFAULT NULL,
            expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            max_battles INT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            UNIQUE INDEX UNIQ_PORTAL_SLUG (slug),
            INDEX idx_portal_latlng (latitude, longitude),
            INDEX idx_portal_type_realm (type, realm),
            INDEX idx_portal_expires_at (expires_at),
            INDEX IDX_PORTAL_VIRTUAL_REPLICA (virtual_replica_of_id),
            INDEX IDX_PORTAL_CREATED_BY (created_by_user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql('ALTER TABLE portals
            ADD CONSTRAINT FK_PORTAL_VIRTUAL_REPLICA FOREIGN KEY (virtual_replica_of_id) REFERENCES portals (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE portals
            ADD CONSTRAINT FK_PORTAL_CREATED_BY FOREIGN KEY (created_by_user_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE portals DROP FOREIGN KEY FK_PORTAL_VIRTUAL_REPLICA');
        $this->addSql('ALTER TABLE portals DROP FOREIGN KEY FK_PORTAL_CREATED_BY');
        $this->addSql('DROP TABLE portals');
    }
}
