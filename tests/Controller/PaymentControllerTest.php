<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaymentControllerTest extends WebTestCase
{
    public function testPaymentCheckoutRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('POST', '/paiement/reservation/1');
        $this->assertResponseRedirects();
    }

    public function testPaymentSuccessRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/paiement/succes');
        $this->assertResponseRedirects();
    }

    public function testPaymentCancelRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/paiement/annule/1');
        $this->assertResponseRedirects();
    }
}
