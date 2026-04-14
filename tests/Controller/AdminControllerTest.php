<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests fonctionnels des routes admin.
 * Vérifie l'accès protégé et les réponses.
 */
class AdminControllerTest extends WebTestCase
{
    public function testAdminDashboardRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/');

        // Doit rediriger vers le login
        $this->assertResponseRedirects();
        $this->assertStringContainsString('login', $client->getResponse()->headers->get('Location'));
    }

    public function testAdminReservationsRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/reservations');

        $this->assertResponseRedirects();
    }

    public function testAdminCalendrierRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/calendrier');

        $this->assertResponseRedirects();
    }

    public function testAdminComptabiliteRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/comptabilite');

        $this->assertResponseRedirects();
    }

    public function testAdminDashboardWithAdmin(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository(User::class);

        /** @var User|null $admin */
        $admin = $userRepository->findOneBy(['email' => 'admin@appart-hotel-tricastin.fr']);

        if ($admin === null) {
            $this->markTestSkipped('Admin user not found in test database. Load fixtures first.');
        }

        $client->loginUser($admin);
        $client->request('GET', '/admin/');

        $this->assertResponseIsSuccessful();
    }

    public function testAdminReservationsWithAdmin(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository(User::class);

        $admin = $userRepository->findOneBy(['email' => 'admin@appart-hotel-tricastin.fr']);

        if ($admin === null) {
            $this->markTestSkipped('Admin user not found in test database.');
        }

        $client->loginUser($admin);
        $client->request('GET', '/admin/reservations');

        $this->assertResponseIsSuccessful();
    }

    public function testAdminCalendrierWithAdmin(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository(User::class);

        $admin = $userRepository->findOneBy(['email' => 'admin@appart-hotel-tricastin.fr']);

        if ($admin === null) {
            $this->markTestSkipped('Admin user not found in test database.');
        }

        $client->loginUser($admin);
        $client->request('GET', '/admin/calendrier');

        $this->assertResponseIsSuccessful();
    }

    public function testAdminComptabiliteWithAdmin(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository(User::class);

        $admin = $userRepository->findOneBy(['email' => 'admin@appart-hotel-tricastin.fr']);

        if ($admin === null) {
            $this->markTestSkipped('Admin user not found in test database.');
        }

        $client->loginUser($admin);
        $client->request('GET', '/admin/comptabilite');

        $this->assertResponseIsSuccessful();
    }

    public function testClientCannotAccessAdmin(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository(User::class);

        $user = $userRepository->findOneBy(['email' => 'jean.martin@email.fr']);

        if ($user === null) {
            $this->markTestSkipped('Client user not found in test database.');
        }

        $client->loginUser($user);
        $client->request('GET', '/admin/');

        $this->assertResponseStatusCodeSame(403);
    }
}
