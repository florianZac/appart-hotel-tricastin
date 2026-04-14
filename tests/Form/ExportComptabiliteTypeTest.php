<?php

namespace App\Tests\Form;

use App\Form\ExportComptabiliteType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ExportComptabiliteTypeTest extends KernelTestCase
{
    public function testFormHasFields(): void
    {
        self::bootKernel();
        $factory = static::getContainer()->get('form.factory');

        $form = $factory->create(ExportComptabiliteType::class);

        $this->assertTrue($form->has('annee'));
        $this->assertTrue($form->has('appartement'));
    }

    public function testSubmitValidData(): void
    {
        self::bootKernel();
        $factory = static::getContainer()->get('form.factory');

        $form = $factory->create(ExportComptabiliteType::class);
        $form->submit(['annee' => 2026, 'appartement' => null]);

        $this->assertTrue($form->isSynchronized());
    }

    public function testMethodIsGet(): void
    {
        self::bootKernel();
        $factory = static::getContainer()->get('form.factory');

        $form = $factory->create(ExportComptabiliteType::class);
        $this->assertSame('GET', $form->getConfig()->getMethod());
    }
}