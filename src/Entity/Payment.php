<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @author      Florian Aizac
 * @description Entité Payment — gère tous les types de paiement via Stripe :
 *              loyer mensuel, caution, frais de dossier, pénalité, maintenance
 */
#[ORM\Entity(repositoryClass: PaymentRepository::class)]
class Payment
{
	// --- Types de paiement ---
	public const TYPE_LOYER         = 'loyer';
	public const TYPE_CAUTION       = 'caution';
	public const TYPE_FRAIS_DOSSIER = 'frais_dossier';
	public const TYPE_PENALITE      = 'penalite';
	public const TYPE_MAINTENANCE   = 'maintenance';
	public const TYPE_RESERVATION   = 'reservation';

	// --- Statuts de paiement ---
	public const STATUT_EN_ATTENTE = 'en_attente';
	public const STATUT_REUSSI     = 'reussi';
	public const STATUT_ECHOUE     = 'echoue';
	public const STATUT_REMBOURSE  = 'rembourse';

	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'payments')]
	#[ORM\JoinColumn(nullable: false)]
	private ?User $user = null;

	#[ORM\ManyToOne(targetEntity: Reservation::class, inversedBy: 'payments')]
	#[ORM\JoinColumn(nullable: true)]
	private ?Reservation $reservation = null;

	#[ORM\ManyToOne(targetEntity: Appartement::class)]
	#[ORM\JoinColumn(nullable: true)]
	private ?Appartement $appartement = null;

	#[ORM\Column(length: 30)]
	private ?string $type = null;

	#[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
	private ?string $montant = null;

	#[ORM\Column(length: 3)]
	private string $devise = 'EUR';

	#[ORM\Column(length: 20)]
	private ?string $statut = self::STATUT_EN_ATTENTE;

	#[ORM\Column(length: 255, nullable: true)]
	private ?string $stripePaymentIntentId = null;

	#[ORM\Column(length: 255, nullable: true)]
	private ?string $stripeSessionId = null;

	#[ORM\Column(length: 255, nullable: true)]
	private ?string $stripeInvoiceId = null;

	#[ORM\Column(type: Types::TEXT, nullable: true)]
	private ?string $description = null;

	#[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
	private ?\DateTimeInterface $dateEcheance = null;

	#[ORM\Column(type: Types::DATETIME_MUTABLE)]
	private ?\DateTimeInterface $createdAt = null;

	#[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
	private ?\DateTimeInterface $paidAt = null;

	public function __construct()
	{
		$this->createdAt = new \DateTime();
	}

	// --- Getters / Setters ---

	public function getId(): ?int { return $this->id; }

	public function getUser(): ?User { return $this->user; }
	public function setUser(?User $user): static { $this->user = $user; return $this; }

	public function getReservation(): ?Reservation { return $this->reservation; }
	public function setReservation(?Reservation $reservation): static { $this->reservation = $reservation; return $this; }

	public function getAppartement(): ?Appartement { return $this->appartement; }
	public function setAppartement(?Appartement $appartement): static { $this->appartement = $appartement; return $this; }

	public function getType(): ?string { return $this->type; }
	public function setType(string $type): static { $this->type = $type; return $this; }

	public function getMontant(): ?string { return $this->montant; }
	public function setMontant(string $montant): static { $this->montant = $montant; return $this; }

	public function getDevise(): string { return $this->devise; }
	public function setDevise(string $devise): static { $this->devise = $devise; return $this; }

	public function getStatut(): ?string { return $this->statut; }
	public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

	public function getStripePaymentIntentId(): ?string { return $this->stripePaymentIntentId; }
	public function setStripePaymentIntentId(?string $id): static { $this->stripePaymentIntentId = $id; return $this; }

	public function getStripeSessionId(): ?string { return $this->stripeSessionId; }
	public function setStripeSessionId(?string $id): static { $this->stripeSessionId = $id; return $this; }

	public function getStripeInvoiceId(): ?string { return $this->stripeInvoiceId; }
	public function setStripeInvoiceId(?string $id): static { $this->stripeInvoiceId = $id; return $this; }

	public function getDescription(): ?string { return $this->description; }
	public function setDescription(?string $description): static { $this->description = $description; return $this; }

	public function getDateEcheance(): ?\DateTimeInterface { return $this->dateEcheance; }
	public function setDateEcheance(?\DateTimeInterface $date): static { $this->dateEcheance = $date; return $this; }

	public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
	public function setCreatedAt(\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

	public function getPaidAt(): ?\DateTimeInterface { return $this->paidAt; }
	public function setPaidAt(?\DateTimeInterface $paidAt): static { $this->paidAt = $paidAt; return $this; }

	// --- Labels et helpers ---

	public function getTypeLabel(): string
	{
		return match ($this->type) {
			self::TYPE_LOYER         => 'Loyer mensuel',
			self::TYPE_CAUTION       => 'Caution / Dépôt de garantie',
			self::TYPE_FRAIS_DOSSIER => 'Frais de dossier',
			self::TYPE_PENALITE      => 'Pénalité / Retard',
			self::TYPE_MAINTENANCE   => 'Maintenance / Charges',
			self::TYPE_RESERVATION   => 'Paiement de réservation',
			default                  => $this->type,
		};
	}

	public function getStatutLabel(): string
	{
		return match ($this->statut) {
			self::STATUT_EN_ATTENTE => 'En attente',
			self::STATUT_REUSSI     => 'Payé',
			self::STATUT_ECHOUE     => 'Échoué',
			self::STATUT_REMBOURSE  => 'Remboursé',
			default                 => $this->statut,
		};
	}

	public function getStatutBadgeClass(): string
	{
		return match ($this->statut) {
			self::STATUT_EN_ATTENTE => 'warning',
			self::STATUT_REUSSI     => 'success',
			self::STATUT_ECHOUE     => 'danger',
			self::STATUT_REMBOURSE  => 'info',
			default                 => 'light',
		};
	}

	public function isEnRetard(): bool
	{
		if ($this->statut !== self::STATUT_EN_ATTENTE || !$this->dateEcheance) {
			return false;
		}
		return $this->dateEcheance < new \DateTime('today');
	}
}
