<?php

namespace App\Twig;

use App\Service\SeoService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Extension Twig SEO.
 *
 * Fonctions :
 *  seo_resolve(overrides)        → array  données SEO page courante
 *  seo_breadcrumb_list(items)    → string JSON-LD BreadcrumbList
 *
 * Filtres :
 *  text|seo_truncate(max)        → string tronque pour meta
 *  text|seo_strip(max)           → string strip_tags + truncate
 */
class SeoExtension extends AbstractExtension
{
    public function __construct(private readonly SeoService $seoService) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('seo_resolve', [$this, 'resolve']),
            new TwigFunction('seo_breadcrumb_list', [$this, 'breadcrumbList']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('seo_truncate', [$this, 'truncate']),
            new TwigFilter('seo_strip', [$this, 'stripAndTruncate']),
        ];
    }

    /**
     * Résout les données SEO pour la page courante.
     *
     * Usage dans les templates enfants :
     *   {# Surcharge contextuelle depuis le controller #}
     *   {% set _seo = seo_resolve(seoOverride ?? {}) %}
     *
     *   {# Dans base.html.twig, sans surcharge #}
     *   {% set _seo = seo_resolve() %}
     */
    public function resolve(array $overrides = []): array
    {
        return $this->seoService->resolve($overrides);
    }

    /**
     * Génère un JSON-LD BreadcrumbList standalone (pour injection manuelle).
     *
     * @param array<array{label: string, url: string}> $items
     */
    public function breadcrumbList(array $items): string
    {
        return $this->seoService->buildBreadcrumbList($items);
    }

    /** Tronque en coupant au dernier espace */
    public function truncate(string $text, int $max = 160): string
    {
        return $this->seoService->truncate($text, $max);
    }

    /** Strip HTML puis tronque */
    public function stripAndTruncate(string $text, int $max = 160): string
    {
        return $this->seoService->truncate(strip_tags($text), $max);
    }
}
