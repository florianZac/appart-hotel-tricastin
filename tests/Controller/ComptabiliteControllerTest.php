<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ComptabiliteControllerTest extends WebTestCase
{
    public function testComptabiliteRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/comptabilite');
        $this->assertResponseRedirects();
    }

    public function testComptabiliteWithAdmin(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository(User::class)
            ->findOneBy(['email' => 'admin@appart-hotel-tricastin.fr']);

        if (!$admin) { $this->markTestSkipped('Admin not found.'); }

        $client->loginUser($admin);
        $client->request('GET', '/admin/comptabilite');
        $this->assertResponseIsSuccessful();
    }

    public function testExportCsvRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/comptabilite/export-csv?annee=2026');
        $this->assertResponseRedirects();
    }
}
