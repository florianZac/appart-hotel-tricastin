<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('nom', TextType::class, [
				'label' => 'Nom',
				'attr'  => ['placeholder' => 'Nom de famille', 'class' => 'form-control'],
			])
			->add('prenom', TextType::class, [
				'label' => 'Prénom',
				'attr'  => ['placeholder' => 'Prénom', 'class' => 'form-control'],
			])
			->add('email', EmailType::class, [
				'label' => 'Email',
				'attr'  => ['placeholder' => 'email@exemple.fr', 'class' => 'form-control'],
			])
			->add('telephone', TelType::class, [
				'label'    => 'Téléphone',
				'required' => false,
				'attr'     => ['placeholder' => '06 12 34 56 78', 'class' => 'form-control'],
			])
			->add('plainPassword', RepeatedType::class, [
				'type'            => PasswordType::class,
				'mapped'          => false,
				'first_options'   => [
					'label' => 'Mot de passe',
					'attr'  => ['placeholder' => '8 caractères minimum', 'class' => 'form-control'],
				],
				'second_options'  => [
					'label' => 'Confirmer le mot de passe',
					'attr'  => ['placeholder' => 'Répétez le mot de passe', 'class' => 'form-control'],
				],
				'constraints' => [
					new NotBlank(['message' => 'Veuillez saisir un mot de passe']),
					new Length([
						'min'        => 8,
						'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
						'max'        => 255,
					]),
				],
			])
			->add('agreeTerms', CheckboxType::class, [
				'mapped'      => false,
				'label'       => "J'accepte les conditions générales",
				'constraints' => [
					new IsTrue(['message' => 'Vous devez accepter les conditions générales.']),
				],
			])
		;
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => User::class,
		]);
	}
}
