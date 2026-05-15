<?php

namespace App\Controller;

use App\Repository\AppartementRepository;
use App\Repository\LocalisationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Génère le sitemap XML, le plan du site HTML et le robots.txt.
 *
 * Andrieu ch.4 : sitemap XML = aide au crawl budget.
 * Andrieu ch.4 : sitemap HTML = maillage interne + UX + crawlabilité.
 * Andrieu ch.4 : robots.txt = contrôle fin du crawl.
 */
class SitemapController extends AbstractController
{
    public function __construct(
        private readonly AppartementRepository  $appartRepo,
        private readonly LocalisationRepository $localisationRepo,
        private readonly string $appEnv,
    ) {}

    // ── Sitemap XML ──────────────────────────────────────────────────────
    // Andrieu : priorités différenciées par type de page, lastmod réel

    #[Route('/sitemap.xml', name: 'app_sitemap', defaults: ['_format' => 'xml'])]
    public function sitemapXml(): Response
    {
        $abs = UrlGeneratorInterface::ABSOLUTE_URL;
        $today = (new \DateTime())->format('Y-m-d');

        $urls = [];

        // 1. Homepage — priorité maximale (pivot du cocon principal)
        $urls[] = ['loc' => $this->generateUrl('app_home', [], $abs),
                   'priority' => '1.0', 'changefreq' => 'weekly', 'lastmod' => $today];

        // 2. Page listing appartements — pivot du cocon "Séjour"
        $urls[] = ['loc' => $this->generateUrl('app_appartements', [], $abs),
                   'priority' => '0.95', 'changefreq' => 'weekly', 'lastmod' => $today];

        // 3. Page réservation — page transactionnelle, très haute priorité
        $urls[] = ['loc' => $this->generateUrl('app_reservation', [], $abs),
                   'priority' => '0.95', 'changefreq' => 'daily', 'lastmod' => $today];

        // 4. Pages par localisation
        foreach ($this->localisationRepo->findAll() as $loc) {
            $urls[] = [
                'loc'        => $this->generateUrl('app_appartements_localisation', ['slug' => $loc->getSlug()], $abs),
                'priority'   => '0.85',
                'changefreq' => 'weekly',
                'lastmod'    => $loc->getUpdatedAt()?->format('Y-m-d') ?? $today,
            ];
        }

        // 5. Fiches appartements actifs
        foreach ($this->appartRepo->findAllActifs() as $app) {
            $urls[] = [
                'loc'        => $this->generateUrl('app_appartement_detail', ['slug' => $app->getSlug()], $abs),
                'priority'   => '0.85',
                'changefreq' => 'weekly',
                'lastmod'    => $today,
            ];
        }

        // 6. Page contact
        $urls[] = ['loc' => $this->generateUrl('app_contact', [], $abs),
                   'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => $today];

        // 7. Plan du site HTML (aide au crawl)
        $urls[] = ['loc' => $this->generateUrl('app_sitemap_html', [], $abs),
                   'priority' => '0.3', 'changefreq' => 'monthly', 'lastmod' => $today];

        // 8. Mentions légales
        $urls[] = ['loc' => $this->generateUrl('app_mentions_legales', [], $abs),
                   'priority' => '0.2', 'changefreq' => 'yearly', 'lastmod' => $today];

        return new Response(
            $this->renderView('sitemap/index.xml.twig', ['urls' => $urls]),
            200,
            ['Content-Type' => 'application/xml; charset=utf-8', 'Cache-Control' => 'public, max-age=86400']
        );
    }

    // ── Plan du site HTML ────────────────────────────────────────────────
    // Andrieu ch.4 + ch.6 : sitemap HTML visible = maillage interne + crawlabilité

    #[Route('/plan-du-site', name: 'app_sitemap_html')]
    public function sitemapHtml(): Response
    {
        $localisations = $this->localisationRepo->findAllWithAppartements();

        return $this->render('sitemap/index.html.twig', [
            'localisations' => $localisations,
        ]);
    }

    // ── Robots.txt ───────────────────────────────────────────────────────
    // Andrieu ch.4 : bloquer les espaces privés pour économiser le crawl budget

    #[Route('/robots.txt', name: 'app_robots')]
    public function robots(): Response
    {
        $sitemapUrl = $this->generateUrl('app_sitemap', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $isProd     = $this->appEnv === 'prod';

        $content = $isProd
            ? $this->buildProductionRobots($sitemapUrl)
            : "# Environnement non-production — indexation bloquée\nUser-agent: *\nDisallow: /\n";

        return new Response($content, 200, [
            'Content-Type'  => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function buildProductionRobots(string $sitemapUrl): string
    {
        return <<<ROBOTS
User-agent: *
Allow: /

# Espace privé (crawl budget)
Disallow: /admin/
Disallow: /mon-espace/
Disallow: /connexion
Disallow: /inscription
Disallow: /reinitialiser-mot-de-passe

# Pages techniques/transactionnelles (pas d'intérêt SEO)
Disallow: /paiement/
Disallow: /paiement-succes
Disallow: /paiement-annule
Disallow: /api/

# Outils Symfony (dev residuals)
Disallow: /_profiler/
Disallow: /_wdt/

# Bots indésirables — Andrieu : bloquer les scrapers inutiles
User-agent: AhrefsBot
Disallow: /

User-agent: MJ12bot
Disallow: /

Sitemap: {$sitemapUrl}
ROBOTS;
    }
}
