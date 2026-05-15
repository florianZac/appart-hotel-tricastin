<?php

namespace App\Controller;

use App\Repository\AppartementRepository;
use App\Repository\LocalisationRepository;
use App\Repository\TemoignageRepository;
use App\Service\SeoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AppartementController extends AbstractController
{
    /**
     * Page listing des 3 localisations.
     * Andrieu : page pivot du cocon "Séjour & Appartements".
     */
    #[Route('/les-appartements', name: 'app_appartements')]
    public function index(LocalisationRepository $localisationRepository): Response
    {
        // SEO géré via la SeoPage admin (route: app_appartements)
        return $this->render('appartement/index.html.twig', [
            'localisations' => $localisationRepository->findAllWithAppartements(),
        ]);
    }

    /**
     * Appartements d'une localisation.
     * Andrieu ch.7 : page satellite du cocon, H1 géographique spécifique.
     */
    #[Route('/les-appartements/{slug}', name: 'app_appartements_localisation')]
    public function parLocalisation(
        string                $slug,
        LocalisationRepository $localisationRepository,
        SeoService            $seoService,
    ): Response {
        $localisation = $localisationRepository->findBySlug($slug);

        if (!$localisation) {
            throw $this->createNotFoundException('Localisation non trouvée.');
        }

        // Données SEO contextuelles (titre/H1 géographiques, LocalBusiness schema)
        $seoOverride = $seoService->buildForLocalisation($localisation);

        return $this->render('appartement/localisation.html.twig', [
            'localisation' => $localisation,
            'appartements' => $localisation->getAppartements(),
            'seoOverride'  => $seoOverride,
        ]);
    }

    /**
     * Détail d'un appartement.
     * Andrieu ch.5 : title ≠ H1, mot-clé géographique en tête du title.
     * Andrieu ch.10 : schema Apartment + AggregateRating depuis les témoignages.
     */
    #[Route('/appartement/{slug}', name: 'app_appartement_detail')]
    public function detail(
        string                $slug,
        AppartementRepository $appartementRepository,
        TemoignageRepository  $temoignageRepository,
        SeoService            $seoService,
    ): Response {
        $appartement = $appartementRepository->findBySlug($slug);

        if (!$appartement) {
            throw $this->createNotFoundException('Appartement non trouvé.');
        }

        // Témoignages actifs pour AggregateRating (Andrieu ch.10)
        $temoignages = $temoignageRepository->findActifs();

        // Construction des données SEO contextuelles :
        //  – title ≠ H1 (principe Andrieu ch.5)
        //  – mot-clé focus géographique
        //  – Apartment schema + BreadcrumbList + AggregateRating
        $seoOverride = $seoService->buildForAppartement($appartement, $temoignages);

        return $this->render('appartement/detail.html.twig', [
            'appartement' => $appartement,
            'seoOverride' => $seoOverride,
        ]);
    }
}
