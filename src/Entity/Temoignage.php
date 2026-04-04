<?php

namespace App\Entity;

use App\Repository\TemoignageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TemoignageRepository::class)]
class Temoignage
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column(length: 100)]
	private ?string $auteur = null;

	#[ORM\Column(type: Types::TEXT)]
	private ?string $contenu = null;

	#[ORM\Column]
	private ?int $note = 5; // sur 5

	#[ORM\Column(length: 255, nullable: true)]
	private ?string $avatar = null;

	#[ORM\Column]
	private ?bool $actif = true;

	#[ORM\Column(type: Types::DATETIME_MUTABLE)]
	private ?\DateTimeInterface $createdAt = null;

	#[ORM\ManyToOne(targetEntity: User::class)]
	#[ORM\JoinColumn(nullable: true)]
	private ?User $user = null;

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

	public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
	public function setCreatedAt(\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

	public function getUser(): ?User
	{
		return $this->user;
	}

	public function setUser(?User $user): static
	{
		$this->user = $user;
		return $this;
	}

}
