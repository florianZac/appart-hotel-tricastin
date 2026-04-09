<?php

namespace App\Repository;

use App\Entity\Payment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Payment::class);
	}

	/**
	 * Tous les paiements d'un utilisateur
	 * @return Payment[]
	 */
	public function findByUser(User $user, int $limit = 50): array
	{
		return $this->createQueryBuilder('p')
			->andWhere('p.user = :user')
			->setParameter('user', $user)
			->orderBy('p.createdAt', 'DESC')
			->setMaxResults($limit)
			->getQuery()
			->getResult();
	}

	/**
	 * Paiements en attente (pour l'admin)
	 * @return Payment[]
	 */
	public function findEnAttente(): array
	{
		return $this->createQueryBuilder('p')
			->andWhere('p.statut = :statut')
			->setParameter('statut', Payment::STATUT_EN_ATTENTE)
			->orderBy('p.dateEcheance', 'ASC')
			->getQuery()
			->getResult();
	}

	/**
	 * Paiements en retard (échéance dépassée et non payé)
	 * @return Payment[]
	 */
	public function findEnRetard(): array
	{
		return $this->createQueryBuilder('p')
			->andWhere('p.statut = :statut')
			->andWhere('p.dateEcheance < :now')
			->setParameter('statut', Payment::STATUT_EN_ATTENTE)
			->setParameter('now', new \DateTime('today'))
			->orderBy('p.dateEcheance', 'ASC')
			->getQuery()
			->getResult();
	}

	/**
	 * Tous les paiements récents pour le dashboard admin
	 * @return Payment[]
	 */
	public function findRecents(int $limit = 20): array
	{
		return $this->createQueryBuilder('p')
			->orderBy('p.createdAt', 'DESC')
			->setMaxResults($limit)
			->getQuery()
			->getResult();
	}

	/**
	 * Chiffre d'affaires total sur une période
	 */
	public function getTotalRevenu(\DateTimeInterface $debut, \DateTimeInterface $fin): float
	{
		$result = $this->createQueryBuilder('p')
			->select('SUM(p.montant)')
			->andWhere('p.statut = :statut')
			->andWhere('p.paidAt BETWEEN :debut AND :fin')
			->setParameter('statut', Payment::STATUT_REUSSI)
			->setParameter('debut', $debut)
			->setParameter('fin', $fin)
			->getQuery()
			->getSingleScalarResult();

		return (float) ($result ?? 0);
	}

	/**
	 * Revenus groupés par type
	 * @return array<string, float>
	 */
	public function getRevenusParType(): array
	{
		$results = $this->createQueryBuilder('p')
			->select('p.type, SUM(p.montant) as total')
			->andWhere('p.statut = :statut')
			->setParameter('statut', Payment::STATUT_REUSSI)
			->groupBy('p.type')
			->getQuery()
			->getResult();

		$revenus = [];
		foreach ($results as $row) {
			$revenus[$row['type']] = (float) $row['total'];
		}
		return $revenus;
	}
}
