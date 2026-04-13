<?php

namespace App\Form;

use App\Entity\Appartement;
use App\Entity\Tarif;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TarifType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('saison')
            ->add('dateDebut')
            ->add('dateFin')
            ->add('prixJour')
            ->add('prixSemaine')
            ->add('prixMois')
            ->add('appartement', EntityType::class, [
                'class' => Appartement::class,
                'choice_label' => 'id',
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
