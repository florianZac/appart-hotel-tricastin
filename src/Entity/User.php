<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column(length: 100)]
	#[Assert\NotBlank(message: 'Le nom est obligatoire.')]
	#[Assert\Length(min: 2, max: 100, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.')]
	#[Assert\Regex(pattern: '/^[\p{L}\s\-\']+$/u', message: 'Le nom ne doit contenir que des lettres, espaces, tirets ou apostrophes.')]
	private ?string $nom = null;

	#[ORM\Column(length: 100)]
	#[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
	#[Assert\Length(min: 2, max: 100, minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères.')]
	#[Assert\Regex(pattern: '/^[\p{L}\s\-\']+$/u', message: 'Le prénom ne doit contenir que des lettres, espaces, tirets ou apostrophes.')]
	private ?string $prenom = null;

	#[ORM\Column(length: 180)]
	#[Assert\NotBlank(message: 'L\'email est obligatoire.')]
	#[Assert\Email(message: 'L\'adresse email "{{ value }}" n\'est pas valide.')]
	#[Assert\Length(max: 180)]
	private ?string $email = null;

	#[ORM\Column(length: 20, nullable: true)]
	#[Assert\Regex(pattern: '/^(\+33|0)[1-9](\s?\d{2}){4}$/', message: 'Le numéro de téléphone n\'est pas valide (format français attendu).')]
	private ?string $telephone = null;

	/** @var list<string> The user roles */
	#[ORM\Column]
	private array $roles = [];

	/** @var string The hashed password */
	#[ORM\Column]
	private ?string $password = null;

	#[ORM\Column(type: 'boolean')]
	private bool $isActive = true;

	#[ORM\Column(length: 100, nullable: true)]
	private ?string $resetToken = null;

	#[ORM\Column(type: 'datetime_immutable', nullable: true)]
	private ?\DateTimeImmutable $resetTokenExpiresAt = null;

	#[ORM\Column(type: 'datetime_immutable')]
	private ?\DateTimeImmutable $createdAt = null;

	#[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'user')]
	#[ORM\OrderBy(['createdAt' => 'DESC'])]
	private Collection $reservations;

	#[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'user')]
	#[ORM\OrderBy(['createdAt' => 'DESC'])]
	private Collection $payments;

	public function __construct()
	{
		$this->createdAt = new \DateTimeImmutable();
		$this->reservations = new ArrayCollection();
		$this->payments = new ArrayCollection();
	}

	public function getId(): ?int { return $this->id; }

	public function getNom(): ?string { return $this->nom; }
	public function setNom(string $nom): static { $this->nom = $nom; return $this; }

	public function getPrenom(): ?string { return $this->prenom; }
	public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }

	public function getEmail(): ?string { return $this->email; }
	public function setEmail(string $email): static { $this->email = $email; return $this; }

	public function getTelephone(): ?string { return $this->telephone; }
	public function setTelephone(?string $telephone): static { $this->telephone = $telephone; return $this; }

	public function getUserIdentifier(): string
	{
		return (string) $this->email;
	}

	public function getRoles(): array
	{
		$roles = $this->roles;
		$roles[] = 'ROLE_USER';
		return array_unique($roles);
	}

	/** @param list<string> $roles */
	public function setRoles(array $roles): static
	{
		$this->roles = $roles;
		return $this;
	}

	public function getPassword(): ?string { return $this->password; }
	public function setPassword(string $password): static { $this->password = $password; return $this; }

	public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
	public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

	/** @return Collection<int, Reservation> */
	public function getReservations(): Collection { return $this->reservations; }

	/** @return Collection<int, Payment> */
	public function getPayments(): Collection { return $this->payments; }

	public function getFullName(): string
	{
		return $this->prenom . ' ' . $this->nom;
	}

	public function __toString(): string
	{
		return $this->getFullName();
	}

	public function __serialize(): array
	{
		$data = (array) $this;
		$data["\0".self::class."\0password"] = hash('crc32c', $this->password);
		return $data;
	}

	#[\Deprecated]
	public function eraseCredentials(): void
	{
	}
	public function isActive(): bool
	{
		return $this->isActive;
	}

	public function setIsActive(bool $isActive): self
	{
		$this->isActive = $isActive;

		return $this;
	}

	public function getResetToken(): ?string
	{
		return $this->resetToken;
	}

	public function setResetToken(?string $resetToken): self
	{
		$this->resetToken = $resetToken;
		return $this;
	}

	public function getResetTokenExpiresAt(): ?\DateTimeImmutable
	{
		return $this->resetTokenExpiresAt;
	}

	public function setResetTokenExpiresAt(?\DateTimeImmutable $resetTokenExpiresAt): self
	{
		$this->resetTokenExpiresAt = $resetTokenExpiresAt;
		return $this;
	}

	public function isResetTokenValid(): bool
	{
		return $this->resetToken !== null
			&& $this->resetTokenExpiresAt !== null
			&& $this->resetTokenExpiresAt > new \DateTimeImmutable();
	}

}
