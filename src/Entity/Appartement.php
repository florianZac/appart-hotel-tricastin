<?php

namespace App\Entity;

use App\Repository\AppartementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppartementRepository::class)]
class Appartement
{
		#[ORM\Id]
		#[ORM\GeneratedValue]
		#[ORM\Column]
		private ?int $id = null;

		#[ORM\Column(length: 100)]
		private ?string $nom = null;

		#[ORM\Column(length: 50)]
		private ?string $slug = null;

		#[ORM\Column(length: 20)]
		private ?string $type = null; // Studio, T2, T2 bis, T4

		#[ORM\Column]
		private ?int $surface = null; // en m²

		#[ORM\Column]
		private ?int $capaciteMin = null;

		#[ORM\Column]
		private ?int $capaciteMax = null;

		#[ORM\Column(type: Types::TEXT)]
		private ?string $description = null;

		#[ORM\Column(type: Types::TEXT, nullable: true)]
		private ?string $equipements = null; // JSON array

		#[ORM\Column(length: 255)]
		private ?string $imagePrincipale = null;

		#[ORM\Column(type: Types::TEXT, nullable: true)]
		private ?string $galerie = null; // JSON array of image paths

		#[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2)]
		private ?string $prixParNuit = null;

		#[ORM\Column]
		private ?bool $actif = true;

		#[ORM\Column]
		private ?int $ordre = 0;

		#[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'appartement')]
		private Collection $reservations;

		#[ORM\ManyToOne(targetEntity: Localisation::class, inversedBy: 'appartements')]
		#[ORM\JoinColumn(nullable: false)]
		private ?Localisation $localisation = null;

		#[ORM\OneToMany(mappedBy: 'appartement', targetEntity: Tarif::class)]
		private Collection $tarifs;

		public function __construct()
		{
				$this->reservations = new ArrayCollection();
				$this->tarifs = new ArrayCollection();
		}

		public function getId(): ?int { return $this->id; }

		public function getNom(): ?string { return $this->nom; }
		public function setNom(string $nom): static { $this->nom = $nom; return $this; }

		public function getSlug(): ?string { return $this->slug; }
		public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

		public function getType(): ?string { return $this->type; }
		public function setType(string $type): static { $this->type = $type; return $this; }

		public function getSurface(): ?int { return $this->surface; }
		public function setSurface(int $surface): static { $this->surface = $surface; return $this; }

		public function getCapaciteMin(): ?int { return $this->capaciteMin; }
		public function setCapaciteMin(int $capaciteMin): static { $this->capaciteMin = $capaciteMin; return $this; }

		public function getCapaciteMax(): ?int { return $this->capaciteMax; }
		public function setCapaciteMax(int $capaciteMax): static { $this->capaciteMax = $capaciteMax; return $this; }

		public function getDescription(): ?string { return $this->description; }
		public function setDescription(string $description): static { $this->description = $description; return $this; }

		public function getEquipements(): ?array
		{
			return $this->equipements ? json_decode($this->equipements, true) : [];
		}
		public function setEquipements(?array $equipements): static
		{
			$this->equipements = $equipements ? json_encode($equipements) : null;
			return $this;
		}

		public function getImagePrincipale(): ?string { return $this->imagePrincipale; }
		public function setImagePrincipale(string $imagePrincipale): static { $this->imagePrincipale = $imagePrincipale; return $this; }

	public function getGalerie(): ?array
	{
		return $this->galerie ? json_decode($this->galerie, true) : [];
	}
	public function setGalerie(?array $galerie): static
	{
		$this->galerie = $galerie ? json_encode($galerie) : null;
		return $this;
	}

	public function getPrixParNuit(): ?string { return $this->prixParNuit; }
	public function setPrixParNuit(string $prixParNuit): static { $this->prixParNuit = $prixParNuit; return $this; }

	public function isActif(): ?bool { return $this->actif; }
	public function setActif(bool $actif): static { $this->actif = $actif; return $this; }

	public function getOrdre(): ?int { return $this->ordre; }
	public function setOrdre(int $ordre): static { $this->ordre = $ordre; return $this; }

	/** @return Collection<int, Reservation> */
	public function getReservations(): Collection { return $this->reservations; }

	public function addReservation(Reservation $reservation): static
	{
		if (!$this->reservations->contains($reservation)) {
			$this->reservations->add($reservation);
			$reservation->setAppartement($this);
		}
		return $this;
	}

	public function __toString(): string { return $this->nom ?? ''; }

	public function getLocalisation(): ?Localisation
	{
		return $this->localisation;
	}

	public function setLocalisation(?Localisation $localisation): static
	{
		$this->localisation = $localisation;
		return $this;
	}

	/**
	 * @return Collection<int, Tarif>
	 */
	public function getTarifs(): Collection
	{
		return $this->tarifs;
	}

	public function addTarif(Tarif $tarif): static
	{
		if (!$this->tarifs->contains($tarif)) {
			$this->tarifs->add($tarif);
			$tarif->setAppartement($this);
		}

		return $this;
	}

	public function removeTarif(Tarif $tarif): static
	{
		if ($this->tarifs->removeElement($tarif)) {
			// côté propriétaire
			if ($tarif->getAppartement() === $this) {
				$tarif->setAppartement(null);
			}
		}

		return $this;
	}

}
