<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AppartementControllerTest extends WebTestCase
{
    public function testListeAppartements(): void
    {
        $client = static::createClient();
        $client->request('GET', '/les-appartements');
        $this->assertResponseIsSuccessful();
    }

    public function testLocalisationInconnue(): void
    {
        $client = static::createClient();
        $client->request('GET', '/les-appartements/ville-inexistante');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAppartementInconnu(): void
    {
        $client = static::createClient();
        $client->request('GET', '/appartement/appartement-inexistant');
        $this->assertResponseStatusCodeSame(404);
    }
}
