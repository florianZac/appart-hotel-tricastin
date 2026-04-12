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
   * API publique : retourne les événements du calendrier pour un appartement.
   * Combine les réservations confirmées + les disponibilités manuelles (admin).
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

    $startParam = $request->query->get('start');
    $endParam = $request->query->get('end');
    $start = $startParam ? new \DateTime(substr($startParam, 0, 10)) : new \DateTime('first day of this month');
    $end = $endParam ? new \DateTime(substr($endParam, 0, 10)) : new \DateTime('last day of +3 months');

    $events = [];

    // ── 1. Réservations confirmées en rouge
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

      // Jour de nettoyage après le départ (gris)
      $jourMaintenance = clone $reservation->getDateDepart();
      $events[] = [
        'id' => 'maint-' . $reservation->getId(),
        'title' => 'Nettoyage',
        'start' => $jourMaintenance->format('Y-m-d'),
        'end' => $jourMaintenance->modify('+1 day')->format('Y-m-d'),
        'color' => '#95a5a6',
        'allDay' => true,
        'extendedProps' => ['type' => 'nettoyage'],
      ];
    }

    // ── 2. Disponibilités manuelles (admin) ─────────────────
    // Affiche chaque statut avec sa couleur et son label réel
    $disponibilites = $disponibiliteRepo->findByAppartementAndPeriode($appartementId, $start, $end);

    foreach ($disponibilites as $dispo) {
      // Mapping statut → couleur + titre pour le calendrier public
      $color = $dispo->getCouleur();
      $title = match ($dispo->getStatut()) {
        Disponibilite::STATUT_DISPONIBLE => 'Disponible',
        Disponibilite::STATUT_RESERVE    => 'Réservé',
        Disponibilite::STATUT_NETTOYAGE  => 'Nettoyage',
        Disponibilite::STATUT_BLOQUE     => 'Indisponible',
        default                          => 'Indisponible',
      };

      $events[] = [
        'id' => 'bloc-' . $dispo->getId(),
        'title' => $title,
        'start' => $dispo->getDateDebut()->format('Y-m-d'),
        'end' => $dispo->getDateFin()->modify('+1 day')->format('Y-m-d'),
        'color' => $color,
        'allDay' => true,
        'extendedProps' => [
          'type' => $dispo->getStatut(),
          'note' => $dispo->getNote(),
        ],
      ];
    }

    return new JsonResponse($events);
  }
}
