<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ContactControllerTest extends WebTestCase
{
    public function testContactPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/contact');
        $this->assertResponseIsSuccessful();
    }

    public function testContactPageHasForm(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact');
        $this->assertSelectorExists('form');
    }
}
