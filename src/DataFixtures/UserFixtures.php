<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
	public const ADMIN_REF  = 'user-admin';
	public const CLIENT_REF = 'user-client';

	public function __construct(private UserPasswordHasherInterface $passwordHasher)
	{
	}

	public function load(ObjectManager $manager): void
	{
		// Admin
		$admin = new User();
		$admin->setNom('Aizac');
		$admin->setPrenom('Florian');
		$admin->setEmail('admin@appart-hotel-tricastin.fr');
		$admin->setTelephone('06 00 00 00 01');
		$admin->setIsActive(true);
		$admin->setRoles(['ROLE_ADMIN']);
		$admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin@2026!'));
		$manager->persist($admin);
		$this->addReference(self::ADMIN_REF, $admin);

		// Client test
		$client = new User();
		$client->setNom('Dupont');
		$client->setPrenom('Marie');
		$client->setEmail('marie.dupont@email.fr');
		$client->setTelephone('06 12 34 56 78');
		$client->setIsActive(true);
		$client->setRoles([]);
		$client->setPassword($this->passwordHasher->hashPassword($client, 'Client@2026!'));
		$manager->persist($client);
		$this->addReference(self::CLIENT_REF, $client);

		// Client 2
		$client2 = new User();
		$client2->setNom('Martin');
		$client2->setPrenom('Jean');
		$client2->setEmail('jean.martin@email.fr');
		$client2->setTelephone('06 98 76 54 32');
		$client2->setIsActive(true);
		$client2->setRoles([]);
		$client2->setPassword($this->passwordHasher->hashPassword($client2, 'Client@2026!'));
		$manager->persist($client2);

		$manager->flush();
	}
}
