<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocaleController extends AbstractController
{
    #[Route('/locale/{locale}', name: 'app_change_locale')]
    public function changeLocale(string $locale, Request $request): Response
    {
        // Vérifier que la locale est supportée
        if (!in_array($locale, ['fr', 'en'])) {
            $locale = 'fr';
        }

        // Stocker la locale en session
        $request->getSession()->set('_locale', $locale);

        // Rediriger vers la page précédente
        $referer = $request->headers->get('referer');

        return $this->redirect($referer ?: $this->generateUrl('app_home'));
    }
}