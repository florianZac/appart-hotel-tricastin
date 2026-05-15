<?php

namespace App\Repository;

use App\Entity\SeoPage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<SeoPage> */
class SeoPageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SeoPage::class);
    }

    public function findByRoute(string $route): ?SeoPage
    {
        return $this->findOneBy(['route' => $route]);
    }

    /** @return SeoPage[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.cocon', 'c')
            ->addSelect('c')
            ->orderBy('c.nom', 'ASC')
            ->addOrderBy('s.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Pages du même cocon (pour le maillage interne suggéré).
     * @return SeoPage[]
     */
    public function findByCoconExcluding(SeoPage $page): array
    {
        if (!$page->getCocon()) return [];

        return $this->createQueryBuilder('s')
            ->where('s.cocon = :cocon')
            ->andWhere('s.id != :id')
            ->setParameter('cocon', $page->getCocon())
            ->setParameter('id', $page->getId())
            ->getQuery()
            ->getResult();
    }
}
