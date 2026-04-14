<?php

namespace App\Form;

use App\Entity\Tarif;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TarifType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('saison', TextType::class, [
				'label' => 'Nom de la saison',
				'attr'  => [
					'placeholder' => 'Ex: Été, Hiver, Haute saison, Noël…',
					'class' => 'form-control',
				],
				'constraints' => [
					new Assert\NotBlank(message: 'Le nom de la saison est requis.'),
					new Assert\Length(max: 100),
				],
			])
			->add('dateDebut', DateType::class, [
				'label'  => 'Date de début',
				'widget' => 'single_text',
				'attr'   => ['class' => 'form-control'],
				'constraints' => [
					new Assert\NotBlank(message: 'La date de début est requise.'),
				],
			])
			->add('dateFin', DateType::class, [
				'label'  => 'Date de fin',
				'widget' => 'single_text',
				'attr'   => ['class' => 'form-control'],
				'constraints' => [
					new Assert\NotBlank(message: 'La date de fin est requise.'),
				],
			])
			->add('prixJour', MoneyType::class, [
				'label'    => 'Prix / nuit',
				'currency' => 'EUR',
				'attr'     => ['class' => 'form-control', 'placeholder' => '0.00'],
				'constraints' => [
					new Assert\NotBlank(message: 'Le prix par nuit est requis.'),
					new Assert\Positive(message: 'Le prix doit être positif.'),
				],
			])
			->add('prixSemaine', MoneyType::class, [
				'label'    => 'Prix / semaine',
				'currency' => 'EUR',
				'attr'     => ['class' => 'form-control', 'placeholder' => '0.00'],
				'constraints' => [
					new Assert\NotBlank(message: 'Le prix par semaine est requis.'),
					new Assert\Positive(message: 'Le prix doit être positif.'),
				],
			])
			->add('prixMois', MoneyType::class, [
				'label'    => 'Prix / mois (30 nuits)',
				'currency' => 'EUR',
				'attr'     => ['class' => 'form-control', 'placeholder' => '0.00'],
				'constraints' => [
					new Assert\NotBlank(message: 'Le prix mensuel est requis.'),
					new Assert\Positive(message: 'Le prix doit être positif.'),
				],
			])
		;
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => Tarif::class,
		]);
	}
}
