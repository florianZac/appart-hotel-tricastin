<?php

namespace App\Form;

use App\Entity\Appartement;
use App\Entity\Localisation;
use App\Entity\Reservation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Repository\AppartementRepository;

class ReservationType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
     ->add('localisation', EntityType::class, [
        'class' => Localisation::class,
        'choice_label' => 'ville',
        'label' => 'Localisation',
        'placeholder' => '-- Choisissez une ville --',
        'mapped' => false,
        'data' => $options['preselected_localisation'],
        'attr' => ['class' => 'form-select'],
      ])
      ->add('nom', TextType::class, [
        'label' => 'Nom',
        'attr' => ['placeholder' => 'Votre nom'],
      ])
      ->add('prenom', TextType::class, [
        'label' => 'Prénom',
        'attr' => ['placeholder' => 'Votre prénom'],
      ])
      ->add('email', EmailType::class, [
        'label' => 'Email',
        'attr' => ['placeholder' => 'votre@email.com'],
      ])
      ->add('telephone', TelType::class, [
        'label' => 'Téléphone',
        'attr' => ['placeholder' => '06 00 00 00 00'],
      ])
      ->add('dateArrivee', DateType::class, [
        'label' => 'Date d\'arrivée',
        'widget' => 'single_text',
        'attr' => ['min' => (new \DateTime())->format('Y-m-d')],
      ])
      ->add('dateDepart', DateType::class, [
        'label' => 'Date de départ',
        'widget' => 'single_text',
      ])
      ->add('nombrePersonnes', IntegerType::class, [
        'label' => 'Nombre de personnes',
        'data' => 1,
        'attr' => ['min' => 1, 'max' => 8],
      ])
      ->add('message', TextareaType::class, [
        'label' => 'Message (optionnel)',
        'required' => false,
        'attr' => [
          'placeholder' => 'Précisez vos besoins particuliers...',
          'rows' => 4,
        ],
      ])
    ;

    // Ajouter le champ appartement vide par défaut
    $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
      $form = $event->getForm();
      $reservation = $event->getData();

      $localisation = null;
      if ($reservation && $reservation->getAppartement()) {
          $localisation = $reservation->getAppartement()->getLocalisation();
      }

      $this->addAppartementField($form, $localisation);
    });

    // Mettre à jour le champ appartement quand la localisation change
    $builder->get('localisation')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
      $localisation = $event->getForm()->getData();
      $form = $event->getForm()->getParent();

      $this->addAppartementField($form, $localisation);
    });
  }

  private function addAppartementField(FormInterface $form, ?Localisation $localisation): void
  {
    $form->add('appartement', EntityType::class, [
      'class' => Appartement::class,
      'choice_label' => function (Appartement $appartement) {
        return sprintf('%s — %s · %dm² · %d-%d pers. · %s€/nuit',
            $appartement->getNom(),
            $appartement->getType(),
            $appartement->getSurface(),
            $appartement->getCapaciteMin(),
            $appartement->getCapaciteMax(),
            $appartement->getPrixParNuit()
        );
      },
      'label' => 'Appartement',
      'placeholder' => $localisation ? '-- Choisissez un appartement --' : '-- Sélectionnez d\'abord une ville --',
      'attr' => ['class' => 'form-select'],
      'query_builder' => function (AppartementRepository $repo) use ($localisation) {
        $qb = $repo->createQueryBuilder('a')
          ->where('a.actif = :actif')
          ->setParameter('actif', true)
          ->orderBy('a.ordre', 'ASC');

        if ($localisation) {
          $qb->andWhere('a.localisation = :loc')
            ->setParameter('loc', $localisation);
        } else {
          // Aucun résultat si pas de localisation sélectionnée
          $qb->andWhere('a.localisation IS NULL');
        }

        return $qb;
      },
    ]);
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'data_class' => Reservation::class,
      'preselected_localisation' => null,
    ]);
  }
}