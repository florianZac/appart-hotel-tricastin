<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'contact.nom-form',           // clé de traduction pour le label
                'constraints' => [new Assert\NotBlank()],           
                'attr' => [
                    'placeholder' => 'contact.nom_placeholder', // clé de traduction pour le placeholder
                    'class' => 'form-control'
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'contact.email-form',
                'constraints' => [new Assert\NotBlank(), new Assert\Email()],
                'attr' => [ 
                    'placeholder' => 'contact.email_placeholder',
                    'class' => 'form-control'],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'contact.telephone-form',
                'required' => false,
                'attr' => [
                    'placeholder' => 'contact.telephone_placeholder',
                    'class' => 'form-control'
                ],
            ])
            ->add('sujet', TextType::class, [
                'label' => 'contact.sujet-form',
                'constraints' => [new Assert\NotBlank()],
                'attr' => [
                    'placeholder' => 'contact.sujet_placeholder',
                    'class' => 'form-control'
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'contact.message-form',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 10)],
                'attr' => [
                    'placeholder' => 'contact.message_placeholder',
                    'class' => 'form-control',
                    'rows' => 6,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
