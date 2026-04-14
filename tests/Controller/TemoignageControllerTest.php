<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TemoignageControllerTest extends WebTestCase
{
    public function testDepotAvisRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/temoignage/nouveau/1');
        $this->assertResponseRedirects();
    }
}
