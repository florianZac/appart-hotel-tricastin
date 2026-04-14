<?php

namespace App\Tests\Form;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ContactTypeTest extends KernelTestCase
{
    public function testFormHasFields(): void
    {
        self::bootKernel();
        $factory = static::getContainer()->get('form.factory');

        $form = $factory->create(ContactType::class);

        $this->assertTrue($form->has('nom'));
        $this->assertTrue($form->has('email'));
        $this->assertTrue($form->has('sujet'));
        $this->assertTrue($form->has('message'));
    }

    public function testSubmitValidData(): void
    {
        self::bootKernel();
        $factory = static::getContainer()->get('form.factory');

        $form = $factory->create(ContactType::class);
        $form->submit([
            'nom' => 'Dupont',
            'email' => 'dupont@email.fr',
            'sujet' => 'Demande info',
            'message' => 'Bonjour, je souhaite des informations.',
        ]);

        $this->assertTrue($form->isSynchronized());
    }
}