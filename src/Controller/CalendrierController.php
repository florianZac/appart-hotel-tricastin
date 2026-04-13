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
   * Les plages "disponible" sont automatiquement découpées pour ne jamais
   * chevaucher les réservations, nettoyages, ni les blocages admin.
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

    // ── Collecter TOUTES les périodes non-disponibles ──
    $periodesOccupees = [];

    // 1. Réservations confirmées
    $reservations = $reservationRepo->findConfirmeesParAppartement($appartementId, $start, $end);

    foreach ($reservations as $reservation) {
      $resStart = clone $reservation->getDateArrivee();
      $resEnd   = clone $reservation->getDateDepart();
      $nettoyageFin = (clone $resEnd)->modify('+1 day');

      // Événement réservé (rouge)
      $events[] = [
        'id' => 'res-' . $reservation->getId(),
        'title' => 'Réservé',
        'start' => $resStart->format('Y-m-d'),
        'end' => $resEnd->format('Y-m-d'),
        'color' => '#c0392b',
        'allDay' => true,
        'extendedProps' => ['type' => 'reserve'],
      ];

      // Événement nettoyage (gris)
      $events[] = [
        'id' => 'maint-' . $reservation->getId(),
        'title' => 'Nettoyage',
        'start' => $resEnd->format('Y-m-d'),
        'end' => $nettoyageFin->format('Y-m-d'),
        'color' => '#95a5a6',
        'allDay' => true,
        'extendedProps' => ['type' => 'nettoyage'],
      ];

      // Période occupée : du début réservation à la fin du nettoyage (exclusif)
      $periodesOccupees[] = [
        'start' => clone $resStart,
        'end'   => clone $nettoyageFin,
      ];
    }

    // 2. Disponibilités admin NON-disponibles (bloqué, réservé, nettoyage)
    $disponibilites = $disponibiliteRepo->findByAppartementAndPeriode($appartementId, $start, $end);

    foreach ($disponibilites as $dispo) {
      if ($dispo->getStatut() !== Disponibilite::STATUT_DISPONIBLE) {
        $color = $dispo->getCouleur();
        $title = match ($dispo->getStatut()) {
          Disponibilite::STATUT_RESERVE    => 'Réservé',
          Disponibilite::STATUT_NETTOYAGE  => 'Nettoyage',
          Disponibilite::STATUT_BLOQUE     => 'Indisponible',
          default                          => 'Indisponible',
        };

        $dispoStart = clone $dispo->getDateDebut();
        $dispoEnd   = clone $dispo->getDateFin();

        $events[] = [
          'id' => 'bloc-' . $dispo->getId(),
          'title' => $title,
          'start' => $dispoStart->format('Y-m-d'),
          'end' => (clone $dispoEnd)->modify('+1 day')->format('Y-m-d'),
          'color' => $color,
          'allDay' => true,
          'extendedProps' => [
            'type' => $dispo->getStatut(),
            'note' => $dispo->getNote(),
          ],
        ];

        // Ajouter aux périodes occupées (fin exclusive = dateFin + 1 jour)
        $periodesOccupees[] = [
          'start' => clone $dispoStart,
          'end'   => (clone $dispoEnd)->modify('+1 day'),
        ];
      }
    }

    // Trier les périodes occupées par date de début
    usort($periodesOccupees, fn($a, $b) => $a['start'] <=> $b['start']);

    // ── 3. Plages "Disponible" — découpées autour de tout ce qui est occupé ──
    foreach ($disponibilites as $dispo) {
      if ($dispo->getStatut() !== Disponibilite::STATUT_DISPONIBLE) {
        continue;
      }

      $dispoStart = clone $dispo->getDateDebut();
      $dispoEnd   = clone $dispo->getDateFin();

      if (empty($periodesOccupees)) {
        // Aucune période occupée → afficher tel quel
        $events[] = [
          'id' => 'bloc-' . $dispo->getId(),
          'title' => 'Disponible',
          'start' => $dispoStart->format('Y-m-d'),
          'end' => (clone $dispoEnd)->modify('+1 day')->format('Y-m-d'),
          'color' => '#28a745',
          'allDay' => true,
          'extendedProps' => [
            'type' => 'disponible',
            'note' => $dispo->getNote(),
          ],
        ];
        continue;
      }

      // Découper la plage disponible en retirant les périodes occupées
      $segments = $this->decouperPlage($dispoStart, $dispoEnd, $periodesOccupees);

      foreach ($segments as $i => $seg) {
        $segEnd = (clone $seg['end'])->modify('+1 day');
        $events[] = [
          'id' => 'bloc-' . $dispo->getId() . '-' . $i,
          'title' => 'Disponible',
          'start' => $seg['start']->format('Y-m-d'),
          'end' => $segEnd->format('Y-m-d'),
          'color' => '#28a745',
          'allDay' => true,
          'extendedProps' => [
            'type' => 'disponible',
            'note' => $dispo->getNote(),
          ],
        ];
      }
    }

    return new JsonResponse($events);
  }

  /**
   * Découpe une plage [plageStart, plageEnd] en retirant les périodes occupées.
   * Retourne un tableau de segments [{start, end}, ...] sans chevauchement.
   */
  private function decouperPlage(
    \DateTimeInterface $plageStart,
    \DateTimeInterface $plageEnd,
    array $periodesOccupees
  ): array {
    $segments = [];
    $cursor = clone $plageStart;

    foreach ($periodesOccupees as $occ) {
      $occStart = $occ['start'];
      $occEnd   = $occ['end']; // jour exclusif (lendemain du dernier jour occupé)

      // Si la période occupée est entièrement avant le curseur, on l'ignore
      if ($occEnd <= $cursor) {
        continue;
      }

      // Si la période occupée est entièrement après la fin de la plage, on arrête
      if ($occStart > $plageEnd) {
        break;
      }

      // S'il y a un segment libre avant cette occupation
      if ($cursor < $occStart) {
        $segEnd = (clone $occStart)->modify('-1 day');
        if ($segEnd >= $cursor) {
          $segments[] = [
            'start' => clone $cursor,
            'end' => $segEnd,
          ];
        }
      }

      // Avancer le curseur après la fin de l'occupation
      if ($occEnd > $cursor) {
        $cursor = clone $occEnd;
      }
    }

    // Segment restant après la dernière occupation
    if ($cursor <= $plageEnd) {
      $segments[] = [
        'start' => clone $cursor,
        'end' => clone $plageEnd,
      ];
    }

    return $segments;
  }
}
