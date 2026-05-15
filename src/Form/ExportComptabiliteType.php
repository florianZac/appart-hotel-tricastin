<?php

namespace App\Form;

use App\Entity\Appartement;
use App\Entity\Localisation;
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
            // ── Filtre localisation (pour export Excel multi-onglets) ──
            ->add('localisation', EntityType::class, [
                'class'        => Localisation::class,
                'choice_label' => 'ville',
                'label'        => 'Localisation (Excel)',
                'required'     => false,
                'placeholder'  => '— Toutes les localisations —',
                'query_builder' => fn(EntityRepository $er) =>
                    $er->createQueryBuilder('l')->orderBy('l.ville', 'ASC'),
                'attr'    => ['class' => 'form-select'],
                'help'    => 'Pour l\'export Excel uniquement. Vide = 1 onglet par localisation + récapitulatif.',
            ])
            // ── Filtre appartement (pour export CSV historique) ──────
            ->add('appartement', EntityType::class, [
                'class'        => Appartement::class,
                'choice_label' => 'nom',
                'label'        => 'Appartement (CSV)',
                'required'     => false,
                'placeholder'  => '— Tous les appartements —',
                'query_builder' => fn(EntityRepository $er) =>
                    $er->createQueryBuilder('a')->orderBy('a.nom', 'ASC'),
                'attr'    => ['class' => 'form-select'],
                'help'    => 'Pour l\'export CSV uniquement.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method'          => 'GET',
            'csrf_protection' => false,
        ]);
    }
}
