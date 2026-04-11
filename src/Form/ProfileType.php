<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Formulaire de modification du profil utilisateur.
 * - Tous les utilisateurs : nom, prénom, email, téléphone
 * - Admin uniquement : choix du rôle (via l'option is_admin)
 */
class ProfileType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('nom', TextType::class, [
				'label' => 'Nom',
				'attr'  => [
					'placeholder' => 'Nom de famille',
					'class'       => 'form-control',
					'maxlength'   => 100,
				],
				'constraints' => [
					new NotBlank(['message' => 'Le nom est obligatoire.']),
					new Length(['min' => 2, 'max' => 100]),
					new Regex([
						'pattern' => '/^[\p{L}\s\-\']+$/u',
						'message' => 'Le nom ne doit contenir que des lettres.',
					]),
				],
			])
			->add('prenom', TextType::class, [
				'label' => 'Prénom',
				'attr'  => [
					'placeholder' => 'Prénom',
					'class'       => 'form-control',
					'maxlength'   => 100,
				],
				'constraints' => [
					new NotBlank(['message' => 'Le prénom est obligatoire.']),
					new Length(['min' => 2, 'max' => 100]),
					new Regex([
						'pattern' => '/^[\p{L}\s\-\']+$/u',
						'message' => 'Le prénom ne doit contenir que des lettres.',
					]),
				],
			])
			->add('email', EmailType::class, [
				'label' => 'Email',
				'attr'  => [
					'placeholder' => 'email@exemple.fr',
					'class'       => 'form-control',
					'maxlength'   => 180,
				],
				'constraints' => [
					new NotBlank(['message' => 'L\'email est obligatoire.']),
					new Email(['message' => 'L\'adresse email n\'est pas valide.']),
				],
			])
			->add('telephone', TelType::class, [
				'label'    => 'Téléphone',
				'required' => false,
				'attr'     => [
					'placeholder' => '06 12 34 56 78',
					'class'       => 'form-control',
					'maxlength'   => 20,
				],
			])
			->add('adresse', TextType::class, [
				'label'    => 'Adresse',
				'required' => false,
				'attr'     => [
					'placeholder' => '12 rue de la Paix',
					'class'       => 'form-control',
					'maxlength'   => 255,
				],
				'constraints' => [
					new Length(['max' => 255]),
				],
			])
			->add('ville', TextType::class, [
				'label'    => 'Ville',
				'required' => false,
				'attr'     => [
					'placeholder' => 'Pierrelatte',
					'class'       => 'form-control',
					'maxlength'   => 100,
				],
				'constraints' => [
					new Length(['max' => 100]),
					new Regex([
						'pattern' => '/^[\p{L}\s\-\']+$/u',
						'message' => 'La ville ne doit contenir que des lettres.',
					]),
				],
			])
			->add('codePostal', TextType::class, [
				'label'    => 'Code postal',
				'required' => false,
				'attr'     => [
					'placeholder' => '26700',
					'class'       => 'form-control',
					'maxlength'   => 5,
				],
				'constraints' => [
					new Regex([
						'pattern' => '/^\d{5}$/',
						'message' => 'Le code postal doit contenir 5 chiffres.',
					]),
				],
			]);

		// Seul un admin peut modifier les rôles
		if ($options['is_admin']) {
			$builder->add('roles', ChoiceType::class, [
				'label'    => 'Rôles',
				'choices'  => [
					'Utilisateur'    => 'ROLE_USER',
					'Administrateur' => 'ROLE_ADMIN',
				],
				'multiple' => true,
				'expanded' => true,
				'attr'     => ['class' => 'form-check'],
			]);
		}
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => User::class,
			'is_admin'   => false,
		]);

		$resolver->setAllowedTypes('is_admin', 'bool');
	}
}
