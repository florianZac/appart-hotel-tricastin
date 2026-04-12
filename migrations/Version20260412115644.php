<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260412115644 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation ADD avis_email_envoye TINYINT(1) NOT NULL, ADD avis_email_envoye_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE temoignage ADD appartement_id INT DEFAULT NULL, ADD reservation_id INT DEFAULT NULL, ADD statut VARCHAR(20) NOT NULL, ADD validated_at DATETIME DEFAULT NULL, ADD email_envoye TINYINT(1) NOT NULL, ADD email_envoye_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE temoignage ADD CONSTRAINT FK_BDADBC46E1729BBA FOREIGN KEY (appartement_id) REFERENCES appartement (id)');
        $this->addSql('ALTER TABLE temoignage ADD CONSTRAINT FK_BDADBC46B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('CREATE INDEX IDX_BDADBC46E1729BBA ON temoignage (appartement_id)');
        $this->addSql('CREATE INDEX IDX_BDADBC46B83297E7 ON temoignage (reservation_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation DROP avis_email_envoye, DROP avis_email_envoye_at');
        $this->addSql('ALTER TABLE temoignage DROP FOREIGN KEY FK_BDADBC46E1729BBA');
        $this->addSql('ALTER TABLE temoignage DROP FOREIGN KEY FK_BDADBC46B83297E7');
        $this->addSql('DROP INDEX IDX_BDADBC46E1729BBA ON temoignage');
        $this->addSql('DROP INDEX IDX_BDADBC46B83297E7 ON temoignage');
        $this->addSql('ALTER TABLE temoignage DROP appartement_id, DROP reservation_id, DROP statut, DROP validated_at, DROP email_envoye, DROP email_envoye_at');
    }
}
