<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReservationControllerTest extends WebTestCase
{
    public function testReservationPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/reserver');
        $this->assertResponseIsSuccessful();
    }

    public function testReservationPageHasForm(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/reserver');
        $this->assertSelectorExists('form');
    }

    public function testApiAppartementsParLocalisation(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/appartements-par-localisation/999');
        $this->assertResponseStatusCodeSame(404);
    }
}
