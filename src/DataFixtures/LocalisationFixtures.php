<?php

namespace App\DataFixtures;

use App\Entity\Localisation;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LocalisationFixtures extends Fixture
{
  public const LOCALISATION_PONT = 'localisation-pont-saint-esprit';
  public const LOCALISATION_SAINT_PAUL = 'localisation-saint-paul';
  public const LOCALISATION_TULETTE = 'localisation-tulette';

  public function load(ObjectManager $manager): void
  {
    $localisations = [
      [
        'reference' => self::LOCALISATION_PONT,
        'ville' => 'Pont-Saint-Esprit',
        'slug' => 'pont-saint-esprit',
        'adresse' => '1 Rue Exemple, 30130 Pont-Saint-Esprit',
        'codePostal' => '30130',
        'description' => 'Située sur les bords du Rhône, Pont-Saint-Esprit est une charmante ville du Gard offrant un cadre historique et paisible pour votre séjour.',
        'telephone' => '+33 6 00 00 00 01',
        'email' => 'contact.pont@appart-hotel-tricastin.com',
      ],
      [
        'reference' => self::LOCALISATION_SAINT_PAUL,
        'ville' => 'Saint-Paul-Trois-Châteaux',
        'slug' => 'saint-paul-trois-chateaux',
        'adresse' => '2 Rue Exemple, 26130 Saint-Paul-Trois-Châteaux',
        'codePostal' => '26130',
        'description' => 'Au cœur de la Drôme provençale, Saint-Paul-Trois-Châteaux vous accueille avec son patrimoine médiéval, ses marchés et ses vignobles.',
        'telephone' => '+33 6 00 00 00 02',
        'email' => 'contact.saintpaul@appart-hotel-tricastin.com',
      ],
      [
        'reference' => self::LOCALISATION_TULETTE,
        'ville' => 'Tulette',
        'slug' => 'tulette',
        'adresse' => '3 Rue Exemple, 26790 Tulette',
        'codePostal' => '26790',
        'description' => 'Village provençal au milieu des vignes, Tulette offre un cadre calme et ensoleillé, idéal pour découvrir la Drôme provençale.',
        'telephone' => '+33 6 00 00 00 03',
        'email' => 'contact.tulette@appart-hotel-tricastin.com',
      ],
    ];

    foreach ($localisations as $data) {
        $localisation = (new Localisation())
          ->setVille($data['ville'])
          ->setSlug($data['slug'])
          ->setAdresse($data['adresse'])
          ->setCodePostal($data['codePostal'])
          ->setDescription($data['description'])
          ->setTelephone($data['telephone'])
          ->setEmail($data['email'])
          ->setCreatedAt(new DateTimeImmutable());

        $manager->persist($localisation);
        $this->addReference($data['reference'], $localisation, \App\Entity\Localisation::class);
    }

    $manager->flush();
  }
}