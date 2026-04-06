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
        'image' => 'Ville/Pont-saint-esprit.webp',
        'adresse' => 'Pont-Saint-Esprit, 30130',
        'codePostal' => '30130',
        'description' => 'Située sur les bords du Rhône, Pont-Saint-Esprit est une charmante ville du Gard offrant un cadre historique et paisible. Nos 3 appartements T2 vous accueillent avec tout le confort nécessaire et des parkings gratuits à proximité.',
        'telephone' => '+33 6 00 00 00 01',
        'email' => 'contact.pont@appart-hotel-tricastin.com',

      ],
      [
        'reference' => self::LOCALISATION_SAINT_PAUL,
        'ville' => 'Saint-Paul-Trois-Châteaux',
        'slug' => 'saint-paul-trois-chateaux',
        'image' => 'Ville/Saint_paul_trois_chateaux.webp',
        'adresse' => 'Saint-Paul-Trois-Châteaux, 26130',
        'codePostal' => '26130',
        'description' => 'Nichée dans la vallée du Rhône, Saint-Paul-Trois-Châteaux est une ville au charme authentique où histoire, culture et douce ambiance provençale se rencontrent. Ancienne cité gallo-romaine, elle invite à la flânerie dans ses ruelles médiévales. Nos 3 appartements avec terrasse et parking privatif vous accueillent au cœur de cette perle de la Drôme Provençale.',
        'telephone' => '+33 6 00 00 00 02',
        'email' => 'contact.saintpaul@appart-hotel-tricastin.com',
      ],
      [
        'reference' => self::LOCALISATION_TULETTE,
        'ville' => 'Tulette',
        'slug' => 'tulette',
        'image' => 'Ville/Tulette.jpg',
        'adresse' => 'Tulette, 26790',
        'codePostal' => '26790',
        'description' => 'Village provençal au milieu des vignes, Tulette offre un cadre calme et ensoleillé, idéal pour découvrir la Drôme Provençale. Venez découvir nos appartements, dont certains avec cour privative ou terrasse, vous garantissent un séjour paisible avec parkings gratuits à proximité.',
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
          ->setCreatedAt(new DateTimeImmutable())
          ->setImageCouverture($data['image']);

      $manager->persist($localisation);
      $this->addReference($data['reference'], $localisation, \App\Entity\Localisation::class);
    }

    $manager->flush();
  }
}