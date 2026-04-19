<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drop character_race column from the users table.
 *
 * Part of founder decision D4 (2026-04-18): character races are removed
 * from the game. See docs/vision/product-decisions-2026-04-18.md.
 *
 * Existing user-skill links that pointed to race-passive skills should be
 * cleaned up via a follow-up data command, but this migration is limited
 * to the schema change on `users`.
 */
final class Version20260418200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop character_race column from users (D4: races removed)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP character_race');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD character_race VARCHAR(20) DEFAULT NULL');
    }
}
