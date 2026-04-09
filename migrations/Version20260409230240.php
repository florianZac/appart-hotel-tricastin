<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260409230240 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE appartement (id INT AUTO_INCREMENT NOT NULL, localisation_id INT NOT NULL, nom VARCHAR(100) NOT NULL, slug VARCHAR(50) NOT NULL, type VARCHAR(20) NOT NULL, surface INT NOT NULL, capacite_min INT NOT NULL, capacite_max INT NOT NULL, description LONGTEXT NOT NULL, equipements LONGTEXT DEFAULT NULL, image_principale VARCHAR(255) NOT NULL, galerie LONGTEXT DEFAULT NULL, prix_par_nuit NUMERIC(8, 2) NOT NULL, actif TINYINT(1) NOT NULL, ordre INT NOT NULL, INDEX IDX_71A6BD8DC68BE09C (localisation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE disponibilite (id INT AUTO_INCREMENT NOT NULL, appartement_id INT NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, note VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_2CBACE2FE1729BBA (appartement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE localisation (id INT AUTO_INCREMENT NOT NULL, ville VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, adresse VARCHAR(255) NOT NULL, code_postal VARCHAR(10) NOT NULL, description LONGTEXT DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', image_couverture VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, reservation_id INT DEFAULT NULL, appartement_id INT DEFAULT NULL, type VARCHAR(30) NOT NULL, montant NUMERIC(10, 2) NOT NULL, devise VARCHAR(3) NOT NULL, statut VARCHAR(20) NOT NULL, stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, stripe_session_id VARCHAR(255) DEFAULT NULL, stripe_invoice_id VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, date_echeance DATE DEFAULT NULL, created_at DATETIME NOT NULL, paid_at DATETIME DEFAULT NULL, INDEX IDX_6D28840DA76ED395 (user_id), INDEX IDX_6D28840DB83297E7 (reservation_id), INDEX IDX_6D28840DE1729BBA (appartement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, appartement_id INT NOT NULL, user_id INT DEFAULT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, telephone VARCHAR(20) NOT NULL, date_arrivee DATE NOT NULL, date_depart DATE NOT NULL, nombre_personnes INT NOT NULL, message LONGTEXT DEFAULT NULL, statut VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_42C84955E1729BBA (appartement_id), INDEX IDX_42C84955A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE temoignage (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, auteur VARCHAR(100) NOT NULL, contenu LONGTEXT NOT NULL, note INT NOT NULL, avatar VARCHAR(255) DEFAULT NULL, actif TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_BDADBC46A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE appartement ADD CONSTRAINT FK_71A6BD8DC68BE09C FOREIGN KEY (localisation_id) REFERENCES localisation (id)');
        $this->addSql('ALTER TABLE disponibilite ADD CONSTRAINT FK_2CBACE2FE1729BBA FOREIGN KEY (appartement_id) REFERENCES appartement (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DE1729BBA FOREIGN KEY (appartement_id) REFERENCES appartement (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955E1729BBA FOREIGN KEY (appartement_id) REFERENCES appartement (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE temoignage ADD CONSTRAINT FK_BDADBC46A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appartement DROP FOREIGN KEY FK_71A6BD8DC68BE09C');
        $this->addSql('ALTER TABLE disponibilite DROP FOREIGN KEY FK_2CBACE2FE1729BBA');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DA76ED395');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DB83297E7');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DE1729BBA');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955E1729BBA');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A76ED395');
        $this->addSql('ALTER TABLE temoignage DROP FOREIGN KEY FK_BDADBC46A76ED395');
        $this->addSql('DROP TABLE appartement');
        $this->addSql('DROP TABLE disponibilite');
        $this->addSql('DROP TABLE localisation');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE temoignage');
        $this->addSql('DROP TABLE user');
    }
}
