<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LocaleControllerTest extends WebTestCase
{
    public function testSwitchToEnglish(): void
    {
        $client = static::createClient();
        $client->request('GET', '/locale/en');
        $this->assertResponseRedirects();
    }

    public function testSwitchToFrench(): void
    {
        $client = static::createClient();
        $client->request('GET', '/locale/fr');
        $this->assertResponseRedirects();
    }
}
