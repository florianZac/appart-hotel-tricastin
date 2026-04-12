<?php

namespace App\Service;

use App\Entity\Appartement;
use App\Entity\Frais;
use App\Repository\FraisRepository;
use App\Repository\ReservationRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Génère un fichier CSV comptable avec :
 *  - les réservations mois par mois (client, facture, durée, prix…)
 *  - le taux d'occupation par mois
 *  - le récapitulatif des frais annuels
 *  - le bilan financier global
 */
class ComptabiliteExporter
{
    private const MOIS_FR = [
        1  => 'Janvier',   2  => 'Février',  3  => 'Mars',
        4  => 'Avril',     5  => 'Mai',      6  => 'Juin',
        7  => 'Juillet',   8  => 'Août',     9  => 'Septembre',
        10 => 'Octobre',   11 => 'Novembre', 12 => 'Décembre',
    ];

    public function __construct(
        private readonly ReservationRepository $reservationRepo,
        private readonly FraisRepository       $fraisRepo,
    ) {}

    /**
     * Génère la réponse HTTP StreamedResponse contenant le CSV.
     */
    public function exportCsv(int $annee, ?Appartement $appartement = null): StreamedResponse
    {
        $reservations = $this->getReservationsParMois($annee, $appartement);
        $fraisTotaux  = $this->fraisRepo->getTotauxParMois($annee, $appartement);
        $fraisDetail  = $this->fraisRepo->findByAnnee($annee, $appartement);

        $nomAppart = $appartement?->getNom() ?? 'tous-appartements';
        $filename  = sprintf('comptabilite_%s_%d.csv', $this->slugify($nomAppart), $annee);

        $response = new StreamedResponse(function () use ($annee, $appartement, $reservations, $fraisTotaux, $fraisDetail) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 pour Excel
            fwrite($handle, "\xEF\xBB\xBF");

            // ── EN-TÊTE GÉNÉRAL ─────────────────────────────────
            $this->writeSectionHeader($handle, sprintf(
                'BILAN COMPTABLE %d — %s',
                $annee,
                $appartement?->getNom() ?? 'Tous les appartements'
            ));
            fputcsv($handle, [], ';');

            $totalRevenusAnnee     = 0.0;
            $totalJoursOccupes     = 0;
            $totalJoursDisponibles = 0;

            // ── RÉSERVATIONS MOIS PAR MOIS ──────────────────────
            for ($mois = 1; $mois <= 12; $mois++) {
                $joursDispoMois = $this->joursDisponiblesDansMois($annee, $mois);
                $totalJoursDisponibles += $joursDispoMois;

                $this->writeSectionHeader($handle, self::MOIS_FR[$mois] . ' ' . $annee);

                // En-têtes colonnes
                fputcsv($handle, [
                    'Client',
                    'N° Facture',
                    'Appartement',
                    'Date début',
                    'Date fin',
                    'Nb jours',
                    'Prix unitaire (€/nuit)',
                    'Total hébergement (€)',
                ], ';');

                $joursOccupesMois = 0;
                $totalMois        = 0.0;

                if (!empty($reservations[$mois])) {
                    foreach ($reservations[$mois] as $resa) {
                        $nbJours   = $resa['nbJours'];
                        $prixUnit  = $resa['prixUnitaire'];
                        $totalResa = $resa['totalHebergement'];

                        fputcsv($handle, [
                            $resa['client'],
                            $resa['numFacture'],
                            $resa['appartement'],
                            $resa['dateDebut'],
                            $resa['dateFin'],
                            $nbJours,
                            number_format($prixUnit, 2, ',', ' '),
                            number_format($totalResa, 2, ',', ' '),
                        ], ';');

                        $joursOccupesMois += $nbJours;
                        $totalMois        += $totalResa;
                    }
                } else {
                    fputcsv($handle, ['Aucune réservation ce mois'], ';');
                }

                // Sous-total du mois
                $tauxOccupation = $joursDispoMois > 0
                    ? round(($joursOccupesMois / $joursDispoMois) * 100, 1)
                    : 0;

                fputcsv($handle, [], ';');
                fputcsv($handle, [
                    'SOUS-TOTAL ' . strtoupper(self::MOIS_FR[$mois]),
                    '',
                    '',
                    '',
                    '',
                    $joursOccupesMois . ' jours',
                    'Taux occupation : ' . $tauxOccupation . ' %',
                    number_format($totalMois, 2, ',', ' ') . ' €',
                ], ';');
                fputcsv($handle, [], ';');

                $totalRevenusAnnee += $totalMois;
                $totalJoursOccupes += $joursOccupesMois;
            }

            // ── RÉCAPITULATIF TAUX D'OCCUPATION ANNUEL ──────────
            fputcsv($handle, [], ';');
            $this->writeSectionHeader($handle, 'RÉCAPITULATIF TAUX D\'OCCUPATION');
            fputcsv($handle, ['Mois', 'Jours occupés', 'Jours disponibles', 'Taux (%)'], ';');

            for ($mois = 1; $mois <= 12; $mois++) {
                $joursDispo   = $this->joursDisponiblesDansMois($annee, $mois);
                $joursOccupes = 0;

                if (!empty($reservations[$mois])) {
                    foreach ($reservations[$mois] as $resa) {
                        $joursOccupes += $resa['nbJours'];
                    }
                }

                $taux = $joursDispo > 0 ? round(($joursOccupes / $joursDispo) * 100, 1) : 0;

                fputcsv($handle, [
                    self::MOIS_FR[$mois],
                    $joursOccupes,
                    $joursDispo,
                    $taux . ' %',
                ], ';');
            }

            $tauxAnnuel = $totalJoursDisponibles > 0
                ? round(($totalJoursOccupes / $totalJoursDisponibles) * 100, 1)
                : 0;

            fputcsv($handle, [
                'TOTAL ANNUEL',
                $totalJoursOccupes,
                $totalJoursDisponibles,
                $tauxAnnuel . ' %',
            ], ';');

            // ── DÉTAIL DES FRAIS ────────────────────────────────
            fputcsv($handle, [], ';');
            $this->writeSectionHeader($handle, 'DÉTAIL DES FRAIS — ' . $annee);
            fputcsv($handle, [
                'Type',
                'Libellé',
                'Appartement',
                'Périodicité',
                'Mois',
                'Montant (€)',
            ], ';');

            $totalFraisAnnee = 0.0;

            foreach ($fraisDetail as $frais) {
                $montantEffectif = (float) $frais->getMontant();

                // Les frais mensuels comptent ×12
                if ($frais->getPeriodicite() === Frais::PERIODICITE_MENSUEL) {
                    $totalFraisAnnee += $montantEffectif * 12;
                } else {
                    $totalFraisAnnee += $montantEffectif;
                }

                fputcsv($handle, [
                    $frais->getTypeFraisLabel(),
                    $frais->getLibelle(),
                    $frais->getAppartement()?->getNom() ?? 'Global',
                    Frais::PERIODICITE_LABELS[$frais->getPeriodicite()] ?? $frais->getPeriodicite(),
                    $frais->getMois() ? self::MOIS_FR[$frais->getMois()] : '—',
                    number_format($montantEffectif, 2, ',', ' '),
                ], ';');
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, [
                'TOTAL FRAIS ANNUELS',
                '',
                '',
                '',
                '',
                number_format($totalFraisAnnee, 2, ',', ' ') . ' €',
            ], ';');

            // ── BILAN FINANCIER ─────────────────────────────────
            fputcsv($handle, [], ';');
            $this->writeSectionHeader($handle, 'BILAN FINANCIER — ' . $annee);

            $resultatNet = $totalRevenusAnnee - $totalFraisAnnee;

            fputcsv($handle, ['Total revenus hébergement', number_format($totalRevenusAnnee, 2, ',', ' ') . ' €'], ';');
            fputcsv($handle, ['Total frais annuels',       number_format($totalFraisAnnee, 2, ',', ' ') . ' €'], ';');
            fputcsv($handle, [], ';');
            fputcsv($handle, [
                'RÉSULTAT NET',
                number_format($resultatNet, 2, ',', ' ') . ' €',
            ], ';');

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));

        return $response;
    }

    // ── Méthodes privées ────────────────────────────────────────

    /**
     * Récupère les réservations de l'année, groupées par mois.
     *
     * @return array<int, array<int, array{
     *     client: string,
     *     numFacture: string,
     *     appartement: string,
     *     dateDebut: string,
     *     dateFin: string,
     *     nbJours: int,
     *     prixUnitaire: float,
     *     totalHebergement: float,
     * }>>
     */
    private function getReservationsParMois(int $annee, ?Appartement $appartement = null): array
    {
        $debut = new \DateTimeImmutable("$annee-01-01");
        $fin   = new \DateTimeImmutable("$annee-12-31");

        $qb = $this->reservationRepo->createQueryBuilder('r')
            ->leftJoin('r.appartement', 'a')
            ->leftJoin('r.client', 'c')      // Adapte selon ta relation User/Client
            ->where('r.dateDebut <= :fin')
            ->andWhere('r.dateFin >= :debut')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('r.dateDebut', 'ASC');

        if ($appartement !== null) {
            $qb->andWhere('r.appartement = :appart')
               ->setParameter('appart', $appartement);
        }

        /** @var \App\Entity\Reservation[] $reservations */
        $reservations = $qb->getQuery()->getResult();

        $parMois = [];

        foreach ($reservations as $resa) {
            // Clamp aux bornes de l'année
            $dateDebut = max($resa->getDateArrivee(), $debut);
            $dateFin   = min($resa->getDateDepart(), $fin);
            $nbJours   = (int) $dateDebut->diff($dateFin)->days;

            if ($nbJours <= 0) {
                continue;
            }

            $moisDebut = (int) $dateDebut->format('n');

            // Prix unitaire = prix total / nb jours
            // Adapte selon tes propriétés réelles (getPrixTotal(), getPrixNuit()…)
            $prixTotal     = (float) ($resa->getMontantTotal() ?? 0);
            $prixUnitaire  = $nbJours > 0 ? round($prixTotal / $nbJours, 2) : 0;

            $parMois[$moisDebut][] = [
                'client'           => $this->getNomClient($resa),
                'numFacture'       => $resa->getNumeroFacture() ?? 'N/A',
                'appartement'      => $resa->getAppartement()?->getNom() ?? '—',
                'dateDebut'        => $dateDebut->format('d/m/Y'),
                'dateFin'          => $dateFin->format('d/m/Y'),
                'nbJours'          => $nbJours,
                'prixUnitaire'     => $prixUnitaire,
                'totalHebergement' => $prixTotal,
            ];
        }

        return $parMois;
    }

    /**
     * Nombre de jours dans un mois donné.
     */
    private function joursDisponiblesDansMois(int $annee, int $mois): int
    {
        return (int) (new \DateTimeImmutable("$annee-$mois-01"))->format('t');
    }

    /**
     * Récupère le nom complet du client.
     * Adapte selon ta structure : User, Client, ou champ directement sur Reservation.
     */
    private function getNomClient(object $reservation): string
    {
        // Option A : relation ManyToOne vers User
        if (method_exists($reservation, 'getClient') && $reservation->getClient() !== null) {
            $client = $reservation->getClient();
            return trim(($client->getPrenom() ?? '') . ' ' . ($client->getNom() ?? '')) ?: 'Client inconnu';
        }

        // Option B : champs directement sur Reservation
        if (method_exists($reservation, 'getNomClient')) {
            return $reservation->getNomClient() ?: 'Client inconnu';
        }

        return 'Client inconnu';
    }

    private function writeSectionHeader($handle, string $titre): void
    {
        fputcsv($handle, ['═══════════════════════════════════════════════════'], ';');
        fputcsv($handle, [$titre], ';');
        fputcsv($handle, ['═══════════════════════════════════════════════════'], ';');
    }

    private function slugify(string $text): string
    {
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}
