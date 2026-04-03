<?php

namespace App\Controller;

use App\Repository\AppartementRepository;
use App\Repository\LocalisationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AppartementController extends AbstractController
{
    /**
     * Page listing des 3 localisations
     */
    #[Route('/les-appartements', name: 'app_appartements')]
    public function index(LocalisationRepository $localisationRepository): Response
    {
        return $this->render('appartement/index.html.twig', [
            'localisations' => $localisationRepository->findAllWithAppartements(),
        ]);
    }

    /**
     * Appartements d'une localisation
     */
    #[Route('/les-appartements/{slug}', name: 'app_appartements_localisation')]
    public function parLocalisation(
        string $slug,
        LocalisationRepository $localisationRepository
    ): Response {
        $localisation = $localisationRepository->findBySlug($slug);

        if (!$localisation) {
            throw $this->createNotFoundException('Localisation non trouvée.');
        }

        return $this->render('appartement/localisation.html.twig', [
            'localisation' => $localisation,
            'appartements' => $localisation->getAppartements(),
        ]);
    }

    /**
     * Détail d'un appartement
     */
    #[Route('/appartement/{slug}', name: 'app_appartement_detail')]
    public function detail(string $slug, AppartementRepository $appartementRepository): Response
    {
        $appartement = $appartementRepository->findBySlug($slug);

        if (!$appartement) {
            throw $this->createNotFoundException('Appartement non trouvé.');
        }

        return $this->render('appartement/detail.html.twig', [
            'appartement' => $appartement,
        ]);
    }
}