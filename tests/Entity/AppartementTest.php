<?php

namespace App\Tests\Entity;

use App\Entity\Appartement;
use App\Entity\Localisation;
use App\Entity\Reservation;
use PHPUnit\Framework\TestCase;

class AppartementTest extends TestCase
{
    private Appartement $appartement;

    protected function setUp(): void
    {
        $this->appartement = new Appartement();
    }

    public function testDefaultValues(): void
    {
        $this->assertTrue($this->appartement->isActif());
        $this->assertSame(0, $this->appartement->getOrdre());
        $this->assertCount(0, $this->appartement->getReservations());
    }

    public function testSettersAndGetters(): void
    {
        $this->appartement->setNom('Studio Lavande');
        $this->assertSame('Studio Lavande', $this->appartement->getNom());
        $this->assertSame('Studio Lavande', (string) $this->appartement);

        $this->appartement->setSlug('studio-lavande');
        $this->assertSame('studio-lavande', $this->appartement->getSlug());

        $this->appartement->setType('T2');
        $this->assertSame('T2', $this->appartement->getType());

        $this->appartement->setSurface(45);
        $this->assertSame(45, $this->appartement->getSurface());

        $this->appartement->setCapaciteMin(1);
        $this->assertSame(1, $this->appartement->getCapaciteMin());

        $this->appartement->setCapaciteMax(4);
        $this->assertSame(4, $this->appartement->getCapaciteMax());

        $this->appartement->setDescription('Un bel appartement');
        $this->assertSame('Un bel appartement', $this->appartement->getDescription());

        $this->appartement->setPrixParNuit('85.00');
        $this->assertSame('85.00', $this->appartement->getPrixParNuit());

        $this->appartement->setImagePrincipale('https://example.com/img.jpg');
        $this->assertSame('https://example.com/img.jpg', $this->appartement->getImagePrincipale());
    }

    public function testEquipementsJsonStorage(): void
    {
        $equipements = ['Wi-Fi', 'Parking', 'Climatisation'];
        $this->appartement->setEquipements($equipements);

        $this->assertSame($equipements, $this->appartement->getEquipements());
    }

    public function testEquipementsNull(): void
    {
        $this->assertSame([], $this->appartement->getEquipements());

        $this->appartement->setEquipements(null);
        $this->assertSame([], $this->appartement->getEquipements());
    }

    public function testGalerieJsonStorage(): void
    {
        $galerie = ['img1.jpg', 'img2.jpg'];
        $this->appartement->setGalerie($galerie);

        $this->assertSame($galerie, $this->appartement->getGalerie());
    }

    public function testGalerieNull(): void
    {
        $this->assertSame([], $this->appartement->getGalerie());
    }

    public function testRelationLocalisation(): void
    {
        $localisation = new Localisation();
        $localisation->setVille('Tulette');

        $this->appartement->setLocalisation($localisation);
        $this->assertSame($localisation, $this->appartement->getLocalisation());
    }

    public function testAddReservation(): void
    {
        $reservation = new Reservation();
        $this->appartement->addReservation($reservation);

        $this->assertCount(1, $this->appartement->getReservations());
        $this->assertSame($this->appartement, $reservation->getAppartement());
    }

    public function testAddReservationNoDuplicate(): void
    {
        $reservation = new Reservation();
        $this->appartement->addReservation($reservation);
        $this->appartement->addReservation($reservation);

        $this->assertCount(1, $this->appartement->getReservations());
    }

    public function testActif(): void
    {
        $this->appartement->setActif(false);
        $this->assertFalse($this->appartement->isActif());
    }
}
