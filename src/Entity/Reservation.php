<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
	public const STATUT_EN_ATTENTE = 'en_attente';
	public const STATUT_CONFIRMEE = 'confirmee';
	public const STATUT_ANNULEE = 'annulee';
	public const STATUT_TERMINEE = 'terminee';

	public const PAIEMENT_NON_PAYE = 'non_paye';
	public const PAIEMENT_ACOMPTE = 'acompte_paye';
	public const PAIEMENT_COMPLET = 'paye';

	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\ManyToOne(targetEntity: Appartement::class, inversedBy: 'reservations')]
	#[ORM\JoinColumn(nullable: false)]
	private ?Appartement $appartement = null;

	#[ORM\Column(length: 100)]
	#[Assert\NotBlank]
	private ?string $nom = null;

	#[ORM\Column(length: 100)]
	#[Assert\NotBlank]
	private ?string $prenom = null;

	#[ORM\Column(length: 180)]
	#[Assert\NotBlank]
	#[Assert\Email]
	private ?string $email = null;

	#[ORM\Column(length: 20)]
	#[Assert\NotBlank]
	private ?string $telephone = null;

	#[ORM\Column(type: Types::DATE_MUTABLE)]
	#[Assert\NotBlank]
	private ?\DateTimeInterface $dateArrivee = null;

	#[ORM\Column(type: Types::DATE_MUTABLE)]
	#[Assert\NotBlank]
	private ?\DateTimeInterface $dateDepart = null;

	#[ORM\Column]
	#[Assert\NotBlank(message: 'Veuillez indiquer le nombre de personnes.')]
	#[Assert\Range(min: 1, max: 8)]
	private ?int $nombrePersonnes = null;

	#[ORM\Column(type: Types::TEXT, nullable: true)]
	private ?string $message = null;

	#[ORM\Column(length: 20)]
	private ?string $statut = self::STATUT_EN_ATTENTE;

	#[ORM\Column(length: 20)]
	private ?string $paiementStatut = self::PAIEMENT_NON_PAYE;

	#[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
	private ?string $montantTotal = null;

	#[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
	private ?string $montantCaution = null;

	#[ORM\Column(type: Types::DATETIME_MUTABLE)]
	private ?\DateTimeInterface $createdAt = null;

	#[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reservations')]
	#[ORM\JoinColumn(nullable: true)]
	private ?User $user = null;

	#[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'reservation')]
	#[ORM\OrderBy(['createdAt' => 'DESC'])]
	private Collection $payments;

	#[ORM\Column(length: 255, nullable: true)]
	private ?string $stripeSessionId = null;

	#[ORM\Column(type: 'boolean')]
	private bool $avisEmailEnvoye = false;

	#[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
	private ?\DateTimeInterface $avisEmailEnvoyeAt = null;

	#[ORM\Column(length: 50, nullable: true)]
	private ?string $numeroFacture = null;

	public function __construct()
	{
		$this->createdAt = new \DateTime();
		$this->payments = new ArrayCollection();
	}

	public function getId(): ?int { return $this->id; }

	public function getAppartement(): ?Appartement { return $this->appartement; }
	public function setAppartement(?Appartement $appartement): static { $this->appartement = $appartement; return $this; }

	public function getNom(): ?string { return $this->nom; }
	public function setNom(string $nom): static { $this->nom = $nom; return $this; }

	public function getPrenom(): ?string { return $this->prenom; }
	public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }

	public function getEmail(): ?string { return $this->email; }
	public function setEmail(string $email): static { $this->email = $email; return $this; }

	public function getTelephone(): ?string { return $this->telephone; }
	public function setTelephone(string $telephone): static { $this->telephone = $telephone; return $this; }

	public function getDateArrivee(): ?\DateTimeInterface { return $this->dateArrivee; }
	public function setDateArrivee(\DateTimeInterface $dateArrivee): static { $this->dateArrivee = $dateArrivee; return $this; }

	public function getDateDepart(): ?\DateTimeInterface { return $this->dateDepart; }
	public function setDateDepart(\DateTimeInterface $dateDepart): static { $this->dateDepart = $dateDepart; return $this; }

	public function getNombrePersonnes(): ?int { return $this->nombrePersonnes; }
	public function setNombrePersonnes(int $nombrePersonnes): static { $this->nombrePersonnes = $nombrePersonnes; return $this; }

	public function getMessage(): ?string { return $this->message; }
	public function setMessage(?string $message): static { $this->message = $message; return $this; }

	public function getStatut(): ?string { return $this->statut; }
	public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

	public function getPaiementStatut(): ?string { return $this->paiementStatut; }
	public function setPaiementStatut(string $paiementStatut): static { $this->paiementStatut = $paiementStatut; return $this; }

	public function getMontantTotal(): ?string { return $this->montantTotal; }
	public function setMontantTotal(?string $montantTotal): static { $this->montantTotal = $montantTotal; return $this; }

	public function getMontantCaution(): ?string { return $this->montantCaution; }
	public function setMontantCaution(?string $montantCaution): static { $this->montantCaution = $montantCaution; return $this; }

	public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
	public function setCreatedAt(\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

	public function getUser(): ?User { return $this->user; }
	public function setUser(?User $user): static { $this->user = $user; return $this; }

	/** @return Collection<int, Payment> */
	public function getPayments(): Collection { return $this->payments; }

	public function getStripeSessionId(): ?string { return $this->stripeSessionId; }
	public function setStripeSessionId(?string $stripeSessionId): static { $this->stripeSessionId = $stripeSessionId; return $this; }

	public function isAvisEmailEnvoye(): bool { return $this->avisEmailEnvoye; }
	public function setAvisEmailEnvoye(bool $avisEmailEnvoye): static { $this->avisEmailEnvoye = $avisEmailEnvoye; return $this; }

	public function getAvisEmailEnvoyeAt(): ?\DateTimeInterface { return $this->avisEmailEnvoyeAt; }
	public function setAvisEmailEnvoyeAt(?\DateTimeInterface $avisEmailEnvoyeAt): static { $this->avisEmailEnvoyeAt = $avisEmailEnvoyeAt; return $this; }

	public function getNumeroFacture(): ?string
	{
		return $this->numeroFacture;
	}

	public function setNumeroFacture(?string $numeroFacture): static
	{
		$this->numeroFacture = $numeroFacture;
		return $this;
	}
	/**
	 * Calcule le nombre de nuits
	 */
	public function getNombreNuits(): int
	{
		if ($this->dateArrivee && $this->dateDepart) {
			return (int) $this->dateDepart->diff($this->dateArrivee)->days;
		}
		return 0;
	}

	/**
	 * Calcule le montant total basé sur le prix par nuit
	 */
	public function calculerMontantTotal(): string
	{
		if ($this->appartement && $this->appartement->getPrixParNuit()) {
			$total = $this->getNombreNuits() * (float) $this->appartement->getPrixParNuit();
			$this->montantTotal = number_format($total, 2, '.', '');
		}
		return $this->montantTotal ?? '0.00';
	}

	/**
	 * Retourne le total payé sur cette réservation
	 */
	public function getTotalPaye(): float
	{
		$total = 0;
		foreach ($this->payments as $payment) {
			if ($payment->getStatut() === Payment::STATUT_REUSSI) {
				$total += (float) $payment->getMontant();
			}
		}
		return $total;
	}

	/**
	 * Retourne le solde restant à payer
	 */
	public function getSoldeRestant(): float
	{
		return (float) ($this->montantTotal ?? 0) - $this->getTotalPaye();
	}

	/**
	 * Label lisible du statut
	 */
	public function getStatutLabel(): string
	{
		return match ($this->statut) {
			self::STATUT_EN_ATTENTE => 'En attente',
			self::STATUT_CONFIRMEE  => 'Confirmée',
			self::STATUT_ANNULEE    => 'Annulée',
			self::STATUT_TERMINEE   => 'Terminée',
			default                 => $this->statut,
		};
	}

	/**
	 * Couleur Bootstrap pour le badge du statut
	 */
	public function getStatutBadgeClass(): string
	{
		return match ($this->statut) {
			self::STATUT_EN_ATTENTE => 'warning',
			self::STATUT_CONFIRMEE  => 'success',
			self::STATUT_ANNULEE    => 'danger',
			self::STATUT_TERMINEE   => 'secondary',
			default                 => 'light',
		};
	}
}
