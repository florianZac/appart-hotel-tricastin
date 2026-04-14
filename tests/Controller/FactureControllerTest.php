<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FactureControllerTest extends WebTestCase
{
    public function testAdminFactureRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/reservation/1/facture');
        $this->assertResponseRedirects();
    }

    public function testClientFactureRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/espace-client/reservation/1/facture');
        $this->assertResponseRedirects();
    }
}
