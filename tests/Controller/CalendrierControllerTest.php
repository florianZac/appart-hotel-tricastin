<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CalendrierControllerTest extends WebTestCase
{
    public function testApiDisponibilitesAppartementInconnu(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/disponibilites/99999');
        $this->assertResponseStatusCodeSame(404);
    }
}
