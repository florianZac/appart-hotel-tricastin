<?php

namespace App\Service;

use App\Entity\Appartement;
use App\Entity\Localisation;
use App\Entity\SeoPage;
use App\Entity\Temoignage;
use App\Repository\SeoPageRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Service SEO central — Andrieu "Réussir son référencement web" (2022-2023).
 *
 * Responsabilités :
 *  1. Résolution des métadonnées SEO (DB → defaults) par route
 *  2. Génération JSON-LD schema.org :
 *     – WebSite (homepage uniquement, SearchAction sitelinks)
 *     – LodgingBusiness (accueil, Andrieu: signaux EAT)
 *     – BreadcrumbList (toutes les pages, maillage structurel)
 *     – Apartment (fiches appartements)
 *     – LocalBusiness par localisation
 *     – FAQPage (schéma FAQ configuré par admin)
 *     – AggregateRating (avec témoignages)
 *  3. Génération des balises hreflang (FR/EN)
 *  4. Calcul des liens de maillage interne (cocon sémantique)
 */
class SeoService
{
    // ── Identité du site ─────────────────────────────────────────────────
    public const SITE_NAME    = 'Appart Hôtel Tricastin';
    public const SITE_NAME_EN = 'Tricastin Serviced Apartments';
    public const SITE_DESC_FR = '12 appartements meublés de standing dans le Tricastin. Réservez votre séjour à Pont-Saint-Esprit, Saint-Paul-Trois-Châteaux ou Tulette.';
    public const SITE_DESC_EN = '12 furnished serviced apartments in the Tricastin region. Book your stay near the Rhône Valley.';
    public const DEFAULT_OG_IMAGE = '/images/og-default.jpg';

    // ── Types de schémas ─────────────────────────────────────────────────
    public const SCHEMA_WEBPAGE      = 'WebPage';
    public const SCHEMA_LODGING      = 'LodgingBusiness';
    public const SCHEMA_APARTMENT    = 'Apartment';
    public const SCHEMA_CONTACT      = 'ContactPage';
    public const SCHEMA_FAQ          = 'FAQPage';

    public function __construct(
        private readonly SeoPageRepository    $seoRepo,
        private readonly RequestStack         $requestStack,
        private readonly UrlGeneratorInterface $router,
        private readonly string $appEnv = 'prod',
    ) {}

    // ═══════════════════════════════════════════════════════════════════
    // 1. RÉSOLUTION PRINCIPALE
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Retourne le tableau complet de données SEO pour la page courante.
     *
     * Ordre de priorité :
     *   $overrides (controller) > DB (admin) > defaults (code)
     *
     * @param array $overrides Données contextuelles passées par le controller
     *                         (ex: données d'un appartement spécifique)
     */
    public function resolve(array $overrides = []): array
    {
        $request  = $this->requestStack->getCurrentRequest();
        $route    = $request?->attributes->get('_route') ?? '';
        $locale   = $request?->getLocale() ?? 'fr';
        $baseUrl  = $request?->getSchemeAndHttpHost() ?? '';
        $uri      = $request?->getUri() ?? '';

        // Lecture DB
        $seoPage = $this->seoRepo->findByRoute($route);

        // Description par défaut localisée
        $defaultDesc = $locale === 'fr' ? self::SITE_DESC_FR : self::SITE_DESC_EN;

        // Construction du tableau de base
        $data = [
            // Balises fondamentales
            'titre'            => $seoPage?->getTitre(),
            'h1'               => $seoPage?->getH1(),
            'description'      => $seoPage?->getDescription() ?? $defaultDesc,
            'robots'           => $seoPage?->getRobots() ?? SeoPage::ROBOTS_INDEX_FOLLOW,
            'canonical'        => $seoPage?->getCanonical() ?? $uri,
            // Open Graph
            'ogImage'          => $seoPage?->getOgImage() ?? ($baseUrl . self::DEFAULT_OG_IMAGE),
            'ogType'           => $seoPage?->getOgType() ?? SeoPage::OG_TYPE_WEBSITE,
            // Sémantique Andrieu
            'focusKeyword'     => $seoPage?->getFocusKeyword(),
            'breadcrumbLabel'  => $seoPage?->getBreadcrumbLabel(),
            // Hreflang
            'hreflangFr'       => $seoPage?->getHreflangFr(),
            'hreflangEn'       => $seoPage?->getHreflangEn(),
            // Schema.org
            'schemaType'       => $seoPage?->getSchemaType(),
            'faqItems'         => $seoPage?->getFaqItems(),
            'schemaExtra'      => $seoPage?->getSchemaExtra(),
            // Données calculées (remplies plus bas)
            'jsonLd'           => null,
            'breadcrumbs'      => [],
            'locale'           => $locale,
            'baseUrl'          => $baseUrl,
            'route'            => $route,
        ];

        // 2. Appliquer les overrides (données contextuelles du controller)
        foreach ($overrides as $key => $value) {
            if ($value !== null && $value !== '') {
                $data[$key] = $value;
            }
        }

        // 3. Générer les hreflang automatiques si non définis
        if (!$data['hreflangFr'] || !$data['hreflangEn']) {
            [$data['hreflangFr'], $data['hreflangEn']] = $this->buildHreflang(
                $route,
                $request?->attributes->all() ?? [],
                $data['hreflangFr'],
                $data['hreflangEn']
            );
        }

        // 4. Générer le JSON-LD si non fourni en override
        if ($data['jsonLd'] === null && $data['schemaType']) {
            $data['jsonLd'] = $this->buildJsonLd(
                $data['schemaType'],
                $data,
                $baseUrl,
                $seoPage
            );
        }

        return $data;
    }

    // ═══════════════════════════════════════════════════════════════════
    // 2. DONNÉES CONTEXTUELLES — PAGES DYNAMIQUES
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Données SEO pour la page de détail d'un appartement.
     * Andrieu ch.5 : title commence par le mot-clé géographique.
     *
     * @param Temoignage[] $temoignages Pour le schéma AggregateRating
     */
    public function buildForAppartement(Appartement $app, array $temoignages = []): array
    {
        $baseUrl = $this->getBaseUrl();
        $ville   = $app->getLocalisation()?->getVille() ?? '';

        // Title Andrieu : mot-clé géo en tête, marque en queue
        $titre = sprintf('%s à %s — %s', $app->getNom(), $ville, self::SITE_NAME);

        // H1 différent du title (principe fondamental Andrieu)
        $h1 = sprintf('Votre %s meublé à %s', strtolower($app->getType() ?? 'appartement'), $ville);

        // Meta description : mot-clé + CTA (Andrieu ch.5)
        $rawDesc   = strip_tags($app->getDescription() ?? '');
        $shortDesc = $this->truncate($rawDesc, 130);
        $description = sprintf('%s Réservez en ligne dès maintenant.', $shortDesc);

        // Canonical absolu
        $canonical = $this->router->generate(
            'app_appartement_detail',
            ['slug' => $app->getSlug()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Image OG
        $ogImage = $app->getImagePrincipale()
            ? (str_starts_with($app->getImagePrincipale(), 'http')
                ? $app->getImagePrincipale()
                : $baseUrl . $app->getImagePrincipale())
            : $baseUrl . self::DEFAULT_OG_IMAGE;

        // Fil d'Ariane
        $breadcrumbs = [
            ['label' => 'Accueil',          'url' => $this->router->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL)],
            ['label' => 'Nos appartements', 'url' => $this->router->generate('app_appartements', [], UrlGeneratorInterface::ABSOLUTE_URL)],
            ['label' => $ville,              'url' => $this->router->generate('app_appartements_localisation', ['slug' => $app->getLocalisation()?->getSlug() ?? ''], UrlGeneratorInterface::ABSOLUTE_URL)],
            ['label' => $app->getNom(),      'url' => $canonical],
        ];

        $jsonLd = $this->buildApartmentSchema($app, $ogImage, $baseUrl, $temoignages);

        return [
            'titre'           => $titre,
            'h1'              => $h1,
            'description'     => $description,
            'focusKeyword'    => 'appartement meublé ' . strtolower($ville),
            'robots'          => SeoPage::ROBOTS_INDEX_FOLLOW,
            'canonical'       => $canonical,
            'ogImage'         => $ogImage,
            'ogType'          => SeoPage::OG_TYPE_WEBSITE,
            'schemaType'      => self::SCHEMA_APARTMENT,
            'breadcrumbs'     => $breadcrumbs,
            'jsonLd'          => $jsonLd,
        ];
    }

    /**
     * Données SEO pour la page d'une localisation.
     */
    public function buildForLocalisation(Localisation $loc): array
    {
        $baseUrl   = $this->getBaseUrl();
        $ville     = $loc->getVille();
        $nb        = count($loc->getAppartements());
        $canonical = $this->router->generate(
            'app_appartements_localisation',
            ['slug' => $loc->getSlug()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $titre       = sprintf('Appartements meublés à %s (%d disponibles) — %s', $ville, $nb, self::SITE_NAME);
        $h1          = sprintf('Nos %d appartements meublés à %s', $nb, $ville);
        $description = sprintf(
            'Découvrez nos %d appartements meublés de standing à %s. Location courte et longue durée. Réservation en ligne sécurisée.',
            $nb, $ville
        );

        $ogImage = $loc->getImageCouverture()
            ? (str_starts_with($loc->getImageCouverture(), 'http') ? $loc->getImageCouverture() : $baseUrl . $loc->getImageCouverture())
            : $baseUrl . self::DEFAULT_OG_IMAGE;

        $breadcrumbs = [
            ['label' => 'Accueil',          'url' => $this->router->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL)],
            ['label' => 'Nos appartements', 'url' => $this->router->generate('app_appartements', [], UrlGeneratorInterface::ABSOLUTE_URL)],
            ['label' => $ville,              'url' => $canonical],
        ];

        $jsonLd = $this->buildLocalisationSchema($loc, $ogImage, $canonical, $baseUrl);

        return [
            'titre'        => $titre,
            'h1'           => $h1,
            'description'  => $description,
            'focusKeyword' => 'appartement meublé ' . strtolower($ville),
            'robots'       => SeoPage::ROBOTS_INDEX_FOLLOW,
            'canonical'    => $canonical,
            'ogImage'      => $ogImage,
            'ogType'       => SeoPage::OG_TYPE_WEBSITE,
            'breadcrumbs'  => $breadcrumbs,
            'jsonLd'       => $jsonLd,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // 3. HREFLANG (Andrieu ch.12)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Génère les URLs hreflang FR et EN automatiquement.
     * Tente de générer la route avec locale forcée. Fallback sur l'URL courante.
     *
     * @return array{0: string|null, 1: string|null} [hreflangFr, hreflangEn]
     */
    private function buildHreflang(string $route, array $routeParams, ?string $existingFr, ?string $existingEn): array
    {
        // Routes qui n'ont pas de version multilingue (espaces privés)
        $noHreflangRoutes = ['admin_', 'client_', 'app_login', 'app_register',
                             'app_logout', 'app_payment', 'app_reservation'];

        foreach ($noHreflangRoutes as $prefix) {
            if (str_starts_with($route, $prefix)) {
                return [null, null];
            }
        }

        $fr = $existingFr;
        $en = $existingEn;

        if (!$fr || !$en) {
            $params = array_filter($routeParams, fn($v) => is_string($v) || is_int($v));
            unset($params['_route'], $params['_controller'], $params['_locale']);

            try {
                if (!$fr) {
                    $fr = $this->router->generate($route, array_merge($params, ['_locale' => 'fr']), UrlGeneratorInterface::ABSOLUTE_URL);
                }
                if (!$en) {
                    $en = $this->router->generate($route, array_merge($params, ['_locale' => 'en']), UrlGeneratorInterface::ABSOLUTE_URL);
                }
            } catch (\Exception) {
                // Route ne supporte pas le paramètre _locale
                $currentUrl = $this->requestStack->getCurrentRequest()?->getUri();
                $fr = $fr ?? $currentUrl;
                $en = $en ?? $currentUrl;
            }
        }

        return [$fr, $en];
    }

    // ═══════════════════════════════════════════════════════════════════
    // 4. SCHÉMAS JSON-LD
    // ═══════════════════════════════════════════════════════════════════

    private function buildJsonLd(string $type, array $data, string $baseUrl, ?SeoPage $seoPage): ?string
    {
        return match ($type) {
            self::SCHEMA_WEBPAGE  => $this->buildWebPageSchema($data, $baseUrl),
            self::SCHEMA_LODGING  => $this->buildLodgingSchema($data, $baseUrl),
            self::SCHEMA_CONTACT  => $this->buildContactSchema($data),
            self::SCHEMA_FAQ      => $this->buildFaqSchema($data, $seoPage),
            default               => null,
        };
    }

    /**
     * WebPage générique — + BreadcrumbList si breadcrumbs fournis.
     */
    private function buildWebPageSchema(array $data, string $baseUrl): string
    {
        $schemas = [];

        // Schéma principal WebPage
        $page = [
            '@context'    => 'https://schema.org',
            '@type'       => 'WebPage',
            'name'        => $data['titre'] ?? self::SITE_NAME,
            'description' => $data['description'],
            'url'         => $data['canonical'],
            'inLanguage'  => $data['locale'] ?? 'fr',
            'isPartOf'    => ['@type' => 'WebSite', 'name' => self::SITE_NAME, 'url' => $baseUrl],
        ];
        if ($data['focusKeyword'] ?? null) {
            $page['keywords'] = $data['focusKeyword'];
        }
        $page = $this->mergeExtra($page, $data['schemaExtra'] ?? null);
        $schemas[] = json_encode($page, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // BreadcrumbList
        if (!empty($data['breadcrumbs'])) {
            $schemas[] = $this->buildBreadcrumbList($data['breadcrumbs']);
        }

        return implode("\n", $schemas);
    }

    /**
     * LodgingBusiness pour la page d'accueil.
     * Andrieu ch.10 : signaux E-E-A-T, adresse, téléphone, horaires.
     * + WebSite avec SearchAction (Sitelinks Searchbox Google).
     */
    private function buildLodgingSchema(array $data, string $baseUrl): string
    {
        $schemas = [];

        // WebSite avec SearchAction (sitelinks searchbox)
        $webSite = [
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            'name'            => self::SITE_NAME,
            'url'             => $baseUrl,
            'inLanguage'      => ['fr', 'en'],
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $baseUrl . '/les-appartements?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
        $schemas[] = json_encode($webSite, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // LodgingBusiness
        $lodging = [
            '@context'    => 'https://schema.org',
            '@type'       => 'LodgingBusiness',
            '@id'         => $baseUrl . '/#lodging',
            'name'        => self::SITE_NAME,
            'description' => $data['description'],
            'url'         => $baseUrl,
            'image'       => $data['ogImage'],
            'logo'        => $baseUrl . '/images/logo.png',
            'address'     => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => 'Tricastin',
                'addressRegion'   => 'Occitanie',
                'addressCountry'  => 'FR',
            ],
            'geo' => [
                '@type'     => 'GeoCoordinates',
                'latitude'  => '44.22',
                'longitude' => '4.64',
            ],
            'priceRange'          => '€€',
            'currenciesAccepted'  => 'EUR',
            'paymentAccepted'     => 'Cash, Credit Card, Bank Transfer',
            'numberOfRooms'       => 12,
            'checkinTime'         => 'T16:00:00',
            'checkoutTime'        => 'T10:00:00',
            'availableLanguage'   => [
                ['@type' => 'Language', 'name' => 'French'],
                ['@type' => 'Language', 'name' => 'English'],
            ],
            'sameAs' => [
                // À compléter avec les profils réseaux sociaux réels
            ],
        ];
        $lodging = $this->mergeExtra($lodging, $data['schemaExtra'] ?? null);
        $schemas[] = json_encode($lodging, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // BreadcrumbList minimal (homepage)
        $schemas[] = $this->buildBreadcrumbList([
            ['label' => 'Accueil', 'url' => $baseUrl . '/'],
        ]);

        return implode("\n", $schemas);
    }

    /**
     * Apartment — fiche appartement.
     * Andrieu : données structurées maximales pour les rich snippets.
     *
     * @param Temoignage[] $temoignages
     */
    private function buildApartmentSchema(Appartement $app, string $imageUrl, string $baseUrl, array $temoignages): string
    {
        $schemas = [];
        $url     = $this->router->generate('app_appartement_detail', ['slug' => $app->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL);

        $apartment = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Apartment',
            '@id'         => $url . '#apartment',
            'name'        => $app->getNom(),
            'description' => strip_tags($app->getDescription() ?? ''),
            'url'         => $url,
            'image'       => $imageUrl,
            'floorSize'   => [
                '@type'    => 'QuantitativeValue',
                'value'    => $app->getSurface(),
                'unitCode' => 'MTK',
            ],
            'occupancy' => [
                '@type'    => 'QuantitativeValue',
                'minValue' => $app->getCapaciteMin(),
                'maxValue' => $app->getCapaciteMax(),
            ],
            'numberOfRooms' => 1,
            'petsAllowed'   => false,
            'containedInPlace' => [
                '@type' => 'LodgingBusiness',
                '@id'   => $baseUrl . '/#lodging',
                'name'  => self::SITE_NAME,
                'url'   => $baseUrl,
            ],
            'offers' => [
                '@type'           => 'Offer',
                'price'           => $app->getPrixParNuit(),
                'priceCurrency'   => 'EUR',
                'unitCode'        => 'DAY',
                'availability'    => 'https://schema.org/InStock',
                'url'             => $this->router->generate('app_reservation', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ],
        ];

        if ($app->getLocalisation()) {
            $loc = $app->getLocalisation();
            $apartment['address'] = [
                '@type'           => 'PostalAddress',
                'addressLocality' => $loc->getVille(),
                'postalCode'      => $loc->getCodePostal(),
                'addressCountry'  => 'FR',
            ];
            $apartment['geo'] = [
                '@type'     => 'GeoCoordinates',
                // Coordonnées approximatives au niveau de la commune
                'addressLocality' => $loc->getVille(),
            ];
        }

        // AggregateRating depuis les témoignages (Andrieu: rich snippets avis)
        $actifs = array_filter($temoignages, fn($t) => method_exists($t, 'isActif') && $t->isActif());
        if (count($actifs) > 0) {
            $notes = array_map(fn($t) => method_exists($t, 'getNote') ? (int)$t->getNote() : 5, $actifs);
            $apartment['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => round(array_sum($notes) / count($notes), 1),
                'reviewCount' => count($actifs),
                'bestRating'  => 5,
                'worstRating' => 1,
            ];
        }

        $schemas[] = json_encode($apartment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // BreadcrumbList
        if ($app->getLocalisation()) {
            $schemas[] = $this->buildBreadcrumbList([
                ['label' => 'Accueil',          'url' => $this->router->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL)],
                ['label' => 'Nos appartements', 'url' => $this->router->generate('app_appartements', [], UrlGeneratorInterface::ABSOLUTE_URL)],
                ['label' => $app->getLocalisation()->getVille(), 'url' => $this->router->generate('app_appartements_localisation', ['slug' => $app->getLocalisation()->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL)],
                ['label' => $app->getNom(), 'url' => $url],
            ]);
        }

        return implode("\n", $schemas);
    }

    /**
     * LocalBusiness pour une page de localisation.
     */
    private function buildLocalisationSchema(Localisation $loc, string $imageUrl, string $canonical, string $baseUrl): string
    {
        $schemas = [];

        $localBusiness = [
            '@context'    => 'https://schema.org',
            '@type'       => 'LodgingBusiness',
            'name'        => self::SITE_NAME . ' — ' . $loc->getVille(),
            'description' => $loc->getDescription() ?? '',
            'url'         => $canonical,
            'image'       => $imageUrl,
            'address'     => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $loc->getAdresse(),
                'addressLocality' => $loc->getVille(),
                'postalCode'      => $loc->getCodePostal(),
                'addressCountry'  => 'FR',
            ],
            'telephone' => $loc->getTelephone(),
            'email'     => $loc->getEmail(),
            'parentOrganization' => [
                '@type' => 'LodgingBusiness',
                '@id'   => $baseUrl . '/#lodging',
                'name'  => self::SITE_NAME,
            ],
        ];

        $schemas[] = json_encode($localBusiness, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // BreadcrumbList
        $schemas[] = $this->buildBreadcrumbList([
            ['label' => 'Accueil',          'url' => $this->router->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL)],
            ['label' => 'Nos appartements', 'url' => $this->router->generate('app_appartements', [], UrlGeneratorInterface::ABSOLUTE_URL)],
            ['label' => $loc->getVille(),   'url' => $canonical],
        ]);

        return implode("\n", $schemas);
    }

    /**
     * ContactPage + ContactPoint.
     */
    private function buildContactSchema(array $data): string
    {
        $schemas = [];

        $contact = [
            '@context'    => 'https://schema.org',
            '@type'       => 'ContactPage',
            'name'        => $data['titre'] ?? 'Contact — ' . self::SITE_NAME,
            'description' => $data['description'],
            'url'         => $data['canonical'],
            'mainEntity'  => [
                '@type'            => 'Organization',
                'name'             => self::SITE_NAME,
                'contactPoint'     => [
                    '@type'             => 'ContactPoint',
                    'contactType'       => 'customer service',
                    'availableLanguage' => ['French', 'English'],
                ],
            ],
        ];

        $contact = $this->mergeExtra($contact, $data['schemaExtra'] ?? null);
        $schemas[] = json_encode($contact, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (!empty($data['breadcrumbs'])) {
            $schemas[] = $this->buildBreadcrumbList($data['breadcrumbs']);
        }

        return implode("\n", $schemas);
    }

    /**
     * FAQPage — configuré par l'admin via faqItems JSON.
     * Andrieu ch.10 : rich snippet FAQ très efficace pour les featured snippets.
     */
    private function buildFaqSchema(array $data, ?SeoPage $seoPage): ?string
    {
        $faqJson = $data['faqItems'] ?? $seoPage?->getFaqItems();
        if (!$faqJson) return null;

        $items = json_decode($faqJson, true);
        if (!is_array($items) || empty($items)) return null;

        $mainEntity = array_map(fn($item) => [
            '@type'          => 'Question',
            'name'           => $item['question'] ?? '',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => $item['answer'] ?? '',
            ],
        ], $items);

        $schema = [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'name'       => $data['titre'] ?? self::SITE_NAME,
            'url'        => $data['canonical'],
            'mainEntity' => $mainEntity,
        ];

        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * BreadcrumbList — Andrieu ch.6 : fil d'Ariane obligatoire pour le maillage structurel.
     *
     * @param array<array{label: string, url: string}> $items
     */
    public function buildBreadcrumbList(array $items): string
    {
        $listElements = [];
        foreach ($items as $position => $item) {
            $listElements[] = [
                '@type'    => 'ListItem',
                'position' => $position + 1,
                'name'     => $item['label'],
                'item'     => $item['url'],
            ];
        }

        $schema = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $listElements,
        ];

        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    // ═══════════════════════════════════════════════════════════════════
    // 5. HELPERS
    // ═══════════════════════════════════════════════════════════════════

    /** Fusionne les champs schemaExtra JSON dans le schéma */
    private function mergeExtra(array $schema, ?string $extraJson): array
    {
        if (!$extraJson) return $schema;
        $extra = json_decode($extraJson, true);
        if (is_array($extra)) {
            $schema = array_merge($schema, $extra);
        }
        return $schema;
    }

    /** Tronque proprement sans couper un mot */
    public function truncate(string $text, int $max): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text));
        if (mb_strlen($text) <= $max) return $text;
        $cut = mb_substr($text, 0, $max - 3);
        $lastSpace = mb_strrpos($cut, ' ');
        return ($lastSpace !== false ? mb_substr($cut, 0, $lastSpace) : $cut) . '...';
    }

    private function getBaseUrl(): string
    {
        return $this->requestStack->getCurrentRequest()?->getSchemeAndHttpHost() ?? '';
    }
}
