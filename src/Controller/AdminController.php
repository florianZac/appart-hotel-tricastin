<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\AppartementRepository;
use App\Repository\ReservationRepository;
use App\Repository\TemoignageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
}
