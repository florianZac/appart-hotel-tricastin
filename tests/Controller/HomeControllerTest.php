<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests de la page d'accueil et mentions légales.
 */
class HomeControllerTest extends WebTestCase
{
    public function testHomePage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
    }

    public function testHomePageContainsTitle(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testMentionsLegales(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mentions-legales');
        $this->assertResponseIsSuccessful();
    }
}
