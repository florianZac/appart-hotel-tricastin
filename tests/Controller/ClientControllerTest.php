<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels de l'espace client.
 */
class ClientControllerTest extends WebTestCase
{
    public function testClientDashboardRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/espace-client/');

        $this->assertResponseRedirects();
    }

    public function testClientReservationsRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/espace-client/reservations');

        $this->assertResponseRedirects();
    }

    public function testClientPaiementsRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/espace-client/paiements');

        $this->assertResponseRedirects();
    }

    public function testClientDashboardWithUser(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository(User::class);

        $user = $userRepository->findOneBy(['email' => 'jean.martin@email.fr']);

        if ($user === null) {
            $this->markTestSkipped('Client user not found in test database.');
        }

        $client->loginUser($user);
        $client->request('GET', '/espace-client/');

        $this->assertResponseIsSuccessful();
    }

    public function testClientReservationsWithUser(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository(User::class);

        $user = $userRepository->findOneBy(['email' => 'jean.martin@email.fr']);

        if ($user === null) {
            $this->markTestSkipped('Client user not found in test database.');
        }

        $client->loginUser($user);
        $client->request('GET', '/espace-client/reservations');

        $this->assertResponseIsSuccessful();
    }

    public function testClientPaiementsWithUser(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository(User::class);

        $user = $userRepository->findOneBy(['email' => 'jean.martin@email.fr']);

        if ($user === null) {
            $this->markTestSkipped('Client user not found in test database.');
        }

        $client->loginUser($user);
        $client->request('GET', '/espace-client/paiements');

        $this->assertResponseIsSuccessful();
    }
}
