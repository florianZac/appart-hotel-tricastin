<?php

namespace App\Tests\Entity;

use App\Entity\Temoignage;
use PHPUnit\Framework\TestCase;

class TemoignageTest extends TestCase
{
    private Temoignage $temoignage;

    protected function setUp(): void
    {
        $this->temoignage = new Temoignage();
    }

    public function testDefaultValues(): void
    {
        $this->assertSame('en_attente', $this->temoignage->getStatut());
        $this->assertFalse($this->temoignage->isActif());
        $this->assertSame(5, $this->temoignage->getNote());
        $this->assertFalse($this->temoignage->isEmailEnvoye());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->temoignage->getCreatedAt());
    }

    public function testSettersAndGetters(): void
    {
        $this->temoignage->setAuteur('Marie Dupont');
        $this->assertSame('Marie Dupont', $this->temoignage->getAuteur());

        $this->temoignage->setContenu('Très bon séjour, appartement impeccable.');
        $this->assertSame('Très bon séjour, appartement impeccable.', $this->temoignage->getContenu());

        $this->temoignage->setNote(4);
        $this->assertSame(4, $this->temoignage->getNote());

        $this->temoignage->setAvatar('avatar.jpg');
        $this->assertSame('avatar.jpg', $this->temoignage->getAvatar());
    }

    public function testStatutApprouveActivateActif(): void
    {
        $this->temoignage->setStatut(Temoignage::STATUT_APPROUVE);
        $this->assertTrue($this->temoignage->isActif());
    }

    public function testStatutRefuseDeactivateActif(): void
    {
        $this->temoignage->setStatut(Temoignage::STATUT_APPROUVE);
        $this->assertTrue($this->temoignage->isActif());

        $this->temoignage->setStatut(Temoignage::STATUT_REFUSE);
        $this->assertFalse($this->temoignage->isActif());
    }

    public function testStatutLabels(): void
    {
        $this->assertSame('En attente', $this->temoignage->getStatutLabel());

        $this->temoignage->setStatut(Temoignage::STATUT_APPROUVE);
        $this->assertSame('Approuvé', $this->temoignage->getStatutLabel());

        $this->temoignage->setStatut(Temoignage::STATUT_REFUSE);
        $this->assertSame('Refusé', $this->temoignage->getStatutLabel());
    }

    public function testStatutBadgeClass(): void
    {
        $this->assertSame('warning', $this->temoignage->getStatutBadgeClass());

        $this->temoignage->setStatut(Temoignage::STATUT_APPROUVE);
        $this->assertSame('success', $this->temoignage->getStatutBadgeClass());

        $this->temoignage->setStatut(Temoignage::STATUT_REFUSE);
        $this->assertSame('danger', $this->temoignage->getStatutBadgeClass());
    }

    public function testEmailEnvoye(): void
    {
        $this->temoignage->setEmailEnvoye(true);
        $this->assertTrue($this->temoignage->isEmailEnvoye());

        $now = new \DateTime();
        $this->temoignage->setEmailEnvoyeAt($now);
        $this->assertSame($now, $this->temoignage->getEmailEnvoyeAt());
    }
}
