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
      'nom' => 'Le Sautadet',
      'slug' => 'le-sautadet',
      'type' => 'T2',
      'surface' => 35,
      'capaciteMin' => 1,
      'capaciteMax' => 4,
      'prixParNuit' => '65.00',
      'description' => 'T2 de 35m² situé au rez-de-chaussée. Appartement confortable avec 1 lit double en 160cm et 1 canapé-lit. Cuisine équipée, salle de bain avec douche, Wi-Fi gratuit. Parkings gratuits à proximité.',
      'equipements' => ['Wi-Fi gratuit', 'Lit double 160cm', 'Canapé-lit', 'Cuisine équipée', 'Salle de bain', 'Parking gratuit à proximité', 'Linge de lit', 'Serviettes de bain'],
      'imagePrincipale' => 'Pont_saint_esprit/Pont_Rdc_le-sautadet/le-sautadet.jpg',
      'galerie' => [
        'Pont_saint_esprit/Pont_Rdc_le-sautadet/le-sautadet-chambre.jpg',
        'Pont_saint_esprit/Pont_Rdc_le-sautadet/le-sautadet-cuisine.jpg',
        'Pont_saint_esprit/Pont_Rdc_le-sautadet/le-sautadet-salle_de_bain.jpg',
      ],
      'ordre' => 1,
    ],
    [
      'localisation' => LocalisationFixtures::LOCALISATION_PONT,
      'nom' => 'Le Vignoble',
      'slug' => 'le-vignoble',
      'type' => 'T2',
      'surface' => 40,
      'capaciteMin' => 1,
      'capaciteMax' => 4,
      'prixParNuit' => '70.00',
      'description' => 'T2 de 40m² au 2ème étage. Appartement lumineux avec 1 lit double en 160cm et 1 canapé-lit. Cuisine équipée, salle de bain moderne, Wi-Fi gratuit. Parkings gratuits à proximité.',
      'equipements' => ['Wi-Fi gratuit', 'Lit double 160cm', 'Canapé-lit', 'Cuisine équipée', 'Salle de bain', 'Parking gratuit à proximité', 'Linge de lit', 'Serviettes de bain'],
      'imagePrincipale' => 'Pont_saint_esprit/Pont_2e_le-vignoble/le-vignoble.jpg',
      'galerie' => [
        'Pont_saint_esprit/Pont_2e_le-vignoble/le-vignoble-chambre.jpg',
        'Pont_saint_esprit/Pont_2e_le-vignoble/le-vignoble-cuisine.jpg',
        'Pont_saint_esprit/Pont_2e_le-vignoble/le-vignoble-salle_de_bain.jpg',
      ],
      'ordre' => 2,
    ],
    [
      'localisation' => LocalisationFixtures::LOCALISATION_PONT,
      'nom' => 'La Citadelle',
      'slug' => 'la-citadelle',
      'type' => 'T2',
      'surface' => 35,
      'capaciteMin' => 1,
      'capaciteMax' => 4,
      'prixParNuit' => '65.00',
      'description' => 'T2 de 35m² au 3ème étage. Appartement chaleureux avec 1 lit double en 160cm et 1 canapé-lit. Cuisine équipée, salle de bain, Wi-Fi gratuit. Parkings gratuits à proximité.',
      'equipements' => ['Wi-Fi gratuit', 'Lit double 160cm', 'Canapé-lit', 'Cuisine équipée', 'Salle de bain', 'Parking gratuit à proximité', 'Linge de lit', 'Serviettes de bain'],
      'imagePrincipale' => 'Pont_saint_esprit/Pont_3e_la-citadelle/la-citadelle.jpg',
      'galerie' => [
        'Pont_saint_esprit/Pont_3e_la-citadelle/la-citadelle-salon.jpg',
        'Pont_saint_esprit/Pont_3e_la-citadelle/la-citadelle-chambre.JPG',
        'Pont_saint_esprit/Pont_3e_la-citadelle/la-citadelle-salle_de_bain.jpg',
        'Pont_saint_esprit/Pont_3e_la-citadelle/la-citadelle-salle_de_bain_1.jpg',
      ],
      'ordre' => 3,
    ],

    // ===== TULETTE (6 appartements) =====
    [
      'localisation' => LocalisationFixtures::LOCALISATION_TULETTE,
      'nom' => 'Urban Nest',
      'slug' => 'urban-nest',
      'type' => 'T2',
      'surface' => 42,
      'capaciteMin' => 1,
      'capaciteMax' => 4,
      'prixParNuit' => '65.00',
      'description' => 'T2 de 42m² au style urbain et moderne. 1 lit double en 140cm et 1 canapé-lit. Cuisine équipée, salle de bain, Wi-Fi gratuit. Parkings gratuits à proximité.',
      'equipements' => ['Wi-Fi gratuit', 'Lit double 140cm', 'Canapé-lit', 'Cuisine équipée', 'Salle de bain', 'Parking gratuit à proximité', 'Linge de lit', 'Serviettes de bain'],
      'imagePrincipale' => 'Tulette/Tul_A_Urban-Nest/Urban-Nest.JPG',
      'galerie' => [
        'Tulette/Tul_A_Urban-Nest/Urban-Nest-chambre_1.JPG',
        'Tulette/Tul_A_Urban-Nest/Urban-Nest-chambre_2.JPG',
        'Tulette/Tul_A_Urban-Nest/Urban-Nest-cuisine.JPG',
        'Tulette/Tul_A_Urban-Nest/Urban-Nest-salle_de_bain.JPG',
      ],
      'ordre' => 1,
    ],
    [
      'localisation' => LocalisationFixtures::LOCALISATION_TULETTE,
      'nom' => 'Cocon Blanc',
      'slug' => 'cocon-blanc',
      'type' => 'T3',
      'surface' => 69,
      'capaciteMin' => 1,
      'capaciteMax' => 4,
      'prixParNuit' => '85.00',
      'description' => 'Grand T3 de 69m² avec cour privative. Un véritable cocon de douceur avec 1 lit double en 160cm et 1 canapé-lit. Cuisine équipée, salle de bain spacieuse, Wi-Fi gratuit. Parkings gratuits à proximité.',
      'equipements' => ['Wi-Fi gratuit', 'Lit double 160cm', 'Canapé-lit', 'Cour privative', 'Cuisine équipée', 'Salle de bain', 'Parking gratuit à proximité', 'Linge de lit', 'Serviettes de bain'],
      'imagePrincipale' => 'Tulette/Tul_B_Cocon-Blanc/Cocon-Blanc.JPG',
      'galerie' => [
        'Tulette/Tul_B_Cocon-Blanc/Cocon-Blanc-salon.JPG',
        'Tulette/Tul_B_Cocon-Blanc/Cocon-Blanc-chambre.JPG',
        'Tulette/Tul_B_Cocon-Blanc/Cocon-Blanc-chambre_2.JPG',
        'Tulette/Tul_B_Cocon-Blanc/Cocon-Blanc-salle_de_bain.jpg',
        'Tulette/Tul_B_Cocon-Blanc/Cocon-Blanc-exterieur.jpg',
        'Tulette/Tul_B_Cocon-Blanc/Cocon-Blanc-exterieur_2.jpg',
      ],
      'ordre' => 2,
    ],
    [
      'localisation' => LocalisationFixtures::LOCALISATION_TULETTE,
      'nom' => 'Le Central 17',
      'slug' => 'le-central-17',
      'type' => 'T2',
      'surface' => 44,
      'capaciteMin' => 1,
      'capaciteMax' => 4,
      'prixParNuit' => '68.00',
      'description' => 'T2 de 44m² en plein centre. Appartement idéalement situé avec 1 lit double en 160cm et 1 canapé-lit. Cuisine équipée, salle de bain, Wi-Fi gratuit. Parkings gratuits à proximité.',
      'equipements' => ['Wi-Fi gratuit', 'Lit double 160cm', 'Canapé-lit', 'Cuisine équipée', 'Salle de bain', 'Parking gratuit à proximité', 'Linge de lit', 'Serviettes de bain'],
      'imagePrincipale' => 'Tulette/Tul_C_Le-Central-17/Le-Central-17.jpg',
      'galerie' => [
        'Tulette/Tul_C_Le-Central-17/Le-Central-17-salon.jpg',
        'Tulette/Tul_C_Le-Central-17/Le-Central-17-salon_vue_2.jpg',
        'Tulette/Tul_C_Le-Central-17/Le-Central-17-salle_de_bain.jpg',
      ],
      'ordre' => 3,
    ],
    [
      'localisation' => LocalisationFixtures::LOCALISATION_TULETTE,
      'nom' => "L'Équinoxe",
      'slug' => 'l-equinoxe',
      'type' => 'T2',
      'surface' => 46,
      'capaciteMin' => 1,
      'capaciteMax' => 4,
      'prixParNuit' => '70.00',
      'description' => "T2 de 46m² à l'ambiance chaleureuse. 1 lit double en 160cm et 1 canapé-lit. Cuisine équipée, salle de bain moderne, Wi-Fi gratuit. Parkings gratuits à proximité.",
      'equipements' => ['Wi-Fi gratuit', 'Lit double 160cm', 'Canapé-lit', 'Cuisine équipée', 'Salle de bain', 'Parking gratuit à proximité', 'Linge de lit', 'Serviettes de bain'],
      'imagePrincipale' => "Tulette/Tul_D_L'Equinoxe/L'Equinoxe.JPG",
      'galerie' => [
        "Tulette/Tul_D_L'Equinoxe/L'Equinoxe-salon.JPG",
        "Tulette/Tul_D_L'Equinoxe/L'Equinoxe-chambre.JPG",
        "Tulette/Tul_D_L'Equinoxe/L'Equinoxe-chambre1_1.JPG",
        "Tulette/Tul_D_L'Equinoxe/L'Equinoxe-salle_de_bain.jpg",
        "Tulette/Tul_D_L'Equinoxe/L'Equinoxe-salle_de_bain_1.JPG",
      ],
      'ordre' => 4,
    ],
    [
      'localisation' => LocalisationFixtures::LOCALISATION_TULETTE,
      'nom' => 'Altitude Douce',
      'slug' => 'altitude-douce',
      'type' => 'T2',
      'surface' => 48,
      'capaciteMin' => 1,
      'capaciteMax' => 4,
      'prixParNuit' => '72.00',
      'description' => 'T2 de 48m² avec terrasse. Profitez de votre espace extérieur privatif. 1 lit double en 160cm et 1 canapé-lit. Cuisine équipée, salle de bain, Wi-Fi gratuit. Parkings gratuits à proximité.',
      'equipements' => ['Wi-Fi gratuit', 'Lit double 160cm', 'Canapé-lit', 'Terrasse', 'Cuisine équipée', 'Salle de bain', 'Parking gratuit à proximité', 'Linge de lit', 'Serviettes de bain'],
      'imagePrincipale' => 'Tulette/Tul_E_Altitude-Douce/Altitude-Douce.jpeg',
      'galerie' => [
        'Tulette/Tul_E_Altitude-Douce/Altitude-Douce-salon.jpeg',
        'Tulette/Tul_E_Altitude-Douce/Altitude-Douce-chambre_1.jpeg',
        'Tulette/Tul_E_Altitude-Douce/Altitude-Douce-salle_de_bain.jpeg',
        'Tulette/Tul_E_Altitude-Douce/Altitude-Douce-exterieur.jpg',
        'Tulette/Tul_E_Altitude-Douce/Altitude-Douce-exterieur_2.jpg',
      ],
      'ordre' => 5,
    ],
    [
      'localisation' => LocalisationFixtures::LOCALISATION_TULETTE,
      'nom' => 'Les Remparts',
      'slug' => 'les-remparts',
      'type' => 'T2',
      'surface' => 45,
      'capaciteMin' => 1,
      'capaciteMax' => 4,
      'prixParNuit' => '70.00',
      'description' => 'T2 de 45m² avec terrasse. Appartement au charme provençal avec 1 lit double en 160cm et 1 canapé-lit. Cuisine équipée, salle de bain, Wi-Fi gratuit. Parkings gratuits à proximité.',
      'equipements' => ['Wi-Fi gratuit', 'Lit double 160cm', 'Canapé-lit', 'Terrasse', 'Cuisine équipée', 'Salle de bain', 'Parking gratuit à proximité', 'Linge de lit', 'Serviettes de bain'],
      'imagePrincipale' => 'Tulette/Tul_F_Les-Remparts/Les-Remparts.JPG',
      'galerie' => [
        'Tulette/Tul_F_Les-Remparts/Les-Remparts-salon.JPG',
        'Tulette/Tul_F_Les-Remparts/Les-Remparts-salon_1.JPG',
        'Tulette/Tul_F_Les-Remparts/Les-Remparts-chambre_1.JPG',
        'Tulette/Tul_F_Les-Remparts/Les-Remparts-chambre_1_1.JPG',
        'Tulette/Tul_F_Les-Remparts/Les-Remparts-salle_de_bain.jpg',
        'Tulette/Tul_F_Les-Remparts/Les-Remparts-salle_a_manger.JPG',
        'Tulette/Tul_F_Les-Remparts/Les-Remparts-exterieur.jpg',
      ],
      'ordre' => 6,
    ],

    // ===== SAINT-PAUL-TROIS-CHÂTEAUX (3 appartements) =====
    [
      'localisation' => LocalisationFixtures::LOCALISATION_SAINT_PAUL,
      'nom' => 'La Maison des Saisons',
      'slug' => 'la-maison-des-saisons',
      'type' => 'Studio',
      'surface' => 20,
      'capaciteMin' => 1,
      'capaciteMax' => 2,
      'prixParNuit' => '50.00',
      'description' => 'Studio de 20m² avec terrasse. Idéal pour un séjour en solo ou en couple. 1 canapé-lit confortable. Cuisine équipée, salle de bain, Wi-Fi gratuit. Places de parking privatifs à proximité.',
      'equipements' => ['Wi-Fi gratuit', 'Canapé-lit', 'Terrasse', 'Cuisine équipée', 'Salle de bain', 'Parking privatif à proximité', 'Linge de lit', 'Serviettes de bain'],
      // TODO: Ouvre le dossier SP3_Std_La-maison-des-saisons et mets les vrais noms de fichiers
      'imagePrincipale' => 'Saint_paul_trois_chateaux/SP3_Std_La-maison-des-saisons/La-maison-des-saisons.jpg',
      'galerie' => [],
      'ordre' => 1,
    ],
    [
      'localisation' => LocalisationFixtures::LOCALISATION_SAINT_PAUL,
      'nom' => 'Le Tricastin',
      'slug' => 'le-tricastin',
      'type' => 'T2',
      'surface' => 35,
      'capaciteMin' => 1,
      'capaciteMax' => 4,
      'prixParNuit' => '65.00',
      'description' => 'T2 de 35m² avec terrasse. Appartement confortable avec 1 lit double en 160cm et 1 canapé-lit. Cuisine équipée, salle de bain, Wi-Fi gratuit. Places de parking privatifs à proximité.',
      'equipements' => ['Wi-Fi gratuit', 'Lit double 160cm', 'Canapé-lit', 'Terrasse', 'Cuisine équipée', 'Salle de bain', 'Parking privatif à proximité', 'Linge de lit', 'Serviettes de bain'],
      // TODO:  Une fois l'apartement fini Ouvre le dossier SP3_T2_Le-Tricastin et mets les vrais noms de fichiers
      'imagePrincipale' => 'Saint_paul_trois_chateaux/SP3_T2_Le-Tricastin/Le-Tricastin.jpg',
      'galerie' => [],
      'ordre' => 2,
    ],
    [
      'localisation' => LocalisationFixtures::LOCALISATION_SAINT_PAUL,
      'nom' => 'Le Clos des Champs',
      'slug' => 'le-clos-des-champs',
      'type' => 'T3',
      'surface' => 50,
      'capaciteMin' => 1,
      'capaciteMax' => 6,
      'prixParNuit' => '85.00',
      'description' => 'T3 de 50m² avec terrasse. Spacieux et parfait pour les familles. 1 lit double en 160cm et 2 canapé-lits. Cuisine équipée, salle de bain, Wi-Fi gratuit. Places de parking privatifs à proximité.',
      'equipements' => ['Wi-Fi gratuit', 'Lit double 160cm', '2 Canapé-lits', 'Terrasse', 'Cuisine équipée', 'Salle de bain', 'Parking privatif à proximité', 'Linge de lit', 'Serviettes de bain'],
      'imagePrincipale' => 'Saint_paul_trois_chateaux/SP3_T3_Le-Clos-des-Champs/Le-Clos-des-Champs.jpg',
      'galerie' => [
        'Saint_paul_trois_chateaux/SP3_T3_Le-Clos-des-Champs/Le-Clos-des-Champs-salon.jpg',
        'Saint_paul_trois_chateaux/SP3_T3_Le-Clos-des-Champs/Le-Clos-des-Champs-chambre.jpg',
        'Saint_paul_trois_chateaux/SP3_T3_Le-Clos-des-Champs/Le-Clos-des-Champs-coin_tele.jpg',
        'Saint_paul_trois_chateaux/SP3_T3_Le-Clos-des-Champs/Le-Clos-des-Champs-cuisine.jpg',
        'Saint_paul_trois_chateaux/SP3_T3_Le-Clos-des-Champs/Le-Clos-des-Champs-salle_de_bain.jpg',
        'Saint_paul_trois_chateaux/SP3_T3_Le-Clos-des-Champs/Le-Clos-des-Champs-Extérieur.jpg',
      ],
      'ordre' => 3,
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
        ->setEquipements($data['equipements'])
        ->setGalerie($data['galerie'])
        ->setImagePrincipale($data['imagePrincipale'])
        ->setOrdre($data['ordre'])
        ->setActif(true);

      $manager->persist($appartement);
    }

    $manager->flush();
  }

  public function getDependencies(): array
  {
    return [LocalisationFixtures::class];
  }
}