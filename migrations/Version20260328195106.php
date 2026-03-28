<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328195106 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add skill type, duration, cooldown, race restriction, tier, universal/race flags to skills table; create profession_skills junction table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE profession_skills (id BINARY(16) NOT NULL, profession_id BINARY(16) NOT NULL, skill_id BINARY(16) NOT NULL, INDEX IDX_6ADB1825FDEF8996 (profession_id), INDEX IDX_6ADB18255585C142 (skill_id), UNIQUE INDEX uniq_profession_skill (profession_id, skill_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE profession_skills ADD CONSTRAINT FK_6ADB1825FDEF8996 FOREIGN KEY (profession_id) REFERENCES professions (id)');
        $this->addSql('ALTER TABLE profession_skills ADD CONSTRAINT FK_6ADB18255585C142 FOREIGN KEY (skill_id) REFERENCES skills (id)');
        $this->addSql('ALTER TABLE skills ADD skill_type VARCHAR(20) DEFAULT \'passive\' NOT NULL, ADD duration INT DEFAULT NULL, ADD cooldown INT DEFAULT NULL, ADD race_restriction VARCHAR(20) DEFAULT NULL, ADD tier INT DEFAULT NULL, ADD is_universal TINYINT DEFAULT 0 NOT NULL, ADD is_race_skill TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE profession_skills DROP FOREIGN KEY FK_6ADB1825FDEF8996');
        $this->addSql('ALTER TABLE profession_skills DROP FOREIGN KEY FK_6ADB18255585C142');
        $this->addSql('DROP TABLE profession_skills');
        $this->addSql('ALTER TABLE skills DROP skill_type, DROP duration, DROP cooldown, DROP race_restriction, DROP tier, DROP is_universal, DROP is_race_skill');
    }
}
