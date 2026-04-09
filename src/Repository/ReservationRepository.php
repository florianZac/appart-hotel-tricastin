<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Reservation::class);
	}

	/**
	 * Retourne les réservations récentes
	 * @return Reservation[]
	 */
	public function findRecentes(int $limit = 20): array
	{
		return $this->createQueryBuilder('r')
			->orderBy('r.createdAt', 'DESC')
			->setMaxResults($limit)
			->getQuery()
			->getResult();
	}

	/**
	 * Retourne les réservations d'un utilisateur
	 * @return Reservation[]
	 */
	public function findByUser(User $user, int $limit = 50): array
	{
		return $this->createQueryBuilder('r')
			->andWhere('r.user = :user')
			->setParameter('user', $user)
			->orderBy('r.createdAt', 'DESC')
			->setMaxResults($limit)
			->getQuery()
			->getResult();
	}

	/**
	 * Réservations confirmées à venir (pour les rappels)
	 * @return Reservation[]
	 */
	public function findConfirmeesProchainesSemaine(): array
	{
		$now = new \DateTime('today');
		$inOneWeek = (new \DateTime('today'))->modify('+7 days');

		return $this->createQueryBuilder('r')
			->andWhere('r.statut = :statut')
			->andWhere('r.dateArrivee BETWEEN :now AND :inOneWeek')
			->setParameter('statut', Reservation::STATUT_CONFIRMEE)
			->setParameter('now', $now)
			->setParameter('inOneWeek', $inOneWeek)
			->orderBy('r.dateArrivee', 'ASC')
			->getQuery()
			->getResult();
	}

	/**
	 * Retourne les réservations confirmées d'un appartement sur une période
	 * @return Reservation[]
	 */
	public function findConfirmeesParAppartement(int $appartementId, \DateTimeInterface $start, \DateTimeInterface $end): array
	{
		return $this->createQueryBuilder('r')
			->andWhere('r.appartement = :appartId')
			->andWhere('r.statut = :statut')
			->andWhere('r.dateArrivee <= :end')
			->andWhere('r.dateDepart >= :start')
			->setParameter('appartId', $appartementId)
			->setParameter('statut', 'confirmee')
			->setParameter('start', $start)
			->setParameter('end', $end)
			->orderBy('r.dateArrivee', 'ASC')
			->getQuery()
			->getResult();
	}
}
