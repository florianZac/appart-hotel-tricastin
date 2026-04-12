<?php

namespace App\Repository;

use App\Entity\Frais;
use App\Entity\Appartement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Frais>
 */
class FraisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Frais::class);
    }

    /**
     * Récupère tous les frais d'une année donnée,
     * optionnellement filtrés par appartement.
     *
     * @return Frais[]
     */
    public function findByAnnee(int $annee, ?Appartement $appartement = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->where('f.annee = :annee')
            ->setParameter('annee', $annee)
            ->orderBy('f.mois', 'ASC')
            ->addOrderBy('f.typeFrais', 'ASC');

        if ($appartement !== null) {
            $qb->andWhere('f.appartement = :appart OR f.appartement IS NULL')
               ->setParameter('appart', $appartement);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Calcule le total des frais pour une année,
     * ventilé par mois (1-12) + frais annuels (clé 0).
     *
     * @return array<int, float> — clé = mois (0 = annuel), valeur = montant total
     */
    public function getTotauxParMois(int $annee, ?Appartement $appartement = null): array
    {
        $fraisList = $this->findByAnnee($annee, $appartement);

        $totaux = array_fill(0, 13, 0.0); // index 0 = annuel, 1-12 = mois

        foreach ($fraisList as $frais) {
            $montant = (float) $frais->getMontant();

            match ($frais->getPeriodicite()) {
                Frais::PERIODICITE_ANNUEL => $totaux[0] += $montant,

                Frais::PERIODICITE_MENSUEL => array_walk(
                    $totaux,
                    function (&$val, $key) use ($montant) {
                        if ($key >= 1 && $key <= 12) {
                            $val += $montant;
                        }
                    }
                ),

                Frais::PERIODICITE_PONCTUEL => $totaux[$frais->getMois() ?? 0] += $montant,

                default => null,
            };
        }

        return $totaux;
    }
}
