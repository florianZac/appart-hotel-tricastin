<?php

namespace App\Service;

use App\Entity\Reservation;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

/**
 * Génère des factures PDF pour les réservations.
 * Utilisable par l'admin et les clients.
 */
class FactureService
{
    public function __construct(
        private readonly Environment $twig,
    ) {}

    /**
     * Génère le PDF d'une facture et retourne le contenu binaire.
     */
    public function genererFacturePdf(Reservation $reservation): string
    {
        // Générer le numéro de facture s'il n'existe pas
        $numeroFacture = $reservation->getNumeroFacture();
        if (!$numeroFacture) {
            $numeroFacture = sprintf(
                'FAC-%d-%04d',
                $reservation->getCreatedAt()->format('Y'),
                $reservation->getId()
            );
        }

        // Calculer les détails
        $nbNuits      = $reservation->getNombreNuits();
        $montantTotal = (float) ($reservation->getMontantTotal() ?? 0);
        $prixNuit     = $nbNuits > 0 ? round($montantTotal / $nbNuits, 2) : 0;
        $totalPaye    = $reservation->getTotalPaye();
        $soldeRestant = $reservation->getSoldeRestant();

        // Paiements associés
        $paiements = [];
        foreach ($reservation->getPayments() as $payment) {
            $paiements[] = [
                'date'    => $payment->getPaidAt() ?? $payment->getCreatedAt(),
                'type'    => $payment->getTypeLabel(),
                'montant' => (float) $payment->getMontant(),
                'statut'  => $payment->getStatutLabel(),
            ];
        }

        // Rendu du template HTML
        $html = $this->twig->render('pdf/facture.html.twig', [
            'reservation'    => $reservation,
            'numeroFacture'  => $numeroFacture,
            'nbNuits'        => $nbNuits,
            'prixNuit'       => $prixNuit,
            'montantTotal'   => $montantTotal,
            'totalPaye'      => $totalPaye,
            'soldeRestant'   => $soldeRestant,
            'paiements'      => $paiements,
            'dateEmission'   => new \DateTime(),
        ]);

        // Génération du PDF avec DomPDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');
        $options->set('dpi', 150);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Retourne le nom de fichier suggéré pour le téléchargement.
     */
    public function getNomFichier(Reservation $reservation): string
    {
        $numero = $reservation->getNumeroFacture()
            ?? sprintf('FAC-%d-%04d', $reservation->getCreatedAt()->format('Y'), $reservation->getId());

        return sprintf('facture_%s_%s_%s.pdf',
            $numero,
            $reservation->getNom(),
            $reservation->getPrenom()
        );
    }
}
