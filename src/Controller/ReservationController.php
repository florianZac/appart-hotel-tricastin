<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\AppartementRepository;
use App\Repository\LocalisationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReservationController extends AbstractController
{
    #[Route('/reserver', name: 'app_reservation')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($reservation);
            $em->flush();

            $this->addFlash('success', 'Votre demande de réservation a bien été envoyée ! Nous vous recontacterons dans les plus brefs délais.');

            return $this->redirectToRoute('app_reservation');
        }

        return $this->render('reservation/index.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Route AJAX : retourne les appartements d'une localisation en JSON
     */
    #[Route('/api/appartements-par-localisation/{id}', name: 'api_appartements_par_localisation', methods: ['GET'])]
    public function appartementsParLocalisation(
        int $id,
        AppartementRepository $appartementRepository,
        LocalisationRepository $localisationRepository
    ): JsonResponse {
        $localisation = $localisationRepository->find($id);

        if (!$localisation) {
            return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        $appartements = $appartementRepository->findBy(
            ['localisation' => $localisation, 'actif' => true],
            ['ordre' => 'ASC']
        );

        $data = [];
        foreach ($appartements as $appart) {
            $data[] = [
                'id' => $appart->getId(),
                'label' => sprintf('%s — %s · %dm² · %d-%d pers. · %s€/nuit',
                    $appart->getNom(),
                    $appart->getType(),
                    $appart->getSurface(),
                    $appart->getCapaciteMin(),
                    $appart->getCapaciteMax(),
                    $appart->getPrixParNuit()
                ),
            ];
        }

        return new JsonResponse($data);
    }
}