<?php

namespace App\Repository;

use App\Entity\Temoignage;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Temoignage>
 */
class TemoignageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Temoignage::class);
    }

    /**
     * Retourne les témoignages approuvés (affichés sur le site)
     * @return Temoignage[]
     */
    public function findActifs(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.statut = :statut')
            ->setParameter('statut', Temoignage::STATUT_APPROUVE)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les témoignages par statut
     * @return Temoignage[]
     */
    public function findByStatut(string $statut): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un témoignage existe déjà pour une réservation
     */
    public function findByUserAndReservation(User $user, Reservation $reservation): ?Temoignage
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->andWhere('t.reservation = :reservation')
            ->setParameter('user', $user)
            ->setParameter('reservation', $reservation)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne les réservations terminées sans avis pour l'admin
     */
    public function countEnAttente(): int
    {
        return $this->count(['statut' => Temoignage::STATUT_EN_ATTENTE]);
    }
}
