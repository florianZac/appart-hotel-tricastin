<?php

namespace App\Controller;

use App\Entity\Disponibilite;
use App\Repository\AppartementRepository;
use App\Repository\DisponibiliteRepository;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CalendrierController extends AbstractController
{
  /**
   * API publique : retourne les événements du calendrier pour un appartement
   * Combine les réservations confirmées + les blocages manuels
   */
  #[Route('/api/disponibilites/{appartementId}', name: 'api_disponibilites', methods: ['GET'])]
  public function getDisponibilites(
      int $appartementId,
      Request $request,
      AppartementRepository $appartementRepo,
      ReservationRepository $reservationRepo,
      DisponibiliteRepository $disponibiliteRepo
  ): JsonResponse {
    $appartement = $appartementRepo->find($appartementId);
    if (!$appartement) {
      return new JsonResponse([], Response::HTTP_NOT_FOUND);
    }

    $start = new \DateTime($request->query->get('start', 'first day of this month'));
    $end = new \DateTime($request->query->get('end', 'last day of +3 months'));

    $events = [];

    // 1. Réservations confirmées → rouge + gris maintenance
    $reservations = $reservationRepo->findConfirmeesParAppartement($appartementId, $start, $end);

    foreach ($reservations as $reservation) {
      // Période réservée (rouge)
      $events[] = [
        'id' => 'res-' . $reservation->getId(),
        'title' => 'Réservé',
        'start' => $reservation->getDateArrivee()->format('Y-m-d'),
        'end' => $reservation->getDateDepart()->format('Y-m-d'),
        'color' => '#c0392b',
        'allDay' => true,
        'extendedProps' => ['type' => 'reserve'],
      ];

      // Jour de maintenance après le départ (gris)
      $jourMaintenance = clone $reservation->getDateDepart();
      $events[] = [
        'id' => 'maint-' . $reservation->getId(),
        'title' => 'Nettoyage',
        'start' => $jourMaintenance->format('Y-m-d'),
        'end' => $jourMaintenance->modify('+1 day')->format('Y-m-d'),
        'color' => '#95a5a6',
        'allDay' => true,
        'extendedProps' => ['type' => 'maintenance'],
      ];
    }

    // 2. Blocages manuels (admin) → orange
    $blocages = $disponibiliteRepo->findByAppartementAndPeriode($appartementId, $start, $end);
    foreach ($blocages as $blocage) {
      $events[] = [
        'id' => 'bloc-' . $blocage->getId(),
        'title' => $blocage->getNote() ?: 'Indisponible',
        'start' => $blocage->getDateDebut()->format('Y-m-d'),
        'end' => $blocage->getDateFin()->modify('+1 day')->format('Y-m-d'),
        'color' => '#e67e22',
        'allDay' => true,
        'extendedProps' => ['type' => 'bloque', 'note' => $blocage->getNote()],
      ];
    }

    return new JsonResponse($events);
  }
}