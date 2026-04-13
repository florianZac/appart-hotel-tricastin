<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use App\Service\FactureService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FactureController extends AbstractController
{
    public function __construct(
        private readonly FactureService       $factureService,
        private readonly ReservationRepository $reservationRepo,
        private readonly EntityManagerInterface $em,
    ) {}

    // ── Admin : télécharger la facture d'une réservation ────
    #[Route('/admin/reservation/{id}/facture', name: 'admin_facture_pdf', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminFacture(int $id): Response
    {
        $reservation = $this->reservationRepo->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée.');
        }

        // Générer et sauvegarder le numéro de facture si absent
        $this->genererNumeroSiAbsent($reservation);

        $pdf      = $this->factureService->genererFacturePdf($reservation);
        $filename = $this->factureService->getNomFichier($reservation);

        return new Response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    // ── Client : télécharger SA facture ─────────────────────
    #[Route('/espace-client/reservation/{id}/facture', name: 'client_facture_pdf', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function clientFacture(int $id): Response
    {
        $user        = $this->getUser();
        $reservation = $this->reservationRepo->find($id);

        if (!$reservation || $reservation->getUser() !== $user) {
            throw $this->createNotFoundException('Réservation non trouvée.');
        }

        // Seules les réservations confirmées ou terminées ont une facture
        if (!in_array($reservation->getStatut(), ['confirmee', 'terminee'])) {
            $this->addFlash('warning', 'La facture n\'est disponible que pour les réservations confirmées.');
            return $this->redirectToRoute('client_reservation_detail', ['id' => $id]);
        }

        $this->genererNumeroSiAbsent($reservation);

        $pdf      = $this->factureService->genererFacturePdf($reservation);
        $filename = $this->factureService->getNomFichier($reservation);

        return new Response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    // ── Génère et persiste le numéro de facture ─────────────
    private function genererNumeroSiAbsent(object $reservation): void
    {
        if (!$reservation->getNumeroFacture()) {
            $numero = sprintf(
                'FAC-%d-%04d',
                $reservation->getCreatedAt()->format('Y'),
                $reservation->getId()
            );
            $reservation->setNumeroFacture($numero);
            $this->em->flush();
        }
    }
}
