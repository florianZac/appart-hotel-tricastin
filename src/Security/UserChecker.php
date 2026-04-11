<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Vérifie que le compte utilisateur est actif avant d'autoriser la connexion.
 * Point 1 : Un utilisateur désactivé ne peut pas se connecter.
 */
class UserChecker implements UserCheckerInterface
{
	public function checkPreAuth(UserInterface $user): void
	{
		if (!$user instanceof User) {
			return;
		}

		if (!$user->isActive()) {
			throw new CustomUserMessageAccountStatusException(
				'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'
			);
		}
	}

	public function checkPostAuth(UserInterface $user): void
	{
		// Pas de vérification supplémentaire après authentification
	}
}
