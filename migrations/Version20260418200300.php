<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add nullable `realm` column to `item_catalog` for artifact realm binding.
 *
 * Enables the realm-match damage multiplier in BattleResultCalculator
 * (BUSINESS_LOGIC §12): when an equipped artifact's realm matches the
 * current mob's realm, damage is multiplied by 1.4.
 *
 * Null is the default (unbound / generic gear).
 */
final class Version20260418200300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add nullable realm to item_catalog for artifact realm binding';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE item_catalog ADD realm VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE item_catalog DROP realm');
    }
}
