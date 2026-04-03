<?php

namespace App\Repository;

use App\Entity\Localisation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Localisation>
 *
 * @method Localisation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Localisation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Localisation[]    findAll()
 * @method Localisation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LocalisationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
      parent::__construct($registry, Localisation::class);
    }

    /**
     * Retourne toutes les localisations avec leurs appartements
     * @return Localisation[]
     */
    public function findAllWithAppartements(): array
    {
      return $this->createQueryBuilder('l')
        ->leftJoin('l.appartements', 'a')
        ->addSelect('a')
        ->orderBy('l.ville', 'ASC')
        ->getQuery()
        ->getResult();
    }

    /**
     * Trouve une localisation par son slug
     */
    public function findBySlug(string $slug): ?Localisation
    {
      return $this->createQueryBuilder('l')
        ->andWhere('l.slug = :slug')
        ->setParameter('slug', $slug)
        ->getQuery()
        ->getOneOrNullResult();
    }
}