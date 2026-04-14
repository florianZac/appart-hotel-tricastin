<?php

namespace App\Tests\Entity;

use App\Entity\Localisation;
use App\Entity\Appartement;
use PHPUnit\Framework\TestCase;

class LocalisationTest extends TestCase
{
    private Localisation $localisation;

    protected function setUp(): void
    {
        $this->localisation = new Localisation();
    }

    public function testDefaultValues(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->localisation->getCreatedAt());
        $this->assertCount(0, $this->localisation->getAppartements());
    }

    public function testSettersAndGetters(): void
    {
        $this->localisation->setVille('Tulette');
        $this->assertSame('Tulette', $this->localisation->getVille());
        $this->assertSame('Tulette', (string) $this->localisation);

        $this->localisation->setSlug('tulette');
        $this->assertSame('tulette', $this->localisation->getSlug());

        $this->localisation->setAdresse('10 rue du Château');
        $this->assertSame('10 rue du Château', $this->localisation->getAdresse());

        $this->localisation->setCodePostal('26790');
        $this->assertSame('26790', $this->localisation->getCodePostal());

        $this->localisation->setEmail('contact@test.fr');
        $this->assertSame('contact@test.fr', $this->localisation->getEmail());

        $this->localisation->setTelephone('04 75 00 00 00');
        $this->assertSame('04 75 00 00 00', $this->localisation->getTelephone());

        $this->localisation->setDescription('Jolie ville');
        $this->assertSame('Jolie ville', $this->localisation->getDescription());

        $this->localisation->setImageCouverture('cover.jpg');
        $this->assertSame('cover.jpg', $this->localisation->getImageCouverture());
    }

    public function testAddRemoveAppartement(): void
    {
        $appart = new Appartement();
        $this->localisation->addAppartement($appart);

        $this->assertCount(1, $this->localisation->getAppartements());
        $this->assertSame($this->localisation, $appart->getLocalisation());

        $this->localisation->removeAppartement($appart);
        $this->assertCount(0, $this->localisation->getAppartements());
    }

    public function testAddAppartementNoDuplicate(): void
    {
        $appart = new Appartement();
        $this->localisation->addAppartement($appart);
        $this->localisation->addAppartement($appart);

        $this->assertCount(1, $this->localisation->getAppartements());
    }
}
