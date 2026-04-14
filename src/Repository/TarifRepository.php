<?php

namespace App\Repository;

use App\Entity\Tarif;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TarifRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Tarif::class);
	}

	/**
	 * Trouve le tarif applicable pour un appartement à une date donnée.
	 */
	public function findTarifForDate(int $appartementId, \DateTimeInterface $date): ?Tarif
	{
		return $this->createQueryBuilder('t')
			->andWhere('t.appartement = :appartId')
			->andWhere('t.dateDebut <= :date')
			->andWhere('t.dateFin >= :date')
			->setParameter('appartId', $appartementId)
			->setParameter('date', $date)
			->orderBy('t.id', 'DESC')
			->setMaxResults(1)
			->getQuery()
			->getOneOrNullResult();
	}

	/**
	 * Retourne tous les tarifs d'un appartement, triés par date de début.
	 */
	public function findByAppartement(int $appartementId): array
	{
		return $this->createQueryBuilder('t')
			->andWhere('t.appartement = :appartId')
			->setParameter('appartId', $appartementId)
			->orderBy('t.dateDebut', 'ASC')
			->getQuery()
			->getResult();
	}

	/**
	 * Vérifie si une saison chevauche une autre pour le même appartement.
	 */
	public function findChevauchements(
		int $appartementId,
		\DateTimeInterface $dateDebut,
		\DateTimeInterface $dateFin,
		?int $excludeId = null
	): array {
		$qb = $this->createQueryBuilder('t')
			->andWhere('t.appartement = :appartId')
			->andWhere('t.dateDebut <= :fin')
			->andWhere('t.dateFin >= :debut')
			->setParameter('appartId', $appartementId)
			->setParameter('debut', $dateDebut)
			->setParameter('fin', $dateFin);

		if ($excludeId) {
			$qb->andWhere('t.id != :excludeId')
				->setParameter('excludeId', $excludeId);
		}

		return $qb->getQuery()->getResult();
	}

	/**
	 * Retourne les tarifs qui couvrent une période donnée.
	 */
	public function findTarifsForPeriode(
		int $appartementId,
		\DateTimeInterface $start,
		\DateTimeInterface $end
	): array {
		return $this->createQueryBuilder('t')
			->andWhere('t.appartement = :appartId')
			->andWhere('t.dateDebut <= :end')
			->andWhere('t.dateFin >= :start')
			->setParameter('appartId', $appartementId)
			->setParameter('start', $start)
			->setParameter('end', $end)
			->orderBy('t.dateDebut', 'ASC')
			->getQuery()
			->getResult();
	}
}
