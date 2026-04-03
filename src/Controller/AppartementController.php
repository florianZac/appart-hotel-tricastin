<?php

namespace App\Controller;

use App\Repository\AppartementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AppartementController extends AbstractController
{
    #[Route('/les-appartements', name: 'app_appartements')]
    public function index(AppartementRepository $appartementRepository): Response
    {
        return $this->render('appartement/index.html.twig', [
            'appartements' => $appartementRepository->findAllActifs(),
        ]);
    }

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
