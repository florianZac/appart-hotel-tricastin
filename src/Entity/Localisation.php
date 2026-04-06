<?php

namespace App\Entity;

use App\Repository\LocalisationRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LocalisationRepository::class)]
class Localisation
{
  #[ORM\Id]
  #[ORM\GeneratedValue(strategy: "IDENTITY")]
  #[ORM\Column(type: "integer")]
  private ?int $id = null;

  #[ORM\Column(length: 100)]
  private ?string $ville = null;

  #[ORM\Column(length: 100)]
  private ?string $slug = null;

  #[ORM\Column(length: 255)]
  private ?string $adresse = null;

  #[ORM\Column(length: 10)]
  private ?string $codePostal = null;

  #[ORM\Column(type: Types::TEXT, nullable: true)]
  private ?string $description = null;

  #[ORM\Column(length: 20, nullable: true)]
  private ?string $telephone = null;

  #[ORM\Column(length: 180, nullable: true)]
  private ?string $email = null;

  #[ORM\Column]
  private ?DateTimeImmutable $createdAt = null;

  #[ORM\Column(nullable: true)]
  private ?DateTimeImmutable $updatedAt = null;

  #[ORM\Column(length: 255, nullable: true)]
  private ?string $imageCouverture = null;

  #[ORM\OneToMany(targetEntity: Appartement::class, mappedBy: 'localisation', orphanRemoval: true)]
  private Collection $appartements;

  public function __construct()
  {
    $this->appartements = new ArrayCollection();
    $this->createdAt = new DateTimeImmutable();
  }

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getVille(): ?string
  {
    return $this->ville;
  }

  public function setVille(string $ville): static
  {
    $this->ville = $ville;
    return $this;
  }

  public function getSlug(): ?string
  {
    return $this->slug;
  }

  public function setSlug(string $slug): static
  {
    $this->slug = $slug;
    return $this;
  }

  public function getAdresse(): ?string
  {
    return $this->adresse;
  }

  public function setAdresse(string $adresse): static
  {
    $this->adresse = $adresse;
    return $this;
  }

  public function getCodePostal(): ?string
  {
    return $this->codePostal;
  }

  public function setCodePostal(string $codePostal): static
  {
    $this->codePostal = $codePostal;
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

  public function getTelephone(): ?string
  {
    return $this->telephone;
  }

  public function setTelephone(?string $telephone): static
  {
    $this->telephone = $telephone;
    return $this;
  }

  public function getEmail(): ?string
  {
    return $this->email;
  }

  public function setEmail(?string $email): static
  {
    $this->email = $email;
    return $this;
  }

  public function getCreatedAt(): ?DateTimeImmutable
  {
    return $this->createdAt;
  }

  public function setCreatedAt(DateTimeImmutable $createdAt): static
  {
    $this->createdAt = $createdAt;
    return $this;
  }

  public function getUpdatedAt(): ?DateTimeImmutable
  {
    return $this->updatedAt;
  }

  public function setUpdatedAt(?DateTimeImmutable $updatedAt): static
  {
    $this->updatedAt = $updatedAt;
    return $this;
  }

  /** @return Collection<int, Appartement> */
  public function getAppartements(): Collection
  {
    return $this->appartements;
  }

  public function addAppartement(Appartement $appartement): static
  {
    if (!$this->appartements->contains($appartement)) {
      $this->appartements->add($appartement);
      $appartement->setLocalisation($this);
    }
    return $this;
  }

  public function removeAppartement(Appartement $appartement): static
  {
    if ($this->appartements->removeElement($appartement) && $appartement->getLocalisation() === $this) {
      $appartement->setLocalisation(null);
    }
    return $this;
  }

  public function __toString(): string
  {
    return $this->ville ?? '';
  }
  public function getImageCouverture(): ?string
  {
    return $this->imageCouverture;
  }

  public function setImageCouverture(?string $imageCouverture): static
  {
    $this->imageCouverture = $imageCouverture;
    return $this;
  }

}