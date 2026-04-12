<?php

namespace App\DataFixtures;

use App\Entity\Frais;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures de démonstration pour les frais.
 * Dépend de AppartementFixtures pour les références aux appartements.
 */
class FraisFixtures extends Fixture implements DependentFixtureInterface
{

  public static function getGroups(): array
  {
    return ['frais'];
  }

  public function getDependencies(): array
  {
    return [
      AppartementFixtures::class,
    ];
  }

  public function load(ObjectManager $manager): void
  {
    $annee = (int) date('Y');

    // ── Frais globaux (non liés à un appartement) ───────────
    $hebergement = (new Frais())
        ->setTypeFrais(Frais::TYPE_HEBERGEMENT_SITE)
        ->setLibelle('Hébergement site web + nom de domaine')
        ->setMontant('120.00')
        ->setPeriodicite(Frais::PERIODICITE_ANNUEL)
        ->setAnnee($annee)
        ->setDescription('OVH - hébergement mutualisé + domaine .fr');
    $manager->persist($hebergement);

    $assuranceGlobale = (new Frais())
        ->setTypeFrais(Frais::TYPE_ASSURANCE)
        ->setLibelle('Assurance responsabilité civile professionnelle')
        ->setMontant('350.00')
        ->setPeriodicite(Frais::PERIODICITE_ANNUEL)
        ->setAnnee($annee);
    $manager->persist($assuranceGlobale);

    // ── Frais par appartement ───────────────────────────────
    // Adapte les références selon tes fixtures existantes.
    // Exemple : $this->getReference('appartement-studio-lavande')
    // Si tu n'utilises pas de références, remplace par null
    // et ajoute les appartements manuellement dans l'admin.

    $appartementRefs = [
        'appartement-1' => 'Studio Lavande',
        'appartement-2' => 'T2 Olivier',
        'appartement-3' => 'T3 Cigale',
    ];

    foreach ($appartementRefs as $ref => $nomAppart) {
      // Nettoyage annuel
      $nettoyage = (new Frais())
        ->setTypeFrais(Frais::TYPE_NETTOYAGE)
        ->setLibelle(sprintf('Nettoyage annuel — %s', $nomAppart))
        ->setMontant('180.00')
        ->setPeriodicite(Frais::PERIODICITE_ANNUEL)
        ->setAnnee($annee)
        ->setDescription('Nettoyage en profondeur fin de saison');

      // Décommente si tu as des références aux appartements :
      // $nettoyage->setAppartement($this->getReference($ref));

      $manager->persist($nettoyage);

      // Réparation ponctuelle (exemple)
      if ($ref === 'appartement-1') {
        $reparation = (new Frais())
          ->setTypeFrais(Frais::TYPE_REPARATION)
          ->setLibelle(sprintf('Remplacement chauffe-eau — %s', $nomAppart))
          ->setMontant('450.00')
          ->setPeriodicite(Frais::PERIODICITE_PONCTUEL)
          ->setMois(3) // Mars
          ->setAnnee($annee)
          ->setDescription('Remplacement urgent suite à fuite');

        // $reparation->setAppartement($this->getReference($ref));
        $manager->persist($reparation);
      }
    }

    // Taxe de séjour mensuelle (exemple)
    $taxeSejour = (new Frais())
      ->setTypeFrais(Frais::TYPE_TAXE_SEJOUR)
      ->setLibelle('Taxe de séjour — forfait mensuel')
      ->setMontant('25.00')
      ->setPeriodicite(Frais::PERIODICITE_MENSUEL)
      ->setAnnee($annee)
      ->setDescription('Reversement mensuel à la collectivité');
    $manager->persist($taxeSejour);

    $manager->flush();
  }
}
