<?php

namespace App\Entity;

use App\Repository\TemoignageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TemoignageRepository::class)]
class Temoignage
{
	public const STATUT_EN_ATTENTE = 'en_attente';
	public const STATUT_APPROUVE   = 'approuve';
	public const STATUT_REFUSE     = 'refuse';

	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column(length: 100)]
	private ?string $auteur = null;

	#[ORM\Column(type: Types::TEXT)]
	#[Assert\NotBlank(message: 'Le commentaire est obligatoire.')]
	#[Assert\Length(min: 20, max: 1000, minMessage: 'Le commentaire doit contenir au moins {{ limit }} caractères.')]
	private ?string $contenu = null;

	#[ORM\Column]
	#[Assert\Range(min: 1, max: 5)]
	private ?int $note = 5;

	#[ORM\Column(length: 255, nullable: true)]
	private ?string $avatar = null;

	#[ORM\Column]
	private ?bool $actif = false;

	#[ORM\Column(length: 20)]
	private string $statut = self::STATUT_EN_ATTENTE;

	#[ORM\Column(type: Types::DATETIME_MUTABLE)]
	private ?\DateTimeInterface $createdAt = null;

	#[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
	private ?\DateTimeInterface $validatedAt = null;

	#[ORM\ManyToOne(targetEntity: User::class)]
	#[ORM\JoinColumn(nullable: true)]
	private ?User $user = null;

	#[ORM\ManyToOne(targetEntity: Appartement::class)]
	#[ORM\JoinColumn(nullable: true)]
	private ?Appartement $appartement = null;

	#[ORM\ManyToOne(targetEntity: Reservation::class)]
	#[ORM\JoinColumn(nullable: true)]
	private ?Reservation $reservation = null;

	#[ORM\Column(type: 'boolean')]
	private bool $emailEnvoye = false;

	#[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
	private ?\DateTimeInterface $emailEnvoyeAt = null;

	public function __construct()
	{
		$this->createdAt = new \DateTime();
	}

	public function getId(): ?int { return $this->id; }

	public function getAuteur(): ?string { return $this->auteur; }
	public function setAuteur(string $auteur): static { $this->auteur = $auteur; return $this; }

	public function getContenu(): ?string { return $this->contenu; }
	public function setContenu(string $contenu): static { $this->contenu = $contenu; return $this; }

	public function getNote(): ?int { return $this->note; }
	public function setNote(int $note): static { $this->note = $note; return $this; }

	public function getAvatar(): ?string { return $this->avatar; }
	public function setAvatar(?string $avatar): static { $this->avatar = $avatar; return $this; }

	public function isActif(): ?bool { return $this->actif; }
	public function setActif(bool $actif): static { $this->actif = $actif; return $this; }

	public function getStatut(): string { return $this->statut; }
	public function setStatut(string $statut): static
	{
		$this->statut = $statut;
		$this->actif = ($statut === self::STATUT_APPROUVE);
		return $this;
	}

	public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
	public function setCreatedAt(\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

	public function getValidatedAt(): ?\DateTimeInterface { return $this->validatedAt; }
	public function setValidatedAt(?\DateTimeInterface $validatedAt): static { $this->validatedAt = $validatedAt; return $this; }

	public function getUser(): ?User { return $this->user; }
	public function setUser(?User $user): static { $this->user = $user; return $this; }

	public function getAppartement(): ?Appartement { return $this->appartement; }
	public function setAppartement(?Appartement $appartement): static { $this->appartement = $appartement; return $this; }

	public function getReservation(): ?Reservation { return $this->reservation; }
	public function setReservation(?Reservation $reservation): static { $this->reservation = $reservation; return $this; }

	public function isEmailEnvoye(): bool { return $this->emailEnvoye; }
	public function setEmailEnvoye(bool $emailEnvoye): static { $this->emailEnvoye = $emailEnvoye; return $this; }

	public function getEmailEnvoyeAt(): ?\DateTimeInterface { return $this->emailEnvoyeAt; }
	public function setEmailEnvoyeAt(?\DateTimeInterface $emailEnvoyeAt): static { $this->emailEnvoyeAt = $emailEnvoyeAt; return $this; }

	public function getStatutLabel(): string
	{
		return match ($this->statut) {
			self::STATUT_EN_ATTENTE => 'En attente',
			self::STATUT_APPROUVE  => 'Approuvé',
			self::STATUT_REFUSE    => 'Refusé',
			default                => $this->statut,
		};
	}

	public function getStatutBadgeClass(): string
	{
		return match ($this->statut) {
			self::STATUT_EN_ATTENTE => 'warning',
			self::STATUT_APPROUVE  => 'success',
			self::STATUT_REFUSE    => 'danger',
			default                => 'secondary',
		};
	}
}
