<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328143834 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE item_catalog (id BINARY(16) NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, item_type VARCHAR(20) NOT NULL, rarity VARCHAR(20) NOT NULL, icon VARCHAR(255) DEFAULT NULL, slot VARCHAR(20) DEFAULT NULL, durability INT DEFAULT NULL, duration INT DEFAULT NULL, stackable TINYINT DEFAULT 0 NOT NULL, max_stack INT DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_10711413989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE item_stat_bonuses (id BINARY(16) NOT NULL, stat_type VARCHAR(10) NOT NULL, points INT NOT NULL, item_catalog_id BINARY(16) NOT NULL, INDEX idx_item_stat_bonus_item (item_catalog_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE skill_stat_bonuses (id BINARY(16) NOT NULL, stat_type VARCHAR(10) NOT NULL, points INT NOT NULL, skill_id BINARY(16) NOT NULL, INDEX idx_skill_stat_bonus_skill (skill_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE skills (id BINARY(16) NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, icon VARCHAR(255) DEFAULT NULL, required_level INT DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_D5311670989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_inventory (id BINARY(16) NOT NULL, quantity INT DEFAULT 1 NOT NULL, equipped TINYINT DEFAULT 0 NOT NULL, current_durability INT DEFAULT NULL, obtained_at DATETIME NOT NULL, expires_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, user_id BINARY(16) NOT NULL, item_catalog_id BINARY(16) NOT NULL, INDEX IDX_B1CDC7D2A76ED395 (user_id), INDEX IDX_B1CDC7D2718D4EF6 (item_catalog_id), INDEX idx_user_inventory_user_item (user_id, item_catalog_id), INDEX idx_user_inventory_user_equipped (user_id, equipped), INDEX idx_user_inventory_user_deleted (user_id, deleted_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_skills (id BINARY(16) NOT NULL, unlocked_at DATETIME NOT NULL, user_id BINARY(16) NOT NULL, skill_id BINARY(16) NOT NULL, INDEX IDX_B0630D4DA76ED395 (user_id), INDEX IDX_B0630D4D5585C142 (skill_id), UNIQUE INDEX uniq_user_skill (user_id, skill_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE item_stat_bonuses ADD CONSTRAINT FK_AE95458A718D4EF6 FOREIGN KEY (item_catalog_id) REFERENCES item_catalog (id)');
        $this->addSql('ALTER TABLE skill_stat_bonuses ADD CONSTRAINT FK_F3A328495585C142 FOREIGN KEY (skill_id) REFERENCES skills (id)');
        $this->addSql('ALTER TABLE user_inventory ADD CONSTRAINT FK_B1CDC7D2A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE user_inventory ADD CONSTRAINT FK_B1CDC7D2718D4EF6 FOREIGN KEY (item_catalog_id) REFERENCES item_catalog (id)');
        $this->addSql('ALTER TABLE user_skills ADD CONSTRAINT FK_B0630D4DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE user_skills ADD CONSTRAINT FK_B0630D4D5585C142 FOREIGN KEY (skill_id) REFERENCES skills (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE item_stat_bonuses DROP FOREIGN KEY FK_AE95458A718D4EF6');
        $this->addSql('ALTER TABLE skill_stat_bonuses DROP FOREIGN KEY FK_F3A328495585C142');
        $this->addSql('ALTER TABLE user_inventory DROP FOREIGN KEY FK_B1CDC7D2A76ED395');
        $this->addSql('ALTER TABLE user_inventory DROP FOREIGN KEY FK_B1CDC7D2718D4EF6');
        $this->addSql('ALTER TABLE user_skills DROP FOREIGN KEY FK_B0630D4DA76ED395');
        $this->addSql('ALTER TABLE user_skills DROP FOREIGN KEY FK_B0630D4D5585C142');
        $this->addSql('DROP TABLE item_catalog');
        $this->addSql('DROP TABLE item_stat_bonuses');
        $this->addSql('DROP TABLE skill_stat_bonuses');
        $this->addSql('DROP TABLE skills');
        $this->addSql('DROP TABLE user_inventory');
        $this->addSql('DROP TABLE user_skills');
    }
}
