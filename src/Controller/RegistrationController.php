<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

class RegistrationController extends AbstractController
{
	#[Route('/inscription', name: 'app_register')]
	public function register(
		Request $request,
		UserPasswordHasherInterface $passwordHasher,
		EntityManagerInterface $em,
		MailerService $mailerService,
    	RateLimiterFactoryInterface $registrationLimiter,
	): Response {
		// Redirige si déjà connecté
		if ($this->getUser()) {
			return $this->redirectToRoute('client_dashboard');
		}

    // Rate limit
    $limiter = $registrationLimiter->create($request->getClientIp());
    if (false === $limiter->consume(1)->isAccepted()) {
        $this->addFlash('danger', 'Trop de tentatives. Réessayez plus tard.');
        return $this->redirectToRoute('app_register');
    }
		
		$user = new User();
		$form = $this->createForm(RegistrationType::class, $user);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			// Sanitize les champs texte (Point 5)
			$user->setNom(strip_tags(trim($user->getNom())));
			$user->setPrenom(strip_tags(trim($user->getPrenom())));
			$user->setEmail(strip_tags(trim($user->getEmail())));
			if ($user->getTelephone()) {
				$user->setTelephone(strip_tags(trim($user->getTelephone())));
			}
			if ($user->getAdresse()) {
				$user->setAdresse(strip_tags(trim($user->getAdresse())));
			}
			if ($user->getVille()) {
				$user->setVille(strip_tags(trim($user->getVille())));
			}
			if ($user->getCodePostal()) {
				$user->setCodePostal(strip_tags(trim($user->getCodePostal())));
			}

			// Hasher le mot de passe
			$hashedPassword = $passwordHasher->hashPassword(
				$user,
				$form->get('plainPassword')->getData()
			);
			$user->setPassword($hashedPassword);

			$em->persist($user);
			$em->flush();

			// Email de bienvenue
			try {
				$mailerService->sendWelcomeEmail($user);
			} catch (\Exception $e) {
				// Ne bloque pas l'inscription
			}

			$this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');

			return $this->redirectToRoute('app_login');
		}

		return $this->render('security/register.html.twig', [
			'form' => $form,
		]);
	}
}
