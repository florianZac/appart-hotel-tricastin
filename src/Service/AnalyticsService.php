<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service fournissant les données analytiques pour le dashboard admin.
 * Alimente les graphiques Chart.js.
 */
class AnalyticsService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Revenus mensuels de l'année en cours.
     * @return array<int, float> — index 0-11 = janvier-décembre
     */
    public function getRevenusParMois(int $annee): array
    {
        $result = $this->em->createQuery(
            'SELECT MONTH(p.paidAt) AS mois, SUM(p.montant) AS total
             FROM App\Entity\Payment p
             WHERE p.statut = :statut
             AND YEAR(p.paidAt) = :annee
             GROUP BY mois
             ORDER BY mois ASC'
        )
        ->setParameter('statut', Payment::STATUT_REUSSI)
        ->setParameter('annee', $annee)
        ->getResult();

        $revenus = array_fill(0, 12, 0.0);
        foreach ($result as $row) {
            $revenus[(int) $row['mois'] - 1] = round((float) $row['total'], 2);
        }

        return $revenus;
    }

    /**
     * Nombre de réservations par statut.
     * @return array<string, int>
     */
    public function getReservationsParStatut(): array
    {
        $result = $this->em->createQuery(
            'SELECT r.statut, COUNT(r.id) AS total
             FROM App\Entity\Reservation r
             GROUP BY r.statut'
        )->getResult();

        $data = [
            'en_attente' => 0,
            'confirmee'  => 0,
            'annulee'    => 0,
            'terminee'   => 0,
        ];

        foreach ($result as $row) {
            $data[$row['statut']] = (int) $row['total'];
        }

        return $data;
    }

    /**
     * Nombre de réservations par mois pour l'année donnée.
     * @return array<int, int> — index 0-11
     */
    public function getReservationsParMois(int $annee): array
    {
        $result = $this->em->createQuery(
            'SELECT MONTH(r.createdAt) AS mois, COUNT(r.id) AS total
             FROM App\Entity\Reservation r
             WHERE YEAR(r.createdAt) = :annee
             GROUP BY mois
             ORDER BY mois ASC'
        )
        ->setParameter('annee', $annee)
        ->getResult();

        $data = array_fill(0, 12, 0);
        foreach ($result as $row) {
            $data[(int) $row['mois'] - 1] = (int) $row['total'];
        }

        return $data;
    }

    /**
     * Top 5 appartements par chiffre d'affaires.
     * @return array<int, array{nom: string, total: float}>
     */
    public function getTopAppartements(int $limit = 5): array
    {
        $result = $this->em->createQuery(
            'SELECT a.nom, SUM(r.montantTotal) AS total
             FROM App\Entity\Reservation r
             JOIN r.appartement a
             WHERE r.statut IN (:statuts)
             GROUP BY a.id, a.nom
             ORDER BY total DESC'
        )
        ->setParameter('statuts', [
            Reservation::STATUT_CONFIRMEE,
            Reservation::STATUT_TERMINEE,
        ])
        ->setMaxResults($limit)
        ->getResult();

        return array_map(fn($row) => [
            'nom'   => $row['nom'],
            'total' => round((float) $row['total'], 2),
        ], $result);
    }

    /**
     * Taux d'occupation mensuel (toutes les réservations confirmées/terminées).
     * Basé sur le nombre total de jours réservés / jours disponibles × nb appartements.
     */
    public function getTauxOccupationParMois(int $annee, int $nbAppartements): array
    {
        $taux = array_fill(0, 12, 0.0);

        if ($nbAppartements <= 0) {
            return $taux;
        }

        $reservations = $this->em->createQuery(
            'SELECT r.dateArrivee, r.dateDepart
             FROM App\Entity\Reservation r
             WHERE r.statut IN (:statuts)
             AND YEAR(r.dateArrivee) = :annee OR YEAR(r.dateDepart) = :annee'
        )
        ->setParameter('statuts', [
            Reservation::STATUT_CONFIRMEE,
            Reservation::STATUT_TERMINEE,
        ])
        ->setParameter('annee', $annee)
        ->getResult();

        // Compteur de jours occupés par mois
        $joursParMois = array_fill(0, 12, 0);

        foreach ($reservations as $resa) {
            $debut = $resa['dateArrivee'];
            $fin   = $resa['dateDepart'];
            if (!$debut || !$fin) continue;

            $current = clone $debut;
            while ($current < $fin) {
                $m = (int) $current->format('n') - 1;
                $y = (int) $current->format('Y');
                if ($y === $annee && $m >= 0 && $m < 12) {
                    $joursParMois[$m]++;
                }
                $current->modify('+1 day');
            }
        }

        for ($m = 0; $m < 12; $m++) {
            $joursDansMois = (int) (new \DateTime("$annee-" . ($m + 1) . "-01"))->format('t');
            $joursDisponibles = $joursDansMois * $nbAppartements;
            $taux[$m] = $joursDisponibles > 0
                ? round(($joursParMois[$m] / $joursDisponibles) * 100, 1)
                : 0;
        }

        return $taux;
    }
}
