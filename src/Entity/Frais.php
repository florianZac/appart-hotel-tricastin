<?php

namespace App\Entity;

use App\Repository\FraisRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FraisRepository::class)]
#[ORM\Table(name: 'frais')]
class Frais
{
    // ── Types de frais ──────────────────────────────────────────
    public const TYPE_HEBERGEMENT_SITE = 'hebergement_site';
    public const TYPE_NETTOYAGE        = 'nettoyage';
    public const TYPE_REPARATION       = 'reparation';
    public const TYPE_ASSURANCE        = 'assurance';
    public const TYPE_TAXE_SEJOUR      = 'taxe_sejour';
    public const TYPE_AUTRE            = 'autre';

    public const TYPES_LABELS = [
        self::TYPE_HEBERGEMENT_SITE => 'Hébergement du site',
        self::TYPE_NETTOYAGE        => 'Nettoyage',
        self::TYPE_REPARATION       => 'Réparation',
        self::TYPE_ASSURANCE        => 'Assurance',
        self::TYPE_TAXE_SEJOUR      => 'Taxe de séjour',
        self::TYPE_AUTRE            => 'Autre',
    ];

    // ── Périodicités ────────────────────────────────────────────
    public const PERIODICITE_ANNUEL    = 'annuel';
    public const PERIODICITE_MENSUEL   = 'mensuel';
    public const PERIODICITE_PONCTUEL  = 'ponctuel';

    public const PERIODICITE_LABELS = [
        self::PERIODICITE_ANNUEL   => 'Annuel',
        self::PERIODICITE_MENSUEL  => 'Mensuel',
        self::PERIODICITE_PONCTUEL => 'Ponctuel',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $typeFrais = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montant = null;

    #[ORM\Column(length: 20)]
    private ?string $periodicite = null;

    /** Mois concerné (1-12) pour les frais ponctuels/mensuels */
    #[ORM\Column(nullable: true)]
    private ?int $mois = null;

    #[ORM\Column]
    private ?int $annee = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Appartement concerné — null = frais global (ex : hébergement du site).
     */
    #[ORM\ManyToOne(targetEntity: Appartement::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Appartement $appartement = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ── Getters / Setters ───────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeFrais(): ?string
    {
        return $this->typeFrais;
    }

    public function setTypeFrais(string $typeFrais): static
    {
        $this->typeFrais = $typeFrais;
        return $this;
    }

    public function getTypeFraisLabel(): string
    {
        return self::TYPES_LABELS[$this->typeFrais] ?? $this->typeFrais;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;
        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;
        return $this;
    }

    public function getPeriodicite(): ?string
    {
        return $this->periodicite;
    }

    public function setPeriodicite(string $periodicite): static
    {
        $this->periodicite = $periodicite;
        return $this;
    }

    public function getMois(): ?int
    {
        return $this->mois;
    }

    public function setMois(?int $mois): static
    {
        $this->mois = $mois;
        return $this;
    }

    public function getAnnee(): ?int
    {
        return $this->annee;
    }

    public function setAnnee(int $annee): static
    {
        $this->annee = $annee;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getAppartement(): ?Appartement
    {
        return $this->appartement;
    }

    public function setAppartement(?Appartement $appartement): static
    {
        $this->appartement = $appartement;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
