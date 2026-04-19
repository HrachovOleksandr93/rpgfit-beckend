<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Extend the `mobs` table with 8 new columns per BA/outputs/09-mob-bestiary.md §1.
 *
 * Adds realm / class_tier / behavior / archetype (enums), visual_keywords (json),
 * is_champion / accepts_champion (bool), champion_decoration (nullable string).
 *
 * Existing rows are backfilled to a safe "legacy" default:
 *   realm=neutral, class_tier=I, behavior=physical, archetype=beast,
 *   accepts_champion=true, is_champion=false, champion_decoration=null,
 *   visual_keywords=[].
 *
 * HP/XP formulas (BUSINESS_LOGIC §10) remain unchanged.
 */
final class Version20260418200100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Extend mobs table with realm, class_tier, behavior, archetype, visual_keywords, champion fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE mobs
            ADD realm VARCHAR(20) NOT NULL DEFAULT 'neutral',
            ADD class_tier VARCHAR(4) NOT NULL DEFAULT 'I',
            ADD behavior VARCHAR(20) NOT NULL DEFAULT 'physical',
            ADD archetype VARCHAR(20) NOT NULL DEFAULT 'beast',
            ADD visual_keywords JSON NOT NULL,
            ADD is_champion TINYINT(1) NOT NULL DEFAULT 0,
            ADD champion_decoration VARCHAR(40) DEFAULT NULL,
            ADD accepts_champion TINYINT(1) NOT NULL DEFAULT 1");

        // Backfill visual_keywords to empty JSON array for any pre-existing rows.
        $this->addSql("UPDATE mobs SET visual_keywords = '[]' WHERE visual_keywords IS NULL");

        $this->addSql('CREATE INDEX idx_mob_realm_tier ON mobs (realm, class_tier)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_mob_realm_tier ON mobs');
        $this->addSql('ALTER TABLE mobs
            DROP realm,
            DROP class_tier,
            DROP behavior,
            DROP archetype,
            DROP visual_keywords,
            DROP is_champion,
            DROP champion_decoration,
            DROP accepts_champion');
    }
}
