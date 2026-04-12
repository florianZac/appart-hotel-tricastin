<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260412161736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE frais (id INT AUTO_INCREMENT NOT NULL, appartement_id INT DEFAULT NULL, type_frais VARCHAR(50) NOT NULL, libelle VARCHAR(255) NOT NULL, montant NUMERIC(10, 2) NOT NULL, periodicite VARCHAR(20) NOT NULL, mois INT DEFAULT NULL, annee INT NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_25404C98E1729BBA (appartement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE frais ADD CONSTRAINT FK_25404C98E1729BBA FOREIGN KEY (appartement_id) REFERENCES appartement (id)');
        $this->addSql('ALTER TABLE reservation ADD numero_facture VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE frais DROP FOREIGN KEY FK_25404C98E1729BBA');
        $this->addSql('DROP TABLE frais');
        $this->addSql('ALTER TABLE reservation DROP numero_facture');
    }
}
