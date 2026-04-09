<?php

namespace App\Controller;

use App\Repository\PaymentRepository;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @author      Florian Aizac
 * @created     09/04/2026
 * @description Contrôleur de l'espace client — tableau de bord, historique des réservations et paiements
 */
#[Route('/espace-client', name: 'client_')]
#[IsGranted('ROLE_USER')]
class ClientController extends AbstractController
{
	/**
	 * Tableau de bord client : résumé réservations + paiements récents
	 */
	#[Route('/', name: 'dashboard')]
	public function dashboard(
		ReservationRepository $reservationRepo,
		PaymentRepository $paymentRepo
	): Response {
		$user = $this->getUser();

		$reservations = $reservationRepo->findByUser($user, 5);
		$payments = $paymentRepo->findByUser($user, 5);

		// Statistiques rapides
		$totalReservations = count($reservationRepo->findByUser($user, 999));
		$reservationsActives = array_filter(
			$reservationRepo->findByUser($user, 999),
			fn($r) => in_array($r->getStatut(), ['en_attente', 'confirmee'])
		);
		$paiementsEnAttente = array_filter(
			$paymentRepo->findByUser($user, 999),
			fn($p) => $p->getStatut() === 'en_attente'
		);

		return $this->render('client/dashboard.html.twig', [
			'reservations'         => $reservations,
			'payments'             => $payments,
			'totalReservations'    => $totalReservations,
			'reservationsActives'  => count($reservationsActives),
			'paiementsEnAttente'   => count($paiementsEnAttente),
		]);
	}

	/**
	 * Historique complet des réservations du client
	 */
	#[Route('/reservations', name: 'reservations')]
	public function reservations(ReservationRepository $reservationRepo): Response
	{
		$user = $this->getUser();
		$reservations = $reservationRepo->findByUser($user);

		return $this->render('client/reservations.html.twig', [
			'reservations' => $reservations,
		]);
	}

	/**
	 * Détail d'une réservation (avec paiements associés)
	 */
	#[Route('/reservation/{id}', name: 'reservation_detail')]
	public function reservationDetail(int $id, ReservationRepository $reservationRepo): Response
	{
		$user = $this->getUser();
		$reservation = $reservationRepo->find($id);

		if (!$reservation || $reservation->getUser() !== $user) {
			throw $this->createNotFoundException('Réservation non trouvée.');
		}

		return $this->render('client/reservation_detail.html.twig', [
			'reservation' => $reservation,
		]);
	}

	/**
	 * Historique complet des paiements du client
	 */
	#[Route('/paiements', name: 'paiements')]
	public function paiements(PaymentRepository $paymentRepo): Response
	{
		$user = $this->getUser();
		$payments = $paymentRepo->findByUser($user);

		return $this->render('client/paiements.html.twig', [
			'payments' => $payments,
		]);
	}
}
