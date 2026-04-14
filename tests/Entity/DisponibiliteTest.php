<?php

namespace App\Tests\Entity;

use App\Entity\Disponibilite;
use App\Entity\Appartement;
use PHPUnit\Framework\TestCase;

class DisponibiliteTest extends TestCase
{
    private Disponibilite $dispo;

    protected function setUp(): void
    {
        $this->dispo = new Disponibilite();
    }

    public function testDefaultValues(): void
    {
        $this->assertSame('bloque', $this->dispo->getStatut());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->dispo->getCreatedAt());
    }

    public function testSettersAndGetters(): void
    {
        $debut = new \DateTime('2026-07-01');
        $fin   = new \DateTime('2026-07-15');

        $this->dispo->setDateDebut($debut);
        $this->dispo->setDateFin($fin);
        $this->dispo->setNote('Travaux plomberie');

        $this->assertSame($debut, $this->dispo->getDateDebut());
        $this->assertSame($fin, $this->dispo->getDateFin());
        $this->assertSame('Travaux plomberie', $this->dispo->getNote());
    }

    public function testStatutLabels(): void
    {
        $this->dispo->setStatut(Disponibilite::STATUT_DISPONIBLE);
        $this->assertSame('Disponible', $this->dispo->getStatutLabel());

        $this->dispo->setStatut(Disponibilite::STATUT_RESERVE);
        $this->assertSame('Réservé', $this->dispo->getStatutLabel());

        $this->dispo->setStatut(Disponibilite::STATUT_NETTOYAGE);
        $this->assertSame('Nettoyage', $this->dispo->getStatutLabel());

        $this->dispo->setStatut(Disponibilite::STATUT_BLOQUE);
        $this->assertSame('Bloqué', $this->dispo->getStatutLabel());
    }

    public function testCouleurs(): void
    {
        $this->dispo->setStatut(Disponibilite::STATUT_DISPONIBLE);
        $this->assertSame('#28a745', $this->dispo->getCouleur());

        $this->dispo->setStatut(Disponibilite::STATUT_RESERVE);
        $this->assertSame('#dc3545', $this->dispo->getCouleur());

        $this->dispo->setStatut(Disponibilite::STATUT_NETTOYAGE);
        $this->assertSame('#6c757d', $this->dispo->getCouleur());

        $this->dispo->setStatut(Disponibilite::STATUT_BLOQUE);
        $this->assertSame('#fd7e14', $this->dispo->getCouleur());
    }

    public function testRelationAppartement(): void
    {
        $appart = new Appartement();
        $this->dispo->setAppartement($appart);
        $this->assertSame($appart, $this->dispo->getAppartement());
    }

    public function testConstants(): void
    {
        $this->assertSame('disponible', Disponibilite::STATUT_DISPONIBLE);
        $this->assertSame('reserve', Disponibilite::STATUT_RESERVE);
        $this->assertSame('nettoyage', Disponibilite::STATUT_NETTOYAGE);
        $this->assertSame('bloque', Disponibilite::STATUT_BLOQUE);
    }
}
