<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TarifControllerTest extends WebTestCase
{
    public function testTarifsRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/tarifs');
        $this->assertResponseRedirects();
    }
}
