<?php

namespace App\Entity;

use App\Repository\TarifRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TarifRepository::class)]
class Tarif
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\ManyToOne(targetEntity: Appartement::class)]
  private ?Appartement $appartement = null;

  #[ORM\Column(length: 100)]
  private ?string $saison = null;

  #[ORM\Column(type: 'date')]
  private ?\DateTimeInterface $dateDebut = null;

  #[ORM\Column(type: 'date')]
  private ?\DateTimeInterface $dateFin = null;

  #[ORM\Column]
  private ?float $prixJour = null;

  #[ORM\Column]
  private ?float $prixSemaine = null;

  #[ORM\Column]
  private ?float $prixMois = null;

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getSaison(): ?string
  {
    return $this->saison;
  }

  public function setSaison(string $saison): static
  {
    $this->saison = $saison;

    return $this;
  }

  public function getPrixJour(): ?float
  {
    return $this->prixJour;
  }

  public function setPrixJour(float $prixJour): static
  {
    $this->prixJour = $prixJour;

    return $this;
  }
  // Appartement
  public function getAppartement(): ?Appartement
  {
    return $this->appartement;
  }

  public function setAppartement(?Appartement $appartement): static
  {
    $this->appartement = $appartement;

    return $this;
  }

  // Date début
  public function getDateDebut(): ?\DateTimeInterface
  {
    return $this->dateDebut;
  }

  public function setDateDebut(\DateTimeInterface $dateDebut): static
  {
    $this->dateDebut = $dateDebut;

    return $this;
  }

  // Date fin
  public function getDateFin(): ?\DateTimeInterface
  {
    return $this->dateFin;
  }

  public function setDateFin(\DateTimeInterface $dateFin): static
  {
    $this->dateFin = $dateFin;

    return $this;
  }

  // Prix semaine
  public function getPrixSemaine(): ?float
  {
    return $this->prixSemaine;
  }

  public function setPrixSemaine(float $prixSemaine): static
  {
    $this->prixSemaine = $prixSemaine;

    return $this;
  }

  // Prix mois
  public function getPrixMois(): ?float
  {
    return $this->prixMois;
  }

  public function setPrixMois(float $prixMois): static
  {
    $this->prixMois = $prixMois;

    return $this;
  }


}
