<?php

namespace App\Tests\Entity;

use App\Entity\Reservation;
use App\Entity\Appartement;
use App\Entity\User;
use App\Entity\Payment;
use PHPUnit\Framework\TestCase;

class ReservationTest extends TestCase
{
    private Reservation $reservation;

    protected function setUp(): void
    {
        $this->reservation = new Reservation();
    }

    public function testDefaultValues(): void
    {
        $this->assertSame('en_attente', $this->reservation->getStatut());
        $this->assertSame('non_paye', $this->reservation->getPaiementStatut());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->reservation->getCreatedAt());
        $this->assertFalse($this->reservation->isAvisEmailEnvoye());
    }

    public function testSettersAndGetters(): void
    {
        $this->reservation->setNom('Dupont');
        $this->assertSame('Dupont', $this->reservation->getNom());

        $this->reservation->setPrenom('Jean');
        $this->assertSame('Jean', $this->reservation->getPrenom());

        $this->reservation->setEmail('jean@email.fr');
        $this->assertSame('jean@email.fr', $this->reservation->getEmail());

        $this->reservation->setTelephone('0601020304');
        $this->assertSame('0601020304', $this->reservation->getTelephone());

        $this->reservation->setNombrePersonnes(3);
        $this->assertSame(3, $this->reservation->getNombrePersonnes());

        $this->reservation->setMessage('Test message');
        $this->assertSame('Test message', $this->reservation->getMessage());

        $this->reservation->setMontantTotal('350.00');
        $this->assertSame('350.00', $this->reservation->getMontantTotal());

        $this->reservation->setNumeroFacture('FAC-2026-0001');
        $this->assertSame('FAC-2026-0001', $this->reservation->getNumeroFacture());
    }

    public function testDates(): void
    {
        $arrivee = new \DateTime('2026-07-01');
        $depart  = new \DateTime('2026-07-08');

        $this->reservation->setDateArrivee($arrivee);
        $this->reservation->setDateDepart($depart);

        $this->assertSame($arrivee, $this->reservation->getDateArrivee());
        $this->assertSame($depart, $this->reservation->getDateDepart());
    }

    public function testNombreNuits(): void
    {
        $this->reservation->setDateArrivee(new \DateTime('2026-07-01'));
        $this->reservation->setDateDepart(new \DateTime('2026-07-08'));

        $this->assertSame(7, $this->reservation->getNombreNuits());
    }

    public function testNombreNuitsSansDate(): void
    {
        $this->assertSame(0, $this->reservation->getNombreNuits());
    }

    public function testCalculerMontantTotal(): void
    {
        $appartement = new Appartement();
        $appartement->setPrixParNuit('85.00');

        $this->reservation->setAppartement($appartement);
        $this->reservation->setDateArrivee(new \DateTime('2026-07-01'));
        $this->reservation->setDateDepart(new \DateTime('2026-07-08'));

        $montant = $this->reservation->calculerMontantTotal();
        $this->assertSame('595.00', $montant);
    }

    public function testStatutLabels(): void
    {
        $this->reservation->setStatut(Reservation::STATUT_EN_ATTENTE);
        $this->assertSame('En attente', $this->reservation->getStatutLabel());

        $this->reservation->setStatut(Reservation::STATUT_CONFIRMEE);
        $this->assertSame('Confirmée', $this->reservation->getStatutLabel());

        $this->reservation->setStatut(Reservation::STATUT_ANNULEE);
        $this->assertSame('Annulée', $this->reservation->getStatutLabel());

        $this->reservation->setStatut(Reservation::STATUT_TERMINEE);
        $this->assertSame('Terminée', $this->reservation->getStatutLabel());
    }

    public function testStatutBadgeClass(): void
    {
        $this->reservation->setStatut(Reservation::STATUT_EN_ATTENTE);
        $this->assertSame('warning', $this->reservation->getStatutBadgeClass());

        $this->reservation->setStatut(Reservation::STATUT_CONFIRMEE);
        $this->assertSame('success', $this->reservation->getStatutBadgeClass());

        $this->reservation->setStatut(Reservation::STATUT_ANNULEE);
        $this->assertSame('danger', $this->reservation->getStatutBadgeClass());
    }

    public function testTotalPaye(): void
    {
        // Sans paiements
        $this->assertSame(0.0, $this->reservation->getTotalPaye());
    }

    public function testSoldeRestant(): void
    {
        $this->reservation->setMontantTotal('500.00');
        $this->assertSame(500.0, $this->reservation->getSoldeRestant());
    }

    public function testRelationAppartement(): void
    {
        $appartement = new Appartement();
        $appartement->setNom('Studio Lavande');

        $this->reservation->setAppartement($appartement);
        $this->assertSame($appartement, $this->reservation->getAppartement());
    }

    public function testRelationUser(): void
    {
        $user = new User();
        $this->reservation->setUser($user);
        $this->assertSame($user, $this->reservation->getUser());
    }

    public function testConstants(): void
    {
        $this->assertSame('en_attente', Reservation::STATUT_EN_ATTENTE);
        $this->assertSame('confirmee', Reservation::STATUT_CONFIRMEE);
        $this->assertSame('annulee', Reservation::STATUT_ANNULEE);
        $this->assertSame('terminee', Reservation::STATUT_TERMINEE);
        $this->assertSame('non_paye', Reservation::PAIEMENT_NON_PAYE);
        $this->assertSame('acompte_paye', Reservation::PAIEMENT_ACOMPTE);
        $this->assertSame('paye', Reservation::PAIEMENT_COMPLET);
    }
}
