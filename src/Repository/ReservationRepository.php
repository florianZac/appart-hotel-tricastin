<?php

namespace App\Repository;

use App\Entity\Reservation;
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
