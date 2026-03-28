<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328220235 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workout_sessions ADD used_skill_slugs JSON DEFAULT NULL, ADD used_consumable_slugs JSON DEFAULT NULL, ADD mobs_defeated INT DEFAULT 0 NOT NULL, ADD total_xp_from_mobs INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workout_sessions DROP used_skill_slugs, DROP used_consumable_slugs, DROP mobs_defeated, DROP total_xp_from_mobs');
    }
}
