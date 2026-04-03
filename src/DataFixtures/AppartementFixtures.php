<?php

namespace App\DataFixtures;

use App\Entity\Appartement;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AppartementFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
      $appartements = [
        // ===== PONT-SAINT-ESPRIT (3 appartements) =====
        [
          'localisation' => LocalisationFixtures::LOCALISATION_PONT,
          'nom' => 'Le Rhône',
          'slug' => 'le-rhone',
          'type' => 'T2',
          'surface' => 40,
          'capaciteMin' => 1,
          'capaciteMax' => 4,
          'prixParNuit' => '65.00',
          'description' => 'T2 lumineux de 40m² avec vue dégagée. Salon avec canapé convertible, chambre séparée, cuisine équipée et salle de bain moderne.',
          'imagePrincipale' => 'le-rhone.jpg',
          'ordre' => 1,
        ],
        [
          'localisation' => LocalisationFixtures::LOCALISATION_PONT,
          'nom' => 'Le Pont des Eaux',
          'slug' => 'le-pont-des-eaux',
          'type' => 'Studio',
          'surface' => 25,
          'capaciteMin' => 1,
          'capaciteMax' => 2,
          'prixParNuit' => '50.00',
          'description' => 'Studio cosy de 25m², idéal pour un séjour en solo ou en couple. Coin cuisine, salle de bain avec douche, Wi-Fi inclus.',
          'imagePrincipale' => 'pont-des-eaux.jpg',
          'ordre' => 2,
        ],
        [
          'localisation' => LocalisationFixtures::LOCALISATION_PONT,
          'nom' => 'La Citadelle',
          'slug' => 'la-citadelle',
          'type' => 'T3',
          'surface' => 60,
          'capaciteMin' => 1,
          'capaciteMax' => 6,
          'prixParNuit' => '85.00',
          'description' => 'Grand T3 de 60m² pouvant accueillir jusqu\'à 6 personnes. Deux chambres, salon spacieux, cuisine complète. Parfait pour les familles.',
          'imagePrincipale' => 'la-citadelle.jpg',
          'ordre' => 3,
        ],

        // ===== SAINT-PAUL-TROIS-CHÂTEAUX (3 appartements) =====
        [
          'localisation' => LocalisationFixtures::LOCALISATION_SAINT_PAUL,
          'nom' => 'La Cathédrale',
          'slug' => 'la-cathedrale',
          'type' => 'T2',
          'surface' => 45,
          'capaciteMin' => 1,
          'capaciteMax' => 4,
          'prixParNuit' => '70.00',
          'description' => 'Bel appartement T2 de 45m² au cœur du centre historique. Chambre avec lit 160cm, salon confortable, cuisine toute équipée.',
          'imagePrincipale' => 'la-cathedrale.jpg',
          'ordre' => 1,
        ],
        [
          'localisation' => LocalisationFixtures::LOCALISATION_SAINT_PAUL,
          'nom' => 'Le Tricastin',
          'slug' => 'le-tricastin',
          'type' => 'Studio',
          'surface' => 28,
          'capaciteMin' => 1,
          'capaciteMax' => 2,
          'prixParNuit' => '55.00',
          'description' => 'Studio moderne de 28m² avec décoration soignée. Coin nuit, kitchenette, salle d\'eau. Calme et lumineux.',
          'imagePrincipale' => 'le-tricastin.jpg',
          'ordre' => 2,
        ],
        [
          'localisation' => LocalisationFixtures::LOCALISATION_SAINT_PAUL,
          'nom' => 'Les Remparts',
          'slug' => 'les-remparts',
          'type' => 'T3',
          'surface' => 55,
          'capaciteMin' => 1,
          'capaciteMax' => 5,
          'prixParNuit' => '80.00',
          'description' => 'T3 de 55m² avec charme provençal. Deux chambres, séjour avec canapé, cuisine équipée et terrasse privative.',
          'imagePrincipale' => 'les-remparts.jpg',
          'ordre' => 3,
        ],

        // ===== TULETTE (6 appartements) =====
        [
          'localisation' => LocalisationFixtures::LOCALISATION_TULETTE,
          'nom' => 'La Lavande',
          'slug' => 'la-lavande',
          'type' => 'Studio',
          'surface' => 22,
          'capaciteMin' => 1,
          'capaciteMax' => 2,
          'prixParNuit' => '45.00',
          'description' => 'Petit studio de 22m² au calme, parfait pour une escapade en Drôme provençale. Fonctionnel et confortable.',
          'imagePrincipale' => 'la-lavande.jpg',
          'ordre' => 1,
        ],
        [
          'localisation' => LocalisationFixtures::LOCALISATION_TULETTE,
          'nom' => 'Le Vignoble',
          'slug' => 'le-vignoble',
          'type' => 'T2',
          'surface' => 38,
          'capaciteMin' => 1,
          'capaciteMax' => 4,
          'prixParNuit' => '60.00',
          'description' => 'T2 de 38m² avec vue sur les vignes. Chambre séparée, séjour agréable et cuisine équipée. Ambiance provençale.',
          'imagePrincipale' => 'le-vignoble.jpg',
          'ordre' => 2,
        ],
        [
          'localisation' => LocalisationFixtures::LOCALISATION_TULETTE,
          'nom' => 'L\'Olivier',
          'slug' => 'l-olivier',
          'type' => 'T2',
          'surface' => 42,
          'capaciteMin' => 1,
          'capaciteMax' => 4,
          'prixParNuit' => '65.00',
          'description' => 'T2 chaleureux de 42m² décoré dans un style provençal. Chambre avec lit queen, salon lumineux, cuisine complète.',
          'imagePrincipale' => 'l-olivier.jpg',
          'ordre' => 3,
        ],
        [
          'localisation' => LocalisationFixtures::LOCALISATION_TULETTE,
          'nom' => 'Le Mistral',
          'slug' => 'le-mistral',
          'type' => 'T3',
          'surface' => 55,
          'capaciteMin' => 1,
          'capaciteMax' => 5,
          'prixParNuit' => '75.00',
          'description' => 'T3 de 55m² spacieux et moderne. Deux chambres, salon avec canapé, cuisine ouverte. Idéal pour une famille.',
          'imagePrincipale' => 'le-mistral.jpg',
          'ordre' => 4,
        ],
        [
            'localisation' => LocalisationFixtures::LOCALISATION_TULETTE,
            'nom' => 'La Garrigue',
            'slug' => 'la-garrigue',
            'type' => 'T3',
            'surface' => 65,
            'capaciteMin' => 1,
            'capaciteMax' => 6,
            'prixParNuit' => '85.00',
            'description' => 'Grand T3 de 65m² avec terrasse et jardin privatif. Deux chambres, grand séjour, cuisine toute équipée. Le charme de la Provence.',
            'imagePrincipale' => 'la-garrigue.jpg',
            'ordre' => 5,
        ],
        [
        'localisation' => LocalisationFixtures::LOCALISATION_TULETTE,
        'nom' => 'Le Mas Provençal',
        'slug' => 'le-mas-provencal',
        'type' => 'T4',
        'surface' => 80,
        'capaciteMin' => 1,
        'capaciteMax' => 8,
        'prixParNuit' => '110.00',
        'description' => 'T4 de 80m² pouvant accueillir jusqu\'à 8 personnes. Trois chambres, deux salles de bain, grand séjour et cuisine d\'été. Le nec plus ultra pour les groupes.',
        'imagePrincipale' => 'le-mas-provencal.jpg',
        'ordre' => 6,
        ],
      ];

      foreach ($appartements as $data) {
        $appartement = (new Appartement())
          ->setLocalisation($this->getReference($data['localisation'], \App\Entity\Localisation::class))
          ->setNom($data['nom'])
          ->setSlug($data['slug'])
          ->setType($data['type'])
          ->setSurface($data['surface'])
          ->setCapaciteMin($data['capaciteMin'])
          ->setCapaciteMax($data['capaciteMax'])
          ->setPrixParNuit($data['prixParNuit'])
          ->setDescription($data['description'])
          ->setImagePrincipale($data['imagePrincipale'])
          ->setOrdre($data['ordre'])
          ->setActif(true);
          //->setCreatedAt(new DateTimeImmutable());

        $manager->persist($appartement);
      }

      $manager->flush();
    }

    public function getDependencies(): array
    {
      return [LocalisationFixtures::class];
    }
}