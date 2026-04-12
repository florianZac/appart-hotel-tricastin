<?php

namespace App\Form;

use App\Entity\Appartement;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExportComptabiliteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $anneeActuelle = (int) date('Y');

        // Plage d'années : de anneeActuelle-5 à anneeActuelle+1
        $choixAnnees = [];
        for ($a = $anneeActuelle - 5; $a <= $anneeActuelle + 1; $a++) {
            $choixAnnees[$a] = $a;
        }

        $builder
            ->add('annee', ChoiceType::class, [
                'label'   => 'Année',
                'choices' => $choixAnnees,
                'data'    => $anneeActuelle,
                'attr'    => ['class' => 'form-select'],
            ])
            ->add('appartement', EntityType::class, [
                'class'        => Appartement::class,
                'choice_label' => 'nom',
                'label'        => 'Appartement',
                'required'     => false,
                'placeholder'  => '— Tous les appartements —',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('a')->orderBy('a.nom', 'ASC');
                },
                'attr' => ['class' => 'form-select'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false, // GET request, pas besoin de CSRF
        ]);
    }
}
