<?php

namespace App\Form;

use App\Entity\Appartement;
use App\Entity\Frais;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FraisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeFrais', ChoiceType::class, [
                'label'   => 'Type de frais',
                'choices' => array_flip(Frais::TYPES_LABELS),
                'attr'    => ['class' => 'form-select'],
            ])
            ->add('libelle', TextType::class, [
                'label' => 'Libellé',
                'attr'  => [
                    'class'       => 'form-control',
                    'placeholder' => 'Ex : Nettoyage de fin de saison Studio Lavande',
                ],
            ])
            ->add('montant', MoneyType::class, [
                'label'    => 'Montant',
                'currency' => 'EUR',
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('periodicite', ChoiceType::class, [
                'label'   => 'Périodicité',
                'choices' => array_flip(Frais::PERIODICITE_LABELS),
                'attr'    => ['class' => 'form-select'],
            ])
            ->add('mois', ChoiceType::class, [
                'label'    => 'Mois concerné',
                'required' => false,
                'choices'  => [
                    '—'          => null,
                    'Janvier'    => 1,  'Février'  => 2,  'Mars'      => 3,
                    'Avril'      => 4,  'Mai'      => 5,  'Juin'      => 6,
                    'Juillet'    => 7,  'Août'     => 8,  'Septembre' => 9,
                    'Octobre'    => 10, 'Novembre' => 11, 'Décembre'  => 12,
                ],
                'attr' => ['class' => 'form-select'],
                'help' => 'Remplir uniquement pour les frais ponctuels.',
            ])
            ->add('annee', IntegerType::class, [
                'label' => 'Année',
                'data'  => (int) date('Y'),
                'attr'  => ['class' => 'form-control', 'min' => 2020, 'max' => 2035],
            ])
            ->add('appartement', EntityType::class, [
                'class'        => Appartement::class,
                'choice_label' => 'nom',
                'label'        => 'Appartement concerné',
                'required'     => false,
                'placeholder'  => '— Frais global (non lié à un appartement) —',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('a')->orderBy('a.nom', 'ASC');
                },
                'attr' => ['class' => 'form-select'],
                'help' => 'Laisser vide pour un frais global (ex : hébergement du site web).',
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description (optionnel)',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'rows' => 3],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Frais::class,
        ]);
    }
}
