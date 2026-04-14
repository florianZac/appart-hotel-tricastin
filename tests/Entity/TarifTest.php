<?php

namespace App\Tests\Entity;

use App\Entity\Tarif;
use App\Entity\Appartement;
use PHPUnit\Framework\TestCase;

class TarifTest extends TestCase
{
    private Tarif $tarif;

    protected function setUp(): void
    {
        $this->tarif = new Tarif();
    }

    public function testSettersAndGetters(): void
    {
        $this->tarif->setSaison('Haute saison');
        $this->assertSame('Haute saison', $this->tarif->getSaison());

        $this->tarif->setPrixJour(85.0);
        $this->assertSame(85.0, $this->tarif->getPrixJour());

        $this->tarif->setPrixSemaine(500.0);
        $this->assertSame(500.0, $this->tarif->getPrixSemaine());

        $this->tarif->setPrixMois(1800.0);
        $this->assertSame(1800.0, $this->tarif->getPrixMois());
    }

    public function testDates(): void
    {
        $debut = new \DateTime('2026-07-01');
        $fin   = new \DateTime('2026-08-31');

        $this->tarif->setDateDebut($debut);
        $this->tarif->setDateFin($fin);

        $this->assertSame($debut, $this->tarif->getDateDebut());
        $this->assertSame($fin, $this->tarif->getDateFin());
    }

    public function testRelationAppartement(): void
    {
        $appart = new Appartement();
        $this->tarif->setAppartement($appart);
        $this->assertSame($appart, $this->tarif->getAppartement());
    }
}
