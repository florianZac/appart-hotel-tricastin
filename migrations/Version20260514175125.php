<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260514175125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE seo_cocon (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, mot_cle_cocon VARCHAR(150) DEFAULT NULL, couleur VARCHAR(7) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE seo_page (id INT AUTO_INCREMENT NOT NULL, cocon_id INT DEFAULT NULL, route VARCHAR(100) NOT NULL, label VARCHAR(150) NOT NULL, titre VARCHAR(255) DEFAULT NULL, h1 VARCHAR(255) DEFAULT NULL, focus_keyword VARCHAR(150) DEFAULT NULL, secondary_keywords LONGTEXT DEFAULT NULL, description LONGTEXT DEFAULT NULL, robots VARCHAR(50) NOT NULL, canonical VARCHAR(512) DEFAULT NULL, og_image VARCHAR(512) DEFAULT NULL, og_type VARCHAR(30) NOT NULL, schema_type VARCHAR(50) DEFAULT NULL, faq_items LONGTEXT DEFAULT NULL, schema_extra LONGTEXT DEFAULT NULL, hreflang_fr VARCHAR(512) DEFAULT NULL, hreflang_en VARCHAR(512) DEFAULT NULL, breadcrumb_label VARCHAR(100) DEFAULT NULL, is_cocon_pivot TINYINT(1) NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_E8DCA6F191434547 (cocon_id), UNIQUE INDEX uniq_seo_route (route), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE seo_page ADD CONSTRAINT FK_E8DCA6F191434547 FOREIGN KEY (cocon_id) REFERENCES seo_cocon (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seo_page DROP FOREIGN KEY FK_E8DCA6F191434547');
        $this->addSql('DROP TABLE seo_cocon');
        $this->addSql('DROP TABLE seo_page');
    }
}
