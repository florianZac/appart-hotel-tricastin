<?php

namespace App\Service;

use App\Entity\SeoPage;

/**
 * Audit SEO on-page — Andrieu "Réussir son référencement web" (2022-2023).
 *
 * Calcule un score 0-100 basé sur les critères du livre :
 *  ch.5  – Title, H1, meta description
 *  ch.5  – Mot-clé cible dans les balises clés
 *  ch.7  – Appartenance à un cocon sémantique
 *  ch.10 – Schéma JSON-LD défini
 *  ch.12 – Hreflang configuré (site bilingue)
 *
 * Chaque critère retourne : ['ok' => bool, 'message' => string, 'weight' => int]
 */
class SeoAuditService
{
    // Longueurs recommandées (Andrieu ch.5)
    private const TITLE_MIN   = 50;
    private const TITLE_MAX   = 65;
    private const DESC_MIN    = 120;
    private const DESC_MAX    = 160;
    private const H1_MAX      = 70;

    /**
     * Effectue l'audit complet d'une SeoPage.
     *
     * @return array{
     *   score: int,
     *   scoreColor: string,
     *   criteria: array<string, array{ok: bool, warning: bool, message: string, weight: int}>
     * }
     */
    public function audit(SeoPage $page): array
    {
        $criteria = [
            'title_present'     => $this->checkTitlePresent($page),
            'title_length'      => $this->checkTitleLength($page),
            'title_keyword'     => $this->checkTitleContainsKeyword($page),
            'h1_present'        => $this->checkH1Present($page),
            'h1_different'      => $this->checkH1DifferentFromTitle($page),
            'h1_keyword'        => $this->checkH1ContainsKeyword($page),
            'desc_present'      => $this->checkDescPresent($page),
            'desc_length'       => $this->checkDescLength($page),
            'desc_keyword'      => $this->checkDescContainsKeyword($page),
            'focus_keyword'     => $this->checkFocusKeyword($page),
            'robots_set'        => $this->checkRobots($page),
            'canonical_set'     => $this->checkCanonical($page),
            'og_image'          => $this->checkOgImage($page),
            'schema_type'       => $this->checkSchemaType($page),
            'cocon'             => $this->checkCocon($page),
            'hreflang'          => $this->checkHreflang($page),
        ];

        // Score pondéré
        $totalWeight = array_sum(array_column($criteria, 'weight'));
        $earnedWeight = 0;
        foreach ($criteria as $c) {
            if ($c['ok']) $earnedWeight += $c['weight'];
        }

        $score = $totalWeight > 0 ? (int) round(($earnedWeight / $totalWeight) * 100) : 0;

        return [
            'score'      => $score,
            'scoreColor' => $this->scoreColor($score),
            'criteria'   => $criteria,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // Critères individuels
    // ═══════════════════════════════════════════════════════════════════

    private function checkTitlePresent(SeoPage $p): array
    {
        $ok = !empty($p->getTitre());
        return [
            'ok'      => $ok,
            'warning' => false,
            'message' => $ok
                ? 'Titre SEO défini ✓'
                : 'Titre SEO manquant — Andrieu : la balise <title> est la plus importante de la page.',
            'weight'  => 15,
        ];
    }

    private function checkTitleLength(SeoPage $p): array
    {
        $len  = mb_strlen($p->getTitre() ?? '');
        $ok   = $len >= self::TITLE_MIN && $len <= self::TITLE_MAX;
        $warn = $len > 0 && !$ok;
        return [
            'ok'      => $ok,
            'warning' => $warn,
            'message' => $ok
                ? "Longueur du titre correcte ({$len} car.) ✓"
                : ($len === 0
                    ? 'Titre non renseigné'
                    : "Longueur du titre : {$len} car. (cible: " . self::TITLE_MIN . "–" . self::TITLE_MAX . "). Andrieu : les moteurs tronquent au-delà de 65 car."),
            'weight'  => 8,
        ];
    }

    private function checkTitleContainsKeyword(SeoPage $p): array
    {
        if (!$p->getFocusKeyword() || !$p->getTitre()) {
            return ['ok' => false, 'warning' => true, 'message' => 'Impossible à vérifier — mot-clé ou titre manquant.', 'weight' => 10];
        }
        $ok = $this->containsKeyword($p->getTitre(), $p->getFocusKeyword());
        return [
            'ok'      => $ok,
            'warning' => false,
            'message' => $ok
                ? 'Mot-clé cible présent dans le titre ✓'
                : 'Mot-clé cible absent du titre. Andrieu : le mot-clé principal doit figurer en début de <title>.',
            'weight'  => 10,
        ];
    }

    private function checkH1Present(SeoPage $p): array
    {
        $ok = !empty($p->getH1());
        return [
            'ok'      => $ok,
            'warning' => false,
            'message' => $ok
                ? 'H1 défini ✓'
                : 'H1 manquant. Andrieu : définissez un H1 distinct du title pour chaque page.',
            'weight'  => 10,
        ];
    }

    private function checkH1DifferentFromTitle(SeoPage $p): array
    {
        if (!$p->getH1() || !$p->getTitre()) {
            return ['ok' => false, 'warning' => true, 'message' => 'Title ou H1 manquant pour comparer.', 'weight' => 8];
        }
        $same = strtolower(trim($p->getH1())) === strtolower(trim($p->getTitre()));
        return [
            'ok'      => !$same,
            'warning' => $same,
            'message' => !$same
                ? 'H1 différent du titre ✓ (principe fondamental Andrieu)'
                : 'H1 identique au titre ! Andrieu : H1 et <title> DOIVENT être différents pour couvrir plus de requêtes.',
            'weight'  => 8,
        ];
    }

    private function checkH1ContainsKeyword(SeoPage $p): array
    {
        if (!$p->getFocusKeyword() || !$p->getH1()) {
            return ['ok' => false, 'warning' => true, 'message' => 'Impossible à vérifier — mot-clé ou H1 manquant.', 'weight' => 7];
        }
        $ok = $this->containsKeyword($p->getH1(), $p->getFocusKeyword());
        return [
            'ok'      => $ok,
            'warning' => false,
            'message' => $ok
                ? 'Mot-clé cible présent dans le H1 ✓'
                : 'Mot-clé cible absent du H1. Andrieu : le H1 doit contenir le mot-clé principal.',
            'weight'  => 7,
        ];
    }

    private function checkDescPresent(SeoPage $p): array
    {
        $ok = !empty($p->getDescription());
        return [
            'ok'      => $ok,
            'warning' => false,
            'message' => $ok
                ? 'Meta description définie ✓'
                : 'Meta description manquante. Andrieu : indispensable pour le taux de clic (CTR).',
            'weight'  => 10,
        ];
    }

    private function checkDescLength(SeoPage $p): array
    {
        $len  = mb_strlen($p->getDescription() ?? '');
        $ok   = $len >= self::DESC_MIN && $len <= self::DESC_MAX;
        $warn = $len > 0 && !$ok;
        return [
            'ok'      => $ok,
            'warning' => $warn,
            'message' => $ok
                ? "Meta description : {$len} car. ✓"
                : ($len === 0
                    ? 'Description non renseignée'
                    : "Description : {$len} car. (cible : " . self::DESC_MIN . "–" . self::DESC_MAX . " car.). Andrieu : tronquée au-delà de 160 car. dans les SERP."),
            'weight'  => 5,
        ];
    }

    private function checkDescContainsKeyword(SeoPage $p): array
    {
        if (!$p->getFocusKeyword() || !$p->getDescription()) {
            return ['ok' => false, 'warning' => true, 'message' => 'Impossible à vérifier.', 'weight' => 5];
        }
        $ok = $this->containsKeyword($p->getDescription(), $p->getFocusKeyword());
        return [
            'ok'      => $ok,
            'warning' => false,
            'message' => $ok
                ? 'Mot-clé dans la meta description ✓'
                : 'Mot-clé absent de la meta description. Andrieu : Google le met en gras dans les SERP → meilleur CTR.',
            'weight'  => 5,
        ];
    }

    private function checkFocusKeyword(SeoPage $p): array
    {
        $ok = !empty($p->getFocusKeyword());
        return [
            'ok'      => $ok,
            'warning' => false,
            'message' => $ok
                ? "Mot-clé cible défini : « {$p->getFocusKeyword()} » ✓"
                : 'Mot-clé cible manquant. Andrieu ch.7 : 1 mot-clé principal par page, base du cocon sémantique.',
            'weight'  => 5,
        ];
    }

    private function checkRobots(SeoPage $p): array
    {
        $noindex = str_contains($p->getRobots(), 'noindex');
        return [
            'ok'      => !$noindex,
            'warning' => $noindex,
            'message' => !$noindex
                ? "Robots : « {$p->getRobots()} » — page indexable ✓"
                : "⚠ Robots : « {$p->getRobots()} » — page NON indexée. Vérifiez si c'est intentionnel.",
            'weight'  => 3,
        ];
    }

    private function checkCanonical(SeoPage $p): array
    {
        $ok = !empty($p->getCanonical());
        return [
            'ok'      => $ok,
            'warning' => !$ok,
            'message' => $ok
                ? 'Canonical explicite défini ✓'
                : 'Canonical non défini — sera généré automatiquement depuis l\'URL courante (acceptable).',
            'weight'  => 3,
        ];
    }

    private function checkOgImage(SeoPage $p): array
    {
        $ok = !empty($p->getOgImage());
        return [
            'ok'      => $ok,
            'warning' => !$ok,
            'message' => $ok
                ? 'Image Open Graph définie ✓'
                : 'Image OG manquante — l\'image par défaut sera utilisée. Andrieu ch.11 : l\'image OG optimise le CTR sur les réseaux sociaux.',
            'weight'  => 4,
        ];
    }

    private function checkSchemaType(SeoPage $p): array
    {
        $ok = !empty($p->getSchemaType());
        return [
            'ok'      => $ok,
            'warning' => !$ok,
            'message' => $ok
                ? "Schéma JSON-LD configuré : {$p->getSchemaType()} ✓"
                : 'Aucun schéma JSON-LD. Andrieu ch.10 : les données structurées favorisent les rich snippets.',
            'weight'  => 5,
        ];
    }

    private function checkCocon(SeoPage $p): array
    {
        $ok = $p->getCocon() !== null;
        return [
            'ok'      => $ok,
            'warning' => !$ok,
            'message' => $ok
                ? "Cocon sémantique : « {$p->getCocon()->getNom()} »" . ($p->isCoconPivot() ? ' (page pivot) ✓' : ' (page satellite) ✓')
                : 'Page hors cocon sémantique. Andrieu ch.7 : le maillage interne par cocon est clé du SEO modern.',
            'weight'  => 5,
        ];
    }

    private function checkHreflang(SeoPage $p): array
    {
        $ok = !empty($p->getHreflangFr()) && !empty($p->getHreflangEn());
        $partial = !empty($p->getHreflangFr()) || !empty($p->getHreflangEn());
        return [
            'ok'      => $ok || $partial, // L'automatique est acceptable
            'warning' => false,
            'message' => ($ok || $partial)
                ? 'Hreflang configuré ✓ (FR/EN)'
                : 'Hreflang généré automatiquement. Andrieu ch.12 : configurez manuellement pour les URLs canoniques précises.',
            'weight'  => 2,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════════════════════════════

    private function containsKeyword(string $text, string $keyword): bool
    {
        return str_contains(
            mb_strtolower($text),
            mb_strtolower($keyword)
        );
    }

    private function scoreColor(int $score): string
    {
        if ($score >= 80) return 'success';
        if ($score >= 50) return 'warning';
        return 'danger';
    }
}
