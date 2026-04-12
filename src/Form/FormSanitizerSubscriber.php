<?php

namespace App\EventSubscriber;

use App\Service\SanitizerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;

/**
 * Sanitize automatiquement toutes les données texte
 * soumises via n'importe quel formulaire Symfony.
 *
 * Fonctionne sur TOUS les formulaires sans aucune modification
 * dans les controllers — il suffit de le déclarer comme service.
 */
class FormSanitizerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SanitizerService $sanitizer,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT => 'onPreSubmit',
        ];
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (!is_array($data)) {
            return;
        }

        foreach ($data as $fieldName => $value) {
            // Ne sanitize que les chaînes de caractères
            if (!is_string($value)) {
                continue;
            }

            // Détermine le type de sanitisation selon le type de champ
            if (!$form->has($fieldName)) {
                continue;
            }

            $fieldType = get_class($form->get($fieldName)->getConfig()->getType()->getInnerType());

            $type = match ($fieldType) {
                EmailType::class    => 'email',
                TelType::class      => 'telephone',
                TextareaType::class => 'message',
                TextType::class,
                SearchType::class   => 'texte',
                default             => null,
            };

            if ($type !== null) {
                $data[$fieldName] = $this->sanitizer->sanitize($value, $type);
            }
        }

        $event->setData($data);
    }
}
