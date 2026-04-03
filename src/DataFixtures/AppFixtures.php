<?php

namespace App\DataFixtures;

use App\Entity\Appartement;
use App\Entity\Temoignage;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // =============================================
        // APPARTEMENTS
        // =============================================
        $appartements = [
            [
                'nom' => "L'Impérial",
                'slug' => 'imperial',
                'type' => 'Studio',
                'surface' => 20,
                'capaciteMin' => 1,
                'capaciteMax' => 4,
                'prixParNuit' => '55.00',
                'description' => "Studio cosy de 20m² idéalement situé en plein cœur de Poitiers. Parfait pour un séjour en solo ou en couple, L'Impérial vous offre tout le confort nécessaire : lit double, coin cuisine équipé, salle de bain avec douche, Wi-Fi gratuit. Décoration soignée et ambiance chaleureuse.",
                'equipements' => ['Wi-Fi gratuit', 'Lit double', 'Cuisine équipée', 'Douche', 'TV écran plat', 'Linge de lit', 'Serviettes de bain', 'Micro-ondes', 'Réfrigérateur', 'Plaques de cuisson'],
                'imagePrincipale' => 'imperial.jpg',
                'ordre' => 1,
            ],
            [
                'nom' => 'La Suite',
                'slug' => 'suite',
                'type' => 'T2',
                'surface' => 40,
                'capaciteMin' => 1,
                'capaciteMax' => 4,
                'prixParNuit' => '75.00',
                'description' => "Appartement T2 de 40m² avec chambre séparée. La Suite vous accueille dans un cadre élégant avec un salon spacieux et une chambre confortable. Cuisine entièrement équipée, salle de bain moderne. Idéal pour un couple ou une petite famille souhaitant profiter de Poitiers.",
                'equipements' => ['Wi-Fi gratuit', 'Chambre séparée', 'Lit double 160cm', 'Canapé-lit', 'Cuisine complète', 'Lave-linge', 'TV écran plat', 'Salle de bain', 'Linge de lit', 'Serviettes de bain'],
                'imagePrincipale' => 'suite.jpg',
                'ordre' => 2,
            ],
            [
                'nom' => "L'Équinoxe",
                'slug' => 'equinoxe',
                'type' => 'T2 bis',
                'surface' => 50,
                'capaciteMin' => 1,
                'capaciteMax' => 4,
                'prixParNuit' => '85.00',
                'description' => "T2 bis de 50m² décoré avec goût. L'Équinoxe propose un vaste séjour lumineux, une chambre avec lit 160cm, un espace bureau et une cuisine tout équipée. L'appartement dispose d'une belle hauteur sous plafond et de grandes fenêtres offrant une lumière naturelle agréable.",
                'equipements' => ['Wi-Fi gratuit', 'Chambre séparée', 'Lit 160cm', 'Espace bureau', 'Cuisine équipée', 'Lave-linge', 'Lave-vaisselle', 'TV écran plat', 'Fer à repasser', 'Linge fourni'],
                'imagePrincipale' => 'equinoxe.jpg',
                'ordre' => 3,
            ],
            [
                'nom' => 'Le Solstice',
                'slug' => 'solstice',
                'type' => 'T2',
                'surface' => 60,
                'capaciteMin' => 1,
                'capaciteMax' => 4,
                'prixParNuit' => '90.00',
                'description' => "Grand T2 de 60m² au design contemporain. Le Solstice offre des volumes généreux avec un séjour spacieux, une chambre confortable et une cuisine ouverte entièrement équipée. Parfait pour les séjours prolongés, cet appartement vous fera vous sentir comme chez vous.",
                'equipements' => ['Wi-Fi gratuit', 'Chambre séparée', 'Lit 160cm', 'Canapé convertible', 'Cuisine ouverte', 'Lave-linge', 'Lave-vaisselle', 'TV 4K', 'Climatisation', 'Linge fourni'],
                'imagePrincipale' => 'solstice.jpg',
                'ordre' => 4,
            ],
            [
                'nom' => "L'Atlantide",
                'slug' => 'atlantide',
                'type' => 'T4',
                'surface' => 85,
                'capaciteMin' => 1,
                'capaciteMax' => 8,
                'prixParNuit' => '120.00',
                'description' => "Spacieux T4 de 85m² pouvant accueillir jusqu'à 8 personnes. L'Atlantide est idéal pour les familles ou les groupes. Trois chambres, deux salles de bain, un grand séjour et une cuisine complète. Décoration moderne et chaleureuse pour un séjour inoubliable à Poitiers.",
                'equipements' => ['Wi-Fi gratuit', '3 chambres', 'Lits doubles', 'Lits simples', '2 salles de bain', 'Cuisine complète', 'Lave-linge', 'Lave-vaisselle', 'TV écran plat', 'Linge fourni', 'Parking (en option)'],
                'imagePrincipale' => 'atlantide.jpg',
                'ordre' => 5,
            ],
            [
                'nom' => 'Le Neptune',
                'slug' => 'neptune',
                'type' => 'T4',
                'surface' => 85,
                'capaciteMin' => 1,
                'capaciteMax' => 8,
                'prixParNuit' => '120.00',
                'description' => "Second T4 de 85m², Le Neptune est le jumeau de L'Atlantide avec sa propre personnalité. Trois chambres spacieuses, deux salles de bain, un séjour convivial et une cuisine tout équipée. Parfait pour les grands groupes souhaitant explorer Poitiers et le Futuroscope.",
                'equipements' => ['Wi-Fi gratuit', '3 chambres', 'Lits doubles', 'Lits simples', '2 salles de bain', 'Cuisine complète', 'Lave-linge', 'Lave-vaisselle', 'TV écran plat', 'Linge fourni', 'Parking (en option)'],
                'imagePrincipale' => 'neptune.jpg',
                'ordre' => 6,
            ],
        ];

        foreach ($appartements as $data) {
            $appart = new Appartement();
            $appart->setNom($data['nom'])
                ->setSlug($data['slug'])
                ->setType($data['type'])
                ->setSurface($data['surface'])
                ->setCapaciteMin($data['capaciteMin'])
                ->setCapaciteMax($data['capaciteMax'])
                ->setPrixParNuit($data['prixParNuit'])
                ->setDescription($data['description'])
                ->setEquipements($data['equipements'])
                ->setImagePrincipale($data['imagePrincipale'])
                ->setOrdre($data['ordre']);

            $manager->persist($appart);
        }

        // =============================================
        // TÉMOIGNAGES
        // =============================================
        $temoignages = [
            [
                'auteur' => 'Charlene F.',
                'contenu' => 'Je recommande vivement. Très belles prestations et super services. Des proprios aux soins de leurs hôtes. Au cœur de Poitiers vous avez toutes les commodités à proximité.',
                'note' => 5,
            ],
            [
                'auteur' => 'Aurélie P.',
                'contenu' => 'Nous reviendrons avec plaisir dans ce magnifique cocon où nous avons passé un super séjour ! Proche de la Gare, propriétaire très agréable et logement très bien équipé.',
                'note' => 5,
            ],
            [
                'auteur' => 'Pierre T.',
                'contenu' => 'Bel appartement et idéalement situé. Je recommande fortement !',
                'note' => 5,
            ],
            [
                'auteur' => 'Mia A.',
                'contenu' => 'Magnifique suite où l\'on s\'est sentie directement comme à la maison. Merci également pour les chocolats ! Je reviendrais sans hésiter.',
                'note' => 5,
            ],
            [
                'auteur' => 'Bastien C.',
                'contenu' => '2ème visite pour ma part. Toujours aussi bien reçu, rien à dire. Point de chute idéal.',
                'note' => 5,
            ],
            [
                'auteur' => 'Dominique B.',
                'contenu' => 'Logement propre et accessible ! Que du bonheur ! Je recommande vivement !',
                'note' => 5,
            ],
        ];

        foreach ($temoignages as $data) {
            $temoignage = new Temoignage();
            $temoignage->setAuteur($data['auteur'])
                ->setContenu($data['contenu'])
                ->setNote($data['note']);

            $manager->persist($temoignage);
        }

        $manager->flush();
    }
}
