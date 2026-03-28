<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328192253 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activity_categories (id BINARY(16) NOT NULL, slug VARCHAR(50) NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_6F117860989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE activity_types (id BINARY(16) NOT NULL, slug VARCHAR(100) NOT NULL, name VARCHAR(100) NOT NULL, flutter_enum VARCHAR(100) NOT NULL, ios_native VARCHAR(150) DEFAULT NULL, android_native VARCHAR(150) DEFAULT NULL, platform_support VARCHAR(20) NOT NULL, fallback_slug VARCHAR(100) DEFAULT NULL, category_id BINARY(16) NOT NULL, UNIQUE INDEX UNIQ_4732BF83989D9B62 (slug), INDEX idx_activity_type_category (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE professions (id BINARY(16) NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, tier INT NOT NULL, description LONGTEXT DEFAULT NULL, primary_stat VARCHAR(20) NOT NULL, secondary_stat VARCHAR(20) NOT NULL, category_id BINARY(16) NOT NULL, image_id BINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_2FDA85FA989D9B62 (slug), INDEX IDX_2FDA85FA12469DE2 (category_id), INDEX IDX_2FDA85FA3DA5256D (image_id), INDEX idx_profession_category_tier (category_id, tier), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_professions (id BINARY(16) NOT NULL, unlocked_at DATETIME NOT NULL, active TINYINT DEFAULT 1 NOT NULL, user_id BINARY(16) NOT NULL, profession_id BINARY(16) NOT NULL, INDEX IDX_86C14C6FA76ED395 (user_id), INDEX IDX_86C14C6FFDEF8996 (profession_id), INDEX idx_user_profession_active (user_id, active), UNIQUE INDEX uniq_user_profession (user_id, profession_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE activity_types ADD CONSTRAINT FK_4732BF8312469DE2 FOREIGN KEY (category_id) REFERENCES activity_categories (id)');
        $this->addSql('ALTER TABLE professions ADD CONSTRAINT FK_2FDA85FA12469DE2 FOREIGN KEY (category_id) REFERENCES activity_categories (id)');
        $this->addSql('ALTER TABLE professions ADD CONSTRAINT FK_2FDA85FA3DA5256D FOREIGN KEY (image_id) REFERENCES media_files (id)');
        $this->addSql('ALTER TABLE user_professions ADD CONSTRAINT FK_86C14C6FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE user_professions ADD CONSTRAINT FK_86C14C6FFDEF8996 FOREIGN KEY (profession_id) REFERENCES professions (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity_types DROP FOREIGN KEY FK_4732BF8312469DE2');
        $this->addSql('ALTER TABLE professions DROP FOREIGN KEY FK_2FDA85FA12469DE2');
        $this->addSql('ALTER TABLE professions DROP FOREIGN KEY FK_2FDA85FA3DA5256D');
        $this->addSql('ALTER TABLE user_professions DROP FOREIGN KEY FK_86C14C6FA76ED395');
        $this->addSql('ALTER TABLE user_professions DROP FOREIGN KEY FK_86C14C6FFDEF8996');
        $this->addSql('DROP TABLE activity_categories');
        $this->addSql('DROP TABLE activity_types');
        $this->addSql('DROP TABLE professions');
        $this->addSql('DROP TABLE user_professions');
    }
}
