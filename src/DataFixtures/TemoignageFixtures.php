<?php

namespace App\DataFixtures;

use App\Entity\Temoignage;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TemoignageFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $temoignages = [
            ['auteur' => 'Charlene F.', 'contenu' => 'Je recommande vivement. Très belles prestations et super services.', 'note' => 5],
            ['auteur' => 'Aurélie P.', 'contenu' => 'Magnifique cocon où nous avons passé un super séjour ! Propriétaire très agréable.', 'note' => 5],
            ['auteur' => 'Pierre T.', 'contenu' => 'Bel appartement et idéalement situé. Je recommande fortement !', 'note' => 5],
            ['auteur' => 'Mia A.', 'contenu' => 'Magnifique suite où l\'on s\'est sentie directement comme à la maison.', 'note' => 5],
            ['auteur' => 'Bastien C.', 'contenu' => '2ème visite pour ma part. Toujours aussi bien reçu, rien à dire.', 'note' => 5],
            ['auteur' => 'Dominique B.', 'contenu' => 'Logement propre et accessible ! Que du bonheur !', 'note' => 5],
        ];

        foreach ($temoignages as $data) {
            $temoignage = (new Temoignage())
                ->setAuteur($data['auteur'])
                ->setContenu($data['contenu'])
                ->setNote($data['note'])
                ->setCreatedAt(new DateTimeImmutable());

            $manager->persist($temoignage);
        }

        $manager->flush();
    }
}