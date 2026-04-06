<?php

namespace App\Repository;

use App\Entity\Disponibilite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DisponibiliteRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Disponibilite::class);
  }

  /**
   * Retourne les disponibilités d'un appartement pour une période
   */
  public function findByAppartementAndPeriode(int $appartementId, \DateTimeInterface $start, \DateTimeInterface $end): array
  {
    return $this->createQueryBuilder('d')
      ->andWhere('d.appartement = :appartId')
      ->andWhere('d.dateDebut <= :end')
      ->andWhere('d.dateFin >= :start')
      ->setParameter('appartId', $appartementId)
      ->setParameter('start', $start)
      ->setParameter('end', $end)
      ->orderBy('d.dateDebut', 'ASC')
      ->getQuery()
      ->getResult();
  }

  /**
   * Vérifie si un appartement est disponible sur une période
   */
  public function isDisponible(int $appartementId, \DateTimeInterface $start, \DateTimeInterface $end): bool
  {
    $conflits = $this->createQueryBuilder('d')
      ->select('COUNT(d.id)')
      ->andWhere('d.appartement = :appartId')
      ->andWhere('d.statut != :disponible')
      ->andWhere('d.dateDebut < :end')
      ->andWhere('d.dateFin > :start')
      ->setParameter('appartId', $appartementId)
      ->setParameter('disponible', Disponibilite::STATUT_DISPONIBLE)
      ->setParameter('start', $start)
      ->setParameter('end', $end)
      ->getQuery()
      ->getSingleScalarResult();

    return $conflits == 0;
  }
}