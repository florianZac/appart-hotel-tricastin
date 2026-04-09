<?php

namespace App\Entity;

use App\Repository\DisponibiliteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Représente un blocage MANUEL de dates par l'admin
 * (les réservations sont gérées via l'entité Reservation)
 */
#[ORM\Entity(repositoryClass: DisponibiliteRepository::class)]
class Disponibilite
{
	public const STATUT_DISPONIBLE = 'disponible';
	public const STATUT_RESERVE    = 'reserve';
	public const STATUT_NETTOYAGE  = 'nettoyage';
	public const STATUT_BLOQUE     = 'bloque';

	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\ManyToOne(targetEntity: Appartement::class)]
	#[ORM\JoinColumn(nullable: false)]
	private ?Appartement $appartement = null;

	#[ORM\Column(type: Types::DATE_MUTABLE)]
	private ?\DateTimeInterface $dateDebut = null;

	#[ORM\Column(type: Types::DATE_MUTABLE)]
	private ?\DateTimeInterface $dateFin = null;

	#[ORM\Column(length: 20)]
	private string $statut = self::STATUT_BLOQUE;

	#[ORM\Column(length: 255, nullable: true)]
	private ?string $note = null;

	#[ORM\Column(type: Types::DATETIME_MUTABLE)]
	private ?\DateTimeInterface $createdAt = null;

	public function __construct()
	{
		$this->createdAt = new \DateTime();
	}

	public function getId(): ?int { return $this->id; }

	public function getAppartement(): ?Appartement { return $this->appartement; }
	public function setAppartement(?Appartement $appartement): static { $this->appartement = $appartement; return $this; }

	public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
	public function setDateDebut(\DateTimeInterface $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

	public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
	public function setDateFin(\DateTimeInterface $dateFin): static { $this->dateFin = $dateFin; return $this; }

	public function getStatut(): string { return $this->statut; }
	public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

	public function getNote(): ?string { return $this->note; }
	public function setNote(?string $note): static { $this->note = $note; return $this; }

	public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }

	/**
	 * Label lisible du statut
	 */
	public function getStatutLabel(): string
	{
		return match ($this->statut) {
			self::STATUT_DISPONIBLE => 'Disponible',
			self::STATUT_RESERVE    => 'Réservé',
			self::STATUT_NETTOYAGE  => 'Nettoyage',
			self::STATUT_BLOQUE     => 'Bloqué',
			default                 => $this->statut,
		};
	}

	/**
	 * Couleur FullCalendar selon le statut
	 */
	public function getCouleur(): string
	{
		return match ($this->statut) {
			self::STATUT_DISPONIBLE => '#28a745', // vert
			self::STATUT_RESERVE    => '#dc3545', // rouge
			self::STATUT_NETTOYAGE  => '#6c757d', // gris
			self::STATUT_BLOQUE     => '#fd7e14', // orange
			default                 => '#17a2b8',
		};
	}
}
