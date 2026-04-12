<?php

namespace App\Form;

use App\Entity\Temoignage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TemoignageType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('note', ChoiceType::class, [
				'label'    => 'Note',
				'choices'  => [
					'★'         => 1,
					'★★'        => 2,
					'★★★'       => 3,
					'★★★★'      => 4,
					'★★★★★'     => 5,
				],
				'expanded' => true,
				'attr'     => ['class' => 'd-flex gap-3'],
			])
			->add('contenu', TextareaType::class, [
				'label' => 'Votre avis',
				'attr'  => [
					'rows'        => 5,
					'placeholder' => 'Décrivez votre expérience (20 caractères minimum)...',
					'class'       => 'form-control',
				],
			]);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => Temoignage::class,
		]);
	}
}
