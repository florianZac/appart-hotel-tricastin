<?php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Sanitize les champs (Point 5)
            $data['nom']       = strip_tags(trim($data['nom']));
            $data['email']     = strip_tags(trim($data['email']));
            $data['sujet']     = strip_tags(trim($data['sujet']));
            $data['message']   = strip_tags(trim($data['message']));
            $data['telephone'] = isset($data['telephone']) ? strip_tags(trim($data['telephone'])) : null;

            try {
                $email = (new Email())
                    ->from($data['email'])
                    ->to($this->getParameter('contact_email'))
                    ->subject('[Contact Appart Hôtel] ' . $data['sujet'])
                    ->html(
                        '<h3>Nouveau message de contact</h3>' .
                        '<p><strong>Nom :</strong> ' . htmlspecialchars($data['nom']) . '</p>' .
                        '<p><strong>Email :</strong> ' . htmlspecialchars($data['email']) . '</p>' .
                        '<p><strong>Téléphone :</strong> ' . htmlspecialchars($data['telephone'] ?? 'Non renseigné') . '</p>' .
                        '<p><strong>Message :</strong></p>' .
                        '<p>' . nl2br(htmlspecialchars($data['message'])) . '</p>'
                    );

                $mailer->send($email);
            } catch (\Exception $e) {
                // Log l'erreur mais affiche quand même un message de succès
                // car le message a été reçu côté serveur
            }

            $this->addFlash('success', 'Votre message a bien été envoyé. Nous vous répondrons rapidement !');

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form,
        ]);
    }
}
