<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\Reservation;
use App\Repository\PaymentRepository;
use App\Repository\ReservationRepository;
use App\Service\MailerService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @author      Florian Aizac
 * @created     09/04/2026
 * @description Contrôleur gérant les paiements Stripe :
 *              checkout, webhook, succès, annulation, remboursement
 */
class PaymentController extends AbstractController
{
	public function __construct(
		private StripeService $stripeService,
		private MailerService $mailerService,
		private EntityManagerInterface $em,
		private LoggerInterface $logger
	) {}

	// =========================================================================
	// CHECKOUT — Lancement du paiement
	// =========================================================================

	/**
	 * Crée une session Stripe Checkout pour le paiement d'une réservation
	 */
	#[Route('/paiement/reservation/{id}', name: 'payment_reservation_checkout', methods: ['POST'])]
	#[IsGranted('ROLE_USER')]
	public function reservationCheckout(
		int $id,
		ReservationRepository $reservationRepo,
		Request $request
	): Response {

		if (!$this->isCsrfTokenValid('payment_' . $id, $request->request->get('_token'))) {
			throw $this->createAccessDeniedException('Token CSRF invalide.');
		}
		$user = $this->getUser();
		$reservation = $reservationRepo->find($id);

		if (!$reservation || $reservation->getUser() !== $user) {
			throw $this->createNotFoundException('Réservation non trouvée.');
		}

		// Calcul du montant
		$montant = $reservation->calculerMontantTotal();
		if ((float) $montant <= 0) {
			$this->addFlash('danger', 'Montant de réservation invalide.');
			return $this->redirectToRoute('client_reservation_detail', ['id' => $id]);
		}

		// Créer le Payment en base
		$payment = new Payment();
		$payment->setUser($user);
		$payment->setReservation($reservation);
		$payment->setAppartement($reservation->getAppartement());
		$payment->setType(Payment::TYPE_RESERVATION);
		$payment->setMontant($montant);
		$payment->setDescription(sprintf(
			'Réservation %s — %d nuits du %s au %s',
			$reservation->getAppartement()->getNom(),
			$reservation->getNombreNuits(),
			$reservation->getDateArrivee()->format('d/m/Y'),
			$reservation->getDateDepart()->format('d/m/Y')
		));
		$this->em->persist($payment);
		$this->em->flush();

		// Créer la session Stripe
		try {
			$session = $this->stripeService->createCheckoutSession(
				$payment,
				$this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
				$this->generateUrl('payment_cancel', ['id' => $payment->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
			);

			$payment->setStripeSessionId($session->id);
			$reservation->setStripeSessionId($session->id);
			$this->em->flush();

			return $this->redirect($session->url);
		} catch (\Exception $e) {
			$this->logger->error('Erreur Stripe Checkout : ' . $e->getMessage());
			$this->addFlash('danger', 'Erreur lors de la création du paiement. Veuillez réessayer.');
			return $this->redirectToRoute('client_reservation_detail', ['id' => $id]);
		}
	}

	/**
	 * Crée un checkout pour un paiement générique (loyer, caution, etc.)
	 * Appelé depuis l'espace client sur un paiement en attente
	 */
	#[Route('/paiement/{id}/payer', name: 'payment_generic_checkout', methods: ['POST'])]
	#[IsGranted('ROLE_USER')]
	public function genericCheckout(int $id, PaymentRepository $paymentRepo,Request $request): Response
	{
		if (!$this->isCsrfTokenValid('payment_' . $id, $request->request->get('_token'))) {
			throw $this->createAccessDeniedException('Token CSRF invalide.');
		}
		$user = $this->getUser();
		$payment = $paymentRepo->find($id);

		if (!$payment || $payment->getUser() !== $user) {
			throw $this->createNotFoundException('Paiement non trouvé.');
		}

		if ($payment->getStatut() !== Payment::STATUT_EN_ATTENTE) {
			$this->addFlash('info', 'Ce paiement a déjà été traité.');
			return $this->redirectToRoute('client_paiements');
		}

		try {
			$session = $this->stripeService->createCheckoutSession(
				$payment,
				$this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
				$this->generateUrl('payment_cancel', ['id' => $payment->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
			);

			$payment->setStripeSessionId($session->id);
			$this->em->flush();

			return $this->redirect($session->url);
		} catch (\Exception $e) {
			$this->logger->error('Erreur Stripe Checkout générique : ' . $e->getMessage());
			$this->addFlash('danger', 'Erreur lors du paiement. Veuillez réessayer.');
			return $this->redirectToRoute('client_paiements');
		}
	}

	// =========================================================================
	// CALLBACK — Pages de retour Stripe
	// =========================================================================

	/**
	 * Page de succès après paiement Stripe
	 */
	#[Route('/paiement/succes', name: 'payment_success')]
	#[IsGranted('ROLE_USER')]
	public function success(Request $request, PaymentRepository $paymentRepo): Response
	{
		$sessionId = $request->query->get('session_id');

		if (!$sessionId) {
			return $this->redirectToRoute('client_dashboard');
		}

		// Récupérer le paiement via la session Stripe
		$payment = $paymentRepo->findOneBy(['stripeSessionId' => $sessionId]);

		// Vérifier la session côté Stripe
		try {
			$session = $this->stripeService->retrieveSession($sessionId);

			if ($session->payment_status === 'paid' && $payment) {
				// Mettre à jour le paiement si pas encore fait par le webhook
				if ($payment->getStatut() !== Payment::STATUT_REUSSI) {
					$payment->setStatut(Payment::STATUT_REUSSI);
					$payment->setPaidAt(new \DateTime());
					$payment->setStripePaymentIntentId($session->payment_intent);

					// Mettre à jour la réservation si c'est un paiement de réservation
					if ($payment->getReservation()) {
						$payment->getReservation()->setPaiementStatut(Reservation::PAIEMENT_COMPLET);
					}

					$this->em->flush();

					// Envoyer l'email de confirmation
					$this->mailerService->sendPaiementConfirmationEmail($payment);
				}
			}
		} catch (\Exception $e) {
			$this->logger->error('Erreur vérification session Stripe : ' . $e->getMessage());
		}

		return $this->render('payment/success.html.twig', [
			'payment' => $payment,
		]);
	}

	/**
	 * Page d'annulation de paiement
	 */
	#[Route('/paiement/annule/{id}', name: 'payment_cancel')]
	#[IsGranted('ROLE_USER')]
	public function cancel(int $id, PaymentRepository $paymentRepo): Response
	{
		$payment = $paymentRepo->find($id);

		return $this->render('payment/cancel.html.twig', [
			'payment' => $payment,
		]);
	}

	// =========================================================================
	// WEBHOOK — Événements Stripe
	// =========================================================================

	/**
	 * Endpoint Webhook Stripe — reçoit les événements asynchrones
	 * Ne nécessite PAS d'authentification (appel serveur Stripe)
	 */
	#[Route('/webhook/stripe', name: 'stripe_webhook', methods: ['POST'])]
	public function stripeWebhook(Request $request, PaymentRepository $paymentRepo): JsonResponse
	{
		$payload = $request->getContent();
		$sigHeader = $request->headers->get('Stripe-Signature');

		try {
			$event = $this->stripeService->constructWebhookEvent($payload, $sigHeader);
		} catch (\Exception $e) {
			$this->logger->error('Webhook Stripe signature invalide : ' . $e->getMessage());
			return new JsonResponse(['error' => 'Signature invalide'], 400);
		}

		// Traiter les événements pertinents
		switch ($event->type) {
			case 'checkout.session.completed':
				$this->handleCheckoutCompleted($event->data->object, $paymentRepo);
				break;

			case 'payment_intent.payment_failed':
				$this->handlePaymentFailed($event->data->object, $paymentRepo);
				break;

			default:
				$this->logger->info('Webhook Stripe événement non traité : ' . $event->type);
		}

		return new JsonResponse(['status' => 'ok']);
	}

	/**
	 * Traite l'événement checkout.session.completed
	 */
	private function handleCheckoutCompleted(object $session, PaymentRepository $paymentRepo): void
	{
		$payment = $paymentRepo->findOneBy(['stripeSessionId' => $session->id]);

		if (!$payment) {
			$this->logger->warning('Webhook : payment non trouvé pour session ' . $session->id);
			return;
		}

		if ($payment->getStatut() === Payment::STATUT_REUSSI) {
			return; // Déjà traité
		}

		$payment->setStatut(Payment::STATUT_REUSSI);
		$payment->setPaidAt(new \DateTime());
		$payment->setStripePaymentIntentId($session->payment_intent);

		// Mettre à jour la réservation associée
		if ($payment->getReservation()) {
			$reservation = $payment->getReservation();
			$reservation->setPaiementStatut(Reservation::PAIEMENT_COMPLET);

			// Auto-confirmer la réservation si elle était en attente
			if ($reservation->getStatut() === Reservation::STATUT_EN_ATTENTE) {
				$reservation->setStatut(Reservation::STATUT_CONFIRMEE);
			}
		}

		$this->em->flush();

		// Envoyer email de confirmation
		try {
			$this->mailerService->sendPaiementConfirmationEmail($payment);

			// Si c'est une réservation, envoyer aussi la confirmation de réservation
			if ($payment->getReservation()) {
				$this->mailerService->sendConfirmationReservation($payment->getReservation());
			}
		} catch (\Exception $e) {
			$this->logger->error('Erreur envoi email confirmation paiement : ' . $e->getMessage());
		}
	}

	/**
	 * Traite l'événement payment_intent.payment_failed
	 */
	private function handlePaymentFailed(object $paymentIntent, PaymentRepository $paymentRepo): void
	{
		$payment = $paymentRepo->findOneBy(['stripePaymentIntentId' => $paymentIntent->id]);

		if ($payment) {
			$payment->setStatut(Payment::STATUT_ECHOUE);
			$this->em->flush();
		}
	}

	// =========================================================================
	// ADMIN — Création de paiements et remboursements
	// =========================================================================

	/**
	 * Admin : créer un paiement (loyer, caution, pénalité, etc.) pour un locataire
	 */
	#[Route('/admin/paiement/creer', name: 'admin_payment_create', methods: ['POST'])]
	#[IsGranted('ROLE_ADMIN')]
	public function adminCreatePayment(Request $request): JsonResponse
	{
		if ($request->headers->get('X-Requested-With') !== 'XMLHttpRequest') {
			return new JsonResponse(['error' => 'Accès interdit'], 403);
		}
		$data = json_decode($request->getContent(), true);

		// Validation basique
		$requiredFields = ['user_id', 'type', 'montant'];
		foreach ($requiredFields as $field) {
			if (empty($data[$field])) {
				return new JsonResponse(['error' => "Champ requis manquant : $field"], 400);
			}
		}

		$user = $this->em->getRepository(\App\Entity\User::class)->find($data['user_id']);
		if (!$user) {
			return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
		}

		$payment = new Payment();
		$payment->setUser($user);
		$payment->setType($data['type']);
		$payment->setMontant($data['montant']);
		$payment->setDescription($data['description'] ?? null);

		if (!empty($data['reservation_id'])) {
			$reservation = $this->em->getRepository(Reservation::class)->find($data['reservation_id']);
			if ($reservation) {
				$payment->setReservation($reservation);
				$payment->setAppartement($reservation->getAppartement());
			}
		}

		if (!empty($data['appartement_id'])) {
			$appartement = $this->em->getRepository(\App\Entity\Appartement::class)->find($data['appartement_id']);
			if ($appartement) {
				$payment->setAppartement($appartement);
			}
		}

		if (!empty($data['date_echeance'])) {
			$payment->setDateEcheance(new \DateTime($data['date_echeance']));
		}

		$this->em->persist($payment);
		$this->em->flush();

		// Envoyer un email de notification d'échéance au client
		try {
			$this->mailerService->sendPaiementEcheanceEmail($payment);
		} catch (\Exception $e) {
			$this->logger->error('Erreur envoi email échéance : ' . $e->getMessage());
		}

		return new JsonResponse([
			'id'      => $payment->getId(),
			'message' => 'Paiement créé avec succès',
		], 201);
	}

	/**
	 * Admin : rembourser un paiement
	 */
	#[Route('/admin/paiement/{id}/rembourser', name: 'admin_payment_refund', methods: ['POST'])]
	#[IsGranted('ROLE_ADMIN')]
	public function adminRefundPayment(int $id, PaymentRepository $paymentRepo, Request $request): JsonResponse
	{
		if ($request->headers->get('X-Requested-With') !== 'XMLHttpRequest') {
			return new JsonResponse(['error' => 'Accès interdit'], 403);
		}
		$payment = $paymentRepo->find($id);

		if (!$payment) {
			return new JsonResponse(['error' => 'Paiement non trouvé'], 404);
		}

		if ($payment->getStatut() !== Payment::STATUT_REUSSI) {
			return new JsonResponse(['error' => 'Seuls les paiements réussis peuvent être remboursés'], 400);
		}

		if (!$payment->getStripePaymentIntentId()) {
			return new JsonResponse(['error' => 'Pas de PaymentIntent Stripe associé'], 400);
		}

		try {
			$this->stripeService->refundPayment($payment->getStripePaymentIntentId());
			$payment->setStatut(Payment::STATUT_REMBOURSE);
			$this->em->flush();

			return new JsonResponse(['message' => 'Remboursement effectué']);
		} catch (\Exception $e) {
			$this->logger->error('Erreur remboursement Stripe : ' . $e->getMessage());
			return new JsonResponse(['error' => 'Erreur lors du remboursement : ' . $e->getMessage()], 500);
		}
	}
}
