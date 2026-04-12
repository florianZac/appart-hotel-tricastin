<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;


class SecurityController extends AbstractController
{
	#[Route(path: '/login', name: 'app_login')]
	public function login(AuthenticationUtils $authenticationUtils): Response
	{
		if ($this->getUser()) {
			return $this->redirectToRoute('client_dashboard');
		}

		$error = $authenticationUtils->getLastAuthenticationError();
		$lastUsername = $authenticationUtils->getLastUsername();

		return $this->render('security/login.html.twig', [
			'last_username' => $lastUsername,
			'error'         => $error,
		]);
	}

	#[Route(path: '/logout', name: 'app_logout')]
	public function logout(): void
	{
		throw new \LogicException('Intercepted by firewall.');
	}

	// =========================================================================
	// API — Vérification email AJAX (utilisé par login, register, forgot)
	// =========================================================================

	#[Route('/api/check-email', name: 'api_check_email', methods: ['POST'])]
	public function checkEmail(
			Request $request,
			UserRepository $repo,
			RateLimiterFactoryInterface $apiCheckLimiter,
	): JsonResponse {
			// Rate limit par IP
			// Ne retourner l'info que si l'utilisateur est connecté (pour le profil)
			// OU sur la page d'inscription uniquement
			$limiter = $apiCheckLimiter->create($request->getClientIp());
			if (false === $limiter->consume(1)->isAccepted()) {
					return new JsonResponse(['error' => 'Trop de requêtes'], 429);
			}

			$data  = json_decode($request->getContent(), true);
			$email = strip_tags(trim($data['email'] ?? ''));

			if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
					return new JsonResponse(['exists' => false]);
			}

			$user = $repo->findOneBy(['email' => $email]);

			return new JsonResponse([
					'exists' => $user !== null,
			]);
	}

	// =========================================================================
	// MOT DE PASSE OUBLIÉ
	// =========================================================================

	#[Route('/mot-de-passe-oublie', name: 'app_forgot_password')]
	public function forgotPassword(
		Request $request,
		UserRepository $userRepository,
		EntityManagerInterface $em,
		MailerService $mailerService,
		RateLimiterFactoryInterface $forgotPasswordLimiter
	): Response {
		if ($this->getUser()) {
			return $this->redirectToRoute('client_dashboard');
		}

		if ($request->isMethod('POST')) {
			
		// Rate limit
			$limiter = $forgotPasswordLimiter->create($request->getClientIp());
			if (false === $limiter->consume(1)->isAccepted()) {
					$this->addFlash('danger', 'Trop de tentatives. Réessayez plus tard.');
					return $this->redirectToRoute('app_forgot_password');
			}
			
			$email = strip_tags(trim($request->request->get('email', '')));

			if (!$this->isCsrfTokenValid('forgot_password', $request->request->get('_token'))) {
				$this->addFlash('danger', 'Token CSRF invalide.');
				return $this->redirectToRoute('app_forgot_password');
			}

			$successMessage = 'Si cette adresse est associée à un compte, un email de réinitialisation a été envoyé.';

			if (!empty($email)) {
				$user = $userRepository->findOneBy(['email' => $email]);

				if ($user && $user->isActive()) {
					$token = bin2hex(random_bytes(32));
					$user->setResetToken($token);
					$user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
					$em->flush();

					try {
						$mailerService->sendPasswordResetEmail($user, $token);
					} catch (\Exception $e) {
						// Log mais ne bloque pas
					}
				}
			}

			$this->addFlash('success', $successMessage);
			return $this->redirectToRoute('app_login');
		}

		return $this->render('security/forgot_password.html.twig');
	}

	// =========================================================================
	// RÉINITIALISATION MOT DE PASSE
	// =========================================================================

	#[Route('/reinitialiser-mot-de-passe/{token}', name: 'app_reset_password')]
	public function resetPassword(
		string $token,
		Request $request,
		UserRepository $userRepository,
		UserPasswordHasherInterface $passwordHasher,
		EntityManagerInterface $em
	): Response {
		if ($this->getUser()) {
			return $this->redirectToRoute('client_dashboard');
		}

		$user = $userRepository->findOneBy(['resetToken' => $token]);

		if (!$user || !$user->isResetTokenValid()) {
			$this->addFlash('danger', 'Ce lien de réinitialisation est invalide ou a expiré.');
			return $this->redirectToRoute('app_forgot_password');
		}

		if ($request->isMethod('POST')) {
			if (!$this->isCsrfTokenValid('reset_password', $request->request->get('_token'))) {
				$this->addFlash('danger', 'Token CSRF invalide.');
				return $this->redirectToRoute('app_reset_password', ['token' => $token]);
			}

			$password        = $request->request->get('password', '');
			$confirmPassword = $request->request->get('confirm_password', '');

			if (strlen($password) < 8) {
				$this->addFlash('danger', 'Le mot de passe doit contenir au moins 8 caractères.');
				return $this->redirectToRoute('app_reset_password', ['token' => $token]);
			}

			if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d.*\d)(?=.*[\W_]).{8,}$/', $password)) {
				$this->addFlash('danger', 'Le mot de passe doit contenir : 1 majuscule, 1 minuscule, 2 chiffres et 1 caractère spécial.');
				return $this->redirectToRoute('app_reset_password', ['token' => $token]);
			}

			if ($password !== $confirmPassword) {
				$this->addFlash('danger', 'Les mots de passe ne correspondent pas.');
				return $this->redirectToRoute('app_reset_password', ['token' => $token]);
			}

			$user->setPassword($passwordHasher->hashPassword($user, $password));
			$user->setResetToken(null);
			$user->setResetTokenExpiresAt(null);
			$em->flush();

			$this->addFlash('success', 'Votre mot de passe a été réinitialisé. Vous pouvez vous connecter.');
			return $this->redirectToRoute('app_login');
		}

		return $this->render('security/reset_password.html.twig', [
			'token' => $token,
		]);
	}
}
