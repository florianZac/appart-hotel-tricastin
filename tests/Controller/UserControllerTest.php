<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testUsersListRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/users');
        $this->assertResponseRedirects();
    }

    public function testUsersListWithAdmin(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository(User::class)
            ->findOneBy(['email' => 'admin@appart-hotel-tricastin.fr']);

        if (!$admin) { $this->markTestSkipped('Admin not found.'); }

        $client->loginUser($admin);
        $client->request('GET', '/admin/users');
        $this->assertResponseIsSuccessful();
    }

    public function testProfileEditRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-profil');
        $this->assertResponseRedirects();
    }
}
