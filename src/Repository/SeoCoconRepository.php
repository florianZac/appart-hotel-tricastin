<?php

namespace App\Repository;

use App\Entity\SeoCocon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<SeoCocon> */
class SeoCoconRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SeoCocon::class);
    }

    /** @return SeoCocon[] */
    public function findAllWithPages(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.pages', 'p')
            ->addSelect('p')
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
