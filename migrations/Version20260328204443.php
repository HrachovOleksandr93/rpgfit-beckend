<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328204443 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add activity_category to exercises and rename equipment none to no_equipment';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE exercises ADD activity_category VARCHAR(50) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_exercise_activity_category ON exercises (activity_category)');
        $this->addSql("UPDATE exercises SET equipment = 'no_equipment' WHERE equipment = 'none'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_exercise_activity_category ON exercises');
        $this->addSql('ALTER TABLE exercises DROP activity_category');
        $this->addSql("UPDATE exercises SET equipment = 'none' WHERE equipment = 'no_equipment'");
    }
}
