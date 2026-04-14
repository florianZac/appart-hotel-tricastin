<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminTemoignageControllerTest extends WebTestCase
{
    public function testTemoignagesRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/temoignages');
        $this->assertResponseRedirects();
    }

    public function testTemoignagesWithAdmin(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository(User::class)
            ->findOneBy(['email' => 'admin@appart-hotel-tricastin.fr']);

        if (!$admin) { $this->markTestSkipped('Admin not found.'); }

        $client->loginUser($admin);
        $client->request('GET', '/admin/temoignages');
        $this->assertResponseIsSuccessful();
    }
}
