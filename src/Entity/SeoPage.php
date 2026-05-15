<?php

namespace App\Entity;

use App\Repository\SeoPageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Métadonnées SEO par route Symfony.
 *
 * Implémente les recommandations d'Olivier Andrieu (Réussir son référencement web, 2022-2023) :
 *  – Titre SEO ≠ balise H1 (principe fondamental Andrieu ch.5)
 *  – Mot-clé cible unique par page (cocon sémantique)
 *  – Meta description avec appel à l'action (CTA)
 *  – Directive robots par page
 *  – Schema.org configuré par page
 *  – Hreflang pour le bilinguisme FR/EN
 */
#[ORM\Entity(repositoryClass: SeoPageRepository::class)]
#[ORM\Table(name: 'seo_page')]
#[ORM\UniqueConstraint(name: 'uniq_seo_route', columns: ['route'])]
#[ORM\HasLifecycleCallbacks]
class SeoPage
{
    // ── Constantes robots ────────────────────────────────────────────────
    public const ROBOTS_INDEX_FOLLOW    = 'index, follow';
    public const ROBOTS_NOINDEX_FOLLOW  = 'noindex, follow';
    public const ROBOTS_NOINDEX_NOFOLLOW = 'noindex, nofollow';
    public const ROBOTS_INDEX_NOFOLLOW  = 'index, nofollow';

    // ── Constantes OG type ───────────────────────────────────────────────
    public const OG_TYPE_WEBSITE = 'website';
    public const OG_TYPE_ARTICLE = 'article';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Nom de route Symfony exact – ex: app_home, app_appartement_detail */
    #[ORM\Column(length: 100)]
    private string $route = '';

    /** Libellé admin */
    #[ORM\Column(length: 150)]
    private string $label = '';

    // ═══════════════════════════════════════════════════════════════════
    // ── BALISES TITLE & H1 (Andrieu: title ≠ H1, ch.5) ─────────────
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Balise <title> – 55 à 65 caractères.
     * Andrieu : commence par le mot-clé cible, se termine par la marque.
     * Ex: "Appartement Pont-Saint-Esprit – Appart Hôtel Tricastin"
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titre = null;

    /**
     * Balise H1 – DIFFÉRENTE du titre (principe Andrieu).
     * Si vide, le template utilise son propre H1 par défaut.
     * Ex: "Votre appartement meublé à Pont-Saint-Esprit"
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $h1 = null;

    // ═══════════════════════════════════════════════════════════════════
    // ── SÉMANTIQUE (Andrieu: cocon sémantique, ch.7) ─────────────────
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Mot-clé principal/cible de la page.
     * Andrieu : 1 seul mot-clé primaire par page.
     * Ex: "appartement meublé Pont-Saint-Esprit"
     */
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $focusKeyword = null;

    /**
     * Mots-clés secondaires (LSI / sémantique latente).
     * Andrieu : enrichissement sémantique, 3 à 5 mots-clés.
     * Format : un mot-clé par ligne.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $secondaryKeywords = null;

    // ═══════════════════════════════════════════════════════════════════
    // ── META DESCRIPTION (Andrieu: CTA, 150-160 chars, ch.5) ─────────
    // ═══════════════════════════════════════════════════════════════════

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    // ═══════════════════════════════════════════════════════════════════
    // ── PARAMÈTRES TECHNIQUES ────────────────────────────────────────
    // ═══════════════════════════════════════════════════════════════════

    /** Directive robots crawler */
    #[ORM\Column(length: 50)]
    private string $robots = self::ROBOTS_INDEX_FOLLOW;

    /** URL canonique – vide = URL courante de la page */
    #[ORM\Column(length: 512, nullable: true)]
    private ?string $canonical = null;

    // ═══════════════════════════════════════════════════════════════════
    // ── OPEN GRAPH / RÉSEAUX SOCIAUX (Andrieu: ch.11) ────────────────
    // ═══════════════════════════════════════════════════════════════════

    /** URL absolue de l'image OG (recommandé: 1200×630 px) */
    #[ORM\Column(length: 512, nullable: true)]
    private ?string $ogImage = null;

    /** og:type — website ou article */
    #[ORM\Column(length: 30)]
    private string $ogType = self::OG_TYPE_WEBSITE;

    // ═══════════════════════════════════════════════════════════════════
    // ── SCHEMA.ORG / DONNÉES STRUCTURÉES (Andrieu: ch.10) ────────────
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Type de schéma principal de la page.
     * Valeurs : WebPage | LodgingBusiness | Apartment | ContactPage | FAQPage
     * Géré par SeoService::buildJsonLd()
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $schemaType = null;

    /**
     * Paires question/réponse pour le schéma FAQPage.
     * JSON : [{"question": "...", "answer": "..."}, ...]
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $faqItems = null;

    /** Champs JSON-LD additionnels fusionnés dans le schéma généré */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $schemaExtra = null;

    // ═══════════════════════════════════════════════════════════════════
    // ── HREFLANG (Andrieu: internationalisation, ch.12) ──────────────
    // ═══════════════════════════════════════════════════════════════════

    /**
     * URL absolue de la version française (hreflang="fr").
     * Vide = URL générée automatiquement depuis la route.
     */
    #[ORM\Column(length: 512, nullable: true)]
    private ?string $hreflangFr = null;

    /**
     * URL absolue de la version anglaise (hreflang="en").
     * Vide = URL générée automatiquement depuis la route avec locale EN.
     */
    #[ORM\Column(length: 512, nullable: true)]
    private ?string $hreflangEn = null;

    // ═══════════════════════════════════════════════════════════════════
    // ── FIL D'ARIANE (Andrieu: structure et maillage, ch.6) ──────────
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Label affiché dans le fil d'Ariane et le BreadcrumbList schema.org.
     * Si vide, le label est déduit du titre ou de la route.
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $breadcrumbLabel = null;

    // ═══════════════════════════════════════════════════════════════════
    // ── COCON SÉMANTIQUE (Andrieu: ch.7) ─────────────────────────────
    // ═══════════════════════════════════════════════════════════════════

    /** Appartient au cocon sémantique */
    #[ORM\ManyToOne(targetEntity: SeoCocon::class, inversedBy: 'pages')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?SeoCocon $cocon = null;

    /** Cette page est-elle la page pivot (hub) du cocon ? */
    #[ORM\Column]
    private bool $isCoconPivot = false;

    // ═══════════════════════════════════════════════════════════════════
    // ── MÉTADONNÉES ──────────────────────────────────────────────────
    // ═══════════════════════════════════════════════════════════════════

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // ── Getters / Setters ────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getRoute(): string { return $this->route; }
    public function setRoute(string $v): static { $this->route = $v; return $this; }

    public function getLabel(): string { return $this->label; }
    public function setLabel(string $v): static { $this->label = $v; return $this; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(?string $v): static { $this->titre = $v; return $this; }

    public function getH1(): ?string { return $this->h1; }
    public function setH1(?string $v): static { $this->h1 = $v; return $this; }

    public function getFocusKeyword(): ?string { return $this->focusKeyword; }
    public function setFocusKeyword(?string $v): static { $this->focusKeyword = $v; return $this; }

    public function getSecondaryKeywords(): ?string { return $this->secondaryKeywords; }
    public function setSecondaryKeywords(?string $v): static { $this->secondaryKeywords = $v; return $this; }

    /** @return string[] */
    public function getSecondaryKeywordsArray(): array
    {
        if (!$this->secondaryKeywords) return [];
        return array_filter(array_map('trim', explode("\n", $this->secondaryKeywords)));
    }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $v): static { $this->description = $v; return $this; }

    public function getRobots(): string { return $this->robots; }
    public function setRobots(string $v): static { $this->robots = $v; return $this; }

    public function getCanonical(): ?string { return $this->canonical; }
    public function setCanonical(?string $v): static { $this->canonical = $v; return $this; }

    public function getOgImage(): ?string { return $this->ogImage; }
    public function setOgImage(?string $v): static { $this->ogImage = $v; return $this; }

    public function getOgType(): string { return $this->ogType; }
    public function setOgType(string $v): static { $this->ogType = $v; return $this; }

    public function getSchemaType(): ?string { return $this->schemaType; }
    public function setSchemaType(?string $v): static { $this->schemaType = $v; return $this; }

    public function getFaqItems(): ?string { return $this->faqItems; }
    public function setFaqItems(?string $v): static { $this->faqItems = $v; return $this; }

    public function getSchemaExtra(): ?string { return $this->schemaExtra; }
    public function setSchemaExtra(?string $v): static { $this->schemaExtra = $v; return $this; }

    public function getHreflangFr(): ?string { return $this->hreflangFr; }
    public function setHreflangFr(?string $v): static { $this->hreflangFr = $v; return $this; }

    public function getHreflangEn(): ?string { return $this->hreflangEn; }
    public function setHreflangEn(?string $v): static { $this->hreflangEn = $v; return $this; }

    public function getBreadcrumbLabel(): ?string { return $this->breadcrumbLabel; }
    public function setBreadcrumbLabel(?string $v): static { $this->breadcrumbLabel = $v; return $this; }

    public function getCocon(): ?SeoCocon { return $this->cocon; }
    public function setCocon(?SeoCocon $v): static { $this->cocon = $v; return $this; }

    public function isCoconPivot(): bool { return $this->isCoconPivot; }
    public function setIsCoconPivot(bool $v): static { $this->isCoconPivot = $v; return $this; }

    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeInterface $v): static { $this->updatedAt = $v; return $this; }
}
