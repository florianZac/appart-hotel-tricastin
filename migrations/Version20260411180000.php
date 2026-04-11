<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajout des champs reset_token et reset_token_expires_at sur la table user
 * pour la réinitialisation du mot de passe par email.
 */
final class Version20260411180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout reset_token et reset_token_expires_at pour la réinitialisation du mot de passe';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD reset_token VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD reset_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP reset_token');
        $this->addSql('ALTER TABLE user DROP reset_token_expires_at');
    }
}
