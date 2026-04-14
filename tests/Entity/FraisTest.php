<?php

namespace App\Tests\Entity;

use App\Entity\Frais;
use App\Entity\Appartement;
use PHPUnit\Framework\TestCase;

class FraisTest extends TestCase
{
    private Frais $frais;

    protected function setUp(): void
    {
        $this->frais = new Frais();
    }

    public function testDefaultValues(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->frais->getCreatedAt());
        $this->assertNull($this->frais->getAppartement());
    }

    public function testSettersAndGetters(): void
    {
        $this->frais->setTypeFrais(Frais::TYPE_NETTOYAGE);
        $this->assertSame('nettoyage', $this->frais->getTypeFrais());

        $this->frais->setLibelle('Nettoyage annuel');
        $this->assertSame('Nettoyage annuel', $this->frais->getLibelle());

        $this->frais->setMontant('180.00');
        $this->assertSame('180.00', $this->frais->getMontant());

        $this->frais->setPeriodicite(Frais::PERIODICITE_ANNUEL);
        $this->assertSame('annuel', $this->frais->getPeriodicite());

        $this->frais->setAnnee(2026);
        $this->assertSame(2026, $this->frais->getAnnee());

        $this->frais->setMois(3);
        $this->assertSame(3, $this->frais->getMois());

        $this->frais->setDescription('Description test');
        $this->assertSame('Description test', $this->frais->getDescription());
    }

    public function testTypeFraisLabel(): void
    {
        $this->frais->setTypeFrais(Frais::TYPE_HEBERGEMENT_SITE);
        $this->assertSame('Hébergement du site', $this->frais->getTypeFraisLabel());

        $this->frais->setTypeFrais(Frais::TYPE_NETTOYAGE);
        $this->assertSame('Nettoyage', $this->frais->getTypeFraisLabel());

        $this->frais->setTypeFrais(Frais::TYPE_REPARATION);
        $this->assertSame('Réparation', $this->frais->getTypeFraisLabel());

        $this->frais->setTypeFrais(Frais::TYPE_ASSURANCE);
        $this->assertSame('Assurance', $this->frais->getTypeFraisLabel());
    }

    public function testRelationAppartement(): void
    {
        $appart = new Appartement();
        $this->frais->setAppartement($appart);
        $this->assertSame($appart, $this->frais->getAppartement());

        $this->frais->setAppartement(null);
        $this->assertNull($this->frais->getAppartement());
    }

    public function testConstants(): void
    {
        $this->assertArrayHasKey('hebergement_site', Frais::TYPES_LABELS);
        $this->assertArrayHasKey('nettoyage', Frais::TYPES_LABELS);
        $this->assertArrayHasKey('reparation', Frais::TYPES_LABELS);
        $this->assertArrayHasKey('annuel', Frais::PERIODICITE_LABELS);
        $this->assertArrayHasKey('mensuel', Frais::PERIODICITE_LABELS);
        $this->assertArrayHasKey('ponctuel', Frais::PERIODICITE_LABELS);
    }
}
