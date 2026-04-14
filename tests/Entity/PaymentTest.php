<?php

namespace App\Tests\Entity;

use App\Entity\Payment;
use App\Entity\User;
use App\Entity\Reservation;
use PHPUnit\Framework\TestCase;

class PaymentTest extends TestCase
{
    private Payment $payment;

    protected function setUp(): void
    {
        $this->payment = new Payment();
    }

    public function testDefaultValues(): void
    {
        $this->assertSame('en_attente', $this->payment->getStatut());
        $this->assertSame('EUR', $this->payment->getDevise());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->payment->getCreatedAt());
        $this->assertNull($this->payment->getPaidAt());
    }

    public function testSettersAndGetters(): void
    {
        $this->payment->setType(Payment::TYPE_RESERVATION);
        $this->assertSame('reservation', $this->payment->getType());

        $this->payment->setMontant('350.00');
        $this->assertSame('350.00', $this->payment->getMontant());

        $this->payment->setDescription('Paiement test');
        $this->assertSame('Paiement test', $this->payment->getDescription());

        $this->payment->setStripeSessionId('cs_test_123');
        $this->assertSame('cs_test_123', $this->payment->getStripeSessionId());

        $this->payment->setStripePaymentIntentId('pi_test_456');
        $this->assertSame('pi_test_456', $this->payment->getStripePaymentIntentId());
    }

    public function testTypeLabels(): void
    {
        $this->payment->setType(Payment::TYPE_LOYER);
        $this->assertSame('Loyer mensuel', $this->payment->getTypeLabel());

        $this->payment->setType(Payment::TYPE_CAUTION);
        $this->assertSame('Caution / Dépôt de garantie', $this->payment->getTypeLabel());

        $this->payment->setType(Payment::TYPE_RESERVATION);
        $this->assertSame('Paiement de réservation', $this->payment->getTypeLabel());
    }

    public function testStatutLabels(): void
    {
        $this->assertSame('En attente', $this->payment->getStatutLabel());

        $this->payment->setStatut(Payment::STATUT_REUSSI);
        $this->assertSame('Payé', $this->payment->getStatutLabel());

        $this->payment->setStatut(Payment::STATUT_ECHOUE);
        $this->assertSame('Échoué', $this->payment->getStatutLabel());

        $this->payment->setStatut(Payment::STATUT_REMBOURSE);
        $this->assertSame('Remboursé', $this->payment->getStatutLabel());
    }

    public function testStatutBadgeClass(): void
    {
        $this->assertSame('warning', $this->payment->getStatutBadgeClass());

        $this->payment->setStatut(Payment::STATUT_REUSSI);
        $this->assertSame('success', $this->payment->getStatutBadgeClass());
    }

    public function testIsEnRetard(): void
    {
        $this->assertFalse($this->payment->isEnRetard());

        $this->payment->setDateEcheance(new \DateTime('-1 day'));
        $this->assertTrue($this->payment->isEnRetard());

        $this->payment->setDateEcheance(new \DateTime('+1 day'));
        $this->assertFalse($this->payment->isEnRetard());

        // Payé = pas en retard même si date passée
        $this->payment->setDateEcheance(new \DateTime('-1 day'));
        $this->payment->setStatut(Payment::STATUT_REUSSI);
        $this->assertFalse($this->payment->isEnRetard());
    }

    public function testRelations(): void
    {
        $user = new User();
        $this->payment->setUser($user);
        $this->assertSame($user, $this->payment->getUser());

        $reservation = new Reservation();
        $this->payment->setReservation($reservation);
        $this->assertSame($reservation, $this->payment->getReservation());
    }
}
