<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\Reservation;
use App\Entity\User;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Webhook;

/**
 * @author      Florian Aizac
 * @created     09/04/2026
 * @description Service gérant l'intégration Stripe pour les paiements en ligne
 *
 *  1. createCheckoutSession()       : Crée une session Stripe Checkout pour un paiement
 *  2. createPaymentForReservation() : Crée un paiement de type réservation
 *  3. createPaymentGeneric()        : Crée un paiement générique (loyer, caution, etc.)
 *  4. handleWebhookEvent()          : Traite les événements webhook Stripe
 *  5. refundPayment()               : Rembourse un paiement
 *  6. retrieveSession()             : Récupère les détails d'une session Stripe
 */
class StripeService
{
	private string $secretKey;
	private string $webhookSecret;
	private string $appUrl;

	public function __construct(
		string $stripeSecretKey,
		string $stripeWebhookSecret,
		string $appUrl
	) {
		$this->secretKey = $stripeSecretKey;
		$this->webhookSecret = $stripeWebhookSecret;
		$this->appUrl = $appUrl;
		Stripe::setApiKey($this->secretKey);
	}

	/**
	 * Crée une session Stripe Checkout
	 *
	 * @param Payment $payment   Le paiement à traiter
	 * @param string  $successUrl URL de redirection après succès
	 * @param string  $cancelUrl  URL de redirection après annulation
	 * @return Session La session Stripe créée
	 * @throws ApiErrorException
	 */
	public function createCheckoutSession(
		Payment $payment,
		string $successUrl,
		string $cancelUrl
	): Session {
		$session = Session::create([
			'payment_method_types' => ['card'],
			'line_items' => [[
				'price_data' => [
					'currency' => strtolower($payment->getDevise()),
					'product_data' => [
						'name' => $payment->getTypeLabel(),
						'description' => $payment->getDescription() ?? sprintf(
							'Paiement %s — Appart Hôtel Tricastin',
							$payment->getTypeLabel()
						),
					],
					'unit_amount' => (int) round((float) $payment->getMontant() * 100),
				],
				'quantity' => 1,
			]],
			'mode' => 'payment',
			'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
			'cancel_url' => $cancelUrl,
			'customer_email' => $payment->getUser()->getEmail(),
			'metadata' => [
				'payment_id'     => $payment->getId(),
				'payment_type'   => $payment->getType(),
				'user_id'        => $payment->getUser()->getId(),
				'reservation_id' => $payment->getReservation()?->getId(),
			],
		]);

		return $session;
	}

	/**
	 * Crée une session Checkout spécifique pour le paiement d'une réservation
	 */
	public function createReservationCheckout(
		Reservation $reservation,
		User $user,
		string $montant,
		string $successUrl,
		string $cancelUrl
	): Session {
		$nuits = $reservation->getNombreNuits();
		$appart = $reservation->getAppartement();

		$session = Session::create([
			'payment_method_types' => ['card'],
			'line_items' => [[
				'price_data' => [
					'currency' => 'eur',
					'product_data' => [
						'name' => sprintf(
							'%s — %d nuit%s',
							$appart->getNom(),
							$nuits,
							$nuits > 1 ? 's' : ''
						),
						'description' => sprintf(
							'Du %s au %s — %s',
							$reservation->getDateArrivee()->format('d/m/Y'),
							$reservation->getDateDepart()->format('d/m/Y'),
							$appart->getLocalisation()->getVille()
						),
					],
					'unit_amount' => (int) round((float) $montant * 100),
				],
				'quantity' => 1,
			]],
			'mode' => 'payment',
			'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
			'cancel_url' => $cancelUrl,
			'customer_email' => $user->getEmail(),
			'metadata' => [
				'reservation_id' => $reservation->getId(),
				'user_id'        => $user->getId(),
				'type'           => Payment::TYPE_RESERVATION,
			],
		]);

		return $session;
	}

	/**
	 * Récupère les détails d'une session Stripe
	 * @throws ApiErrorException
	 */
	public function retrieveSession(string $sessionId): Session
	{
		return Session::retrieve($sessionId);
	}

	/**
	 * Rembourse un paiement Stripe
	 * @throws ApiErrorException
	 */
	public function refundPayment(string $paymentIntentId, ?int $montantCentimes = null): Refund
	{
		$params = ['payment_intent' => $paymentIntentId];

		if ($montantCentimes !== null) {
			$params['amount'] = $montantCentimes;
		}

		return Refund::create($params);
	}

	/**
	 * Construit un événement Webhook depuis la payload et la signature
	 * @throws \Stripe\Exception\SignatureVerificationException
	 */
	public function constructWebhookEvent(string $payload, string $sigHeader): \Stripe\Event
	{
		return Webhook::constructEvent(
			$payload,
			$sigHeader,
			$this->webhookSecret
		);
	}

	/**
	 * Retourne la clé publique Stripe (pour le frontend)
	 */
	public function getPublicKey(): string
	{
		// La clé publique est dérivée : sk_test_xxx → pk_test_xxx
		// En pratique on la passe via une variable d'environnement séparée
		return $_ENV['STRIPE_PUBLIC_KEY'] ?? '';
	}
}
