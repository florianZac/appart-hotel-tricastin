<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des utilisateurs :
 * - Modification du profil (client + admin)
 * - Désactivation du compte (client)
 * - Administration des utilisateurs (admin : liste, activer/désactiver)
 */
final class UserController extends AbstractController
{
	// =========================================================================
	// PROFIL — Accessible à tout utilisateur connecté (Point 3)
	// =========================================================================

	/**
	 * Modifier son propre profil (nom, prénom, email, téléphone).
	 * Un admin voit en plus le champ "rôles".
	 */
	#[Route('/mon-profil/modifier', name: 'client_profile_edit')]
	#[IsGranted('ROLE_USER')]
	public function editProfile(
		Request $request,
		EntityManagerInterface $em
	): Response {
		/** @var User $user */
		$user = $this->getUser();
		$isAdmin = $this->isGranted('ROLE_ADMIN');

		$form = $this->createForm(ProfileType::class, $user, [
			'is_admin' => $isAdmin,
		]);
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

			$em->flush();

			$this->addFlash('success', 'Profil mis à jour avec succès.');

			return $this->redirectToRoute(
				$isAdmin ? 'admin_dashboard' : 'client_dashboard'
			);
		}

		// Template différent selon admin ou client
		$template = $isAdmin ? 'admin/profile_edit.html.twig' : 'client/profile_edit.html.twig';

		return $this->render($template, [
			'form' => $form,
		]);
	}

	// =========================================================================
	// DÉSACTIVATION DU COMPTE — Client (Point 2)
	// =========================================================================

	/**
	 * Un client peut désactiver son propre compte.
	 * Après désactivation, il est déconnecté et ne pourra plus se reconnecter.
	 */
	#[Route('/mon-profil/desactiver', name: 'client_profile_disable', methods: ['POST'])]
	#[IsGranted('ROLE_USER')]
	public function disableProfile(
		Request $request,
		EntityManagerInterface $em
	): Response {
		if (!$this->isCsrfTokenValid(
			'disable_account',
			$request->request->get('_token')
		)) {
			throw $this->createAccessDeniedException('Token CSRF invalide.');
		}

		/** @var User $user */
		$user = $this->getUser();
		$user->setIsActive(false);
		$em->flush();

		// Rediriger vers la déconnexion — Symfony gère la session proprement.
		// Le UserChecker empêchera toute reconnexion.
		return $this->redirectToRoute('app_logout');
	}

	// =========================================================================
	// ADMIN — Gestion des utilisateurs (Point 4)
	// =========================================================================

	/**
	 * Liste de tous les utilisateurs (admin uniquement).
	 */
	#[Route('/admin/utilisateurs', name: 'admin_users')]
	#[IsGranted('ROLE_ADMIN')]
	public function adminUserList(UserRepository $userRepository): Response
	{
		return $this->render('admin/users.html.twig', [
			'users' => $userRepository->findBy([], ['createdAt' => 'DESC']),
		]);
	}

	/**
	 * Activer / Désactiver un utilisateur (admin uniquement).
	 */
	#[Route('/admin/utilisateur/{id}/toggle', name: 'admin_user_toggle', methods: ['POST'])]
	#[IsGranted('ROLE_ADMIN')]
	public function toggleUserActive(
		int $id,
		Request $request,
		UserRepository $userRepository,
		EntityManagerInterface $em
	): Response {
		$user = $userRepository->find($id);

		if (!$user) {
			throw $this->createNotFoundException('Utilisateur non trouvé.');
		}

		// Empêcher un admin de se désactiver lui-même
		if ($user === $this->getUser()) {
			$this->addFlash('danger', 'Vous ne pouvez pas désactiver votre propre compte.');
			return $this->redirectToRoute('admin_users');
		}

		if (!$this->isCsrfTokenValid('toggle_user_' . $id, $request->request->get('_token'))) {
			throw $this->createAccessDeniedException('Token CSRF invalide.');
		}

		$user->setIsActive(!$user->isActive());
		$em->flush();

		$statut = $user->isActive() ? 'activé' : 'désactivé';
		$this->addFlash('success', sprintf(
			'Le compte de %s a été %s.',
			htmlspecialchars($user->getFullName()),
			$statut
		));

		return $this->redirectToRoute('admin_users');
	}

	/**
	 * Modifier le profil d'un utilisateur (admin uniquement).
	 */
	#[Route('/admin/utilisateur/{id}/modifier', name: 'admin_user_edit')]
	#[IsGranted('ROLE_ADMIN')]
	public function adminEditUser(
		int $id,
		Request $request,
		UserRepository $userRepository,
		EntityManagerInterface $em
	): Response {
		$user = $userRepository->find($id);

		if (!$user) {
			throw $this->createNotFoundException('Utilisateur non trouvé.');
		}

		$form = $this->createForm(ProfileType::class, $user, [
			'is_admin' => true,
		]);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
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

			$em->flush();

			$this->addFlash('success', sprintf(
				'Le profil de %s a été mis à jour.',
				htmlspecialchars($user->getFullName())
			));

			return $this->redirectToRoute('admin_users');
		}

		return $this->render('admin/user_edit.html.twig', [
			'form' => $form,
			'user' => $user,
		]);
	}
}
