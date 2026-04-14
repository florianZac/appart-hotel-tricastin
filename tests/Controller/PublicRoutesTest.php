<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels des routes publiques.
 * Vérifie que les pages répondent avec le bon code HTTP.
 */
class PublicRoutesTest extends WebTestCase
{
    public function testHomePage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testAppartementsPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/les-appartements');

        $this->assertResponseIsSuccessful();
    }

    public function testContactPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/contact');

        $this->assertResponseIsSuccessful();
    }

    public function testMentionsLegalesPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mentions-legales');

        $this->assertResponseIsSuccessful();
    }

    public function testReservationPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/reserver');

        $this->assertResponseIsSuccessful();
    }

    public function testLoginPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
    }

    public function testRegisterPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
    }

    public function test404Page(): void
    {
        $client = static::createClient();
        $client->request('GET', '/page-qui-nexiste-pas');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testLocaleSwitch(): void
    {
        $client = static::createClient();
        $client->request('GET', '/locale/en');

        // Doit rediriger
        $this->assertResponseRedirects();
    }
}
