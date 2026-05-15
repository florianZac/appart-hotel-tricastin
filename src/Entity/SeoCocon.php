<?php

namespace App\Entity;

use App\Repository\SeoCoconRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Cocon sémantique — Andrieu "Réussir son référencement web" ch.7.
 *
 * Principe : regrouper les pages autour d'un thème central.
 * – 1 page pivot (hub) reçoit le maximum de maillage interne
 * – Les pages satellites se lient entre elles ET vers le pivot
 * – Chaque cocon couvre un champ sémantique cohérent
 *
 * Structure pour Appart Hôtel Tricastin :
 *  ┌─ Cocon "Séjour & Réservation"
 *  │   Pivot : /les-appartements
 *  │   Satellites : /les-appartements/{localisation}, /appartement/{slug}, /reservation
 *  │
 *  └─ Cocon "Informations & Contact"
 *      Pivot : / (accueil)
 *      Satellites : /contact, /mentions-legales
 */
#[ORM\Entity(repositoryClass: SeoCoconRepository::class)]
#[ORM\Table(name: 'seo_cocon')]
class SeoCocon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Nom du cocon sémantique */
    #[ORM\Column(length: 100)]
    private string $nom = '';

    /** Thème central / champ sémantique */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Mot-clé principal du cocon (Andrieu: chaque cocon a un thème de recherche).
     * Les pages du cocon doivent enrichir ce thème.
     */
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $motCleCocon = null;

    /**
     * Couleur identifiant le cocon dans l'interface admin.
     * Format hexadécimal : #3b82f6
     */
    #[ORM\Column(length: 7)]
    private string $couleur = '#3b82f6';

    /** Pages appartenant à ce cocon */
    #[ORM\OneToMany(targetEntity: SeoPage::class, mappedBy: 'cocon')]
    private Collection $pages;

    public function __construct()
    {
        $this->pages = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getNom(): string { return $this->nom; }
    public function setNom(string $v): static { $this->nom = $v; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $v): static { $this->description = $v; return $this; }

    public function getMotCleCocon(): ?string { return $this->motCleCocon; }
    public function setMotCleCocon(?string $v): static { $this->motCleCocon = $v; return $this; }

    public function getCouleur(): string { return $this->couleur; }
    public function setCouleur(string $v): static { $this->couleur = $v; return $this; }

    /** @return Collection<int, SeoPage> */
    public function getPages(): Collection { return $this->pages; }

    /** Retourne la page pivot du cocon, ou null */
    public function getPagePivot(): ?SeoPage
    {
        foreach ($this->pages as $page) {
            if ($page->isCoconPivot()) return $page;
        }
        return null;
    }

    /** @return SeoPage[] pages satellites (non-pivot) */
    public function getPagesSatellites(): array
    {
        return $this->pages->filter(fn(SeoPage $p) => !$p->isCoconPivot())->toArray();
    }

    public function __toString(): string { return $this->nom; }
}
