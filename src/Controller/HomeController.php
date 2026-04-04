<?php

namespace App\Controller;

use App\Repository\LocalisationRepository;
use App\Repository\TemoignageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
  #[Route('/', name: 'app_home')]
  public function index(
    LocalisationRepository $localisationRepository,
    TemoignageRepository $temoignageRepository
  ): Response {
    return $this->render('home/index.html.twig', [
      'localisations' => $localisationRepository->findAllWithAppartements(),
      'temoignages' => $temoignageRepository->findActifs(),
    ]);
  }

  #[Route('/mentions-legales', name: 'app_mentions_legales')]
  public function mentionsLegales(): Response
  {
    return $this->render('home/mentions_legales.html.twig');
  }
}