<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Disponibilite;

use App\Repository\AppartementRepository;
use App\Repository\ReservationRepository;
use App\Repository\TemoignageRepository;
use App\Repository\DisponibiliteRepository;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;


#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{
	/**
	 * Dashboard admin
	 */
	#[Route('/', name: 'dashboard')]
	public function dashboard(
			AppartementRepository $appartementRepo,
			ReservationRepository $reservationRepo,
			TemoignageRepository $temoignageRepo
	): Response {
    $reservations = $reservationRepo->findRecentes(10);

    // Compteurs
    $totalAppartements = count($appartementRepo->findAllActifs());
    $totalReservations = $reservationRepo->count([]);
    $reservationsEnAttente = $reservationRepo->count(['statut' => Reservation::STATUT_EN_ATTENTE]);
    $totalTemoignages = count($temoignageRepo->findActifs());

    return $this->render('admin/dashboard.html.twig', [
      'reservations' => $reservations,
      'totalAppartements' => $totalAppartements,
      'totalReservations' => $totalReservations,
      'reservationsEnAttente' => $reservationsEnAttente,
      'totalTemoignages' => $totalTemoignages,
    ]);
	}

	/**
	 * Liste des réservations
	 */
	#[Route('/reservations', name: 'reservations')]
	public function reservations(ReservationRepository $reservationRepo): Response
	{
    return $this->render('admin/reservations.html.twig', [
        'reservations' => $reservationRepo->findRecentes(50),
    ]);
	}

	/**
	 * Changer le statut d'une réservation
	 */
	#[Route('/reservation/{id}/statut/{statut}', name: 'reservation_statut', methods: ['POST'])]
	public function changeStatut(
			int $id,
			string $statut,
			ReservationRepository $reservationRepo,
			EntityManagerInterface $em,
			Request $request
	): Response {
    $reservation = $reservationRepo->find($id);

    if (!$reservation) {
        throw $this->createNotFoundException('Réservation non trouvée.');
    }

    // Vérification CSRF
    if ($this->isCsrfTokenValid('statut_' . $id, $request->request->get('_token'))) {
      $reservation->setStatut($statut);
      $em->flush();

      $this->addFlash('success', sprintf(
        'Réservation #%d de %s %s → statut mis à jour : %s',
        $reservation->getId(),
        $reservation->getPrenom(),
        $reservation->getNom(),
        $statut
      ));
    }

    return $this->redirectToRoute('admin_reservations');
	}


	/**
	 * Page de gestion du calendrier
	 */
	#[Route('/calendrier', name: 'calendrier')]
	public function calendrier(AppartementRepository $appartementRepo): Response
	{
		return $this->render('admin/calendrier.html.twig', [
			'appartements' => $appartementRepo->findAllActifs(),
		]);
	}

	/**
	 * API Admin : récupérer les disponibilités
	 */
	#[Route('/api/disponibilites/{appartementId}', name: 'api_admin_disponibilites', methods: ['GET'])]
	public function getAdminDisponibilites(
			int $appartementId,
			Request $request,
			DisponibiliteRepository $disponibiliteRepo
	): JsonResponse {
		$start = new \DateTime($request->query->get('start', 'first day of this month'));
		$end = new \DateTime($request->query->get('end', 'last day of +2 months'));

		$disponibilites = $disponibiliteRepo->findByAppartementAndPeriode($appartementId, $start, $end);

		$events = [];
		foreach ($disponibilites as $dispo) {
			$events[] = [
					'id' => $dispo->getId(),
					'title' => $dispo->getStatutLabel() . ($dispo->getNote() ? ' - ' . $dispo->getNote() : ''),
					'start' => $dispo->getDateDebut()->format('Y-m-d'),
					'end' => $dispo->getDateFin()->modify('+1 day')->format('Y-m-d'),
					'color' => $dispo->getCouleur(),
					'allDay' => true,
			];
		}

		return new JsonResponse($events);
	}

	/**
	 * API Admin : créer/modifier une disponibilité
	 */
	#[Route('/api/disponibilite', name: 'api_admin_disponibilite_create', methods: ['POST'])]
	public function createDisponibilite(
		Request $request,
		AppartementRepository $appartementRepo,
		EntityManagerInterface $em
	): JsonResponse {
		$data = json_decode($request->getContent(), true);

		$appartement = $appartementRepo->find($data['appartement_id'] ?? 0);
		if (!$appartement) {
			return new JsonResponse(['error' => 'Appartement non trouvé'], 404);
		}

		$dispo = new Disponibilite();
		$dispo->setAppartement($appartement);
		$dispo->setDateDebut(new \DateTime($data['date_debut']));
		$dispo->setDateFin(new \DateTime($data['date_fin']));
		$dispo->setStatut($data['statut'] ?? Disponibilite::STATUT_BLOQUE);
		$dispo->setNote($data['note'] ?? null);

		$em->persist($dispo);
		$em->flush();

		return new JsonResponse([
			'id' => $dispo->getId(),
			'message' => 'Disponibilité créée',
		], 201);
	}

	/**
	 * API Admin : supprimer une disponibilité
	 */
	#[Route('/api/disponibilite/{id}', name: 'api_admin_disponibilite_delete', methods: ['DELETE'])]
	public function deleteDisponibilite(
		int $id,
		DisponibiliteRepository $disponibiliteRepo,
		EntityManagerInterface $em
	): JsonResponse {
		$dispo = $disponibiliteRepo->find($id);
		if (!$dispo) {
			return new JsonResponse(['error' => 'Non trouvé'], 404);
		}

		$em->remove($dispo);
		$em->flush();

		return new JsonResponse(['message' => 'Supprimé']);
	}
}
