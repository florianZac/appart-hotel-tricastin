<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]

final class UserController extends AbstractController
{
  #[Route(name: 'app_user_index', methods: ['GET'])]
  #[IsGranted('ROLE_ADMIN')]
  public function index(UserRepository $userRepository): Response
  {
    return $this->render('user/index.html.twig', [
      'users' => $userRepository->findAll(),
    ]);
  }

  #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
  public function new(
      Request $request, 
      EntityManagerInterface $entityManager,
      UserPasswordHasherInterface $passwordHasher
      ): Response
  {
    $user = new User();
    $form = $this->createForm(UserType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

      $hashedPassword = $passwordHasher->hashPassword(
        $user,
        $user->getPassword()
      );

      $user->setPassword($hashedPassword);
      $entityManager->persist($user);
      $entityManager->flush();

      return $this->redirectToRoute('app_user_index');
    }

    return $this->render('user/new.html.twig', [
      'user' => $user,
      'form' => $form,
    ]);
  }

  #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
  public function show(User $user): Response
  {
    return $this->render('user/show.html.twig', [
      'user' => $user,
    ]);
  }

  #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
  #[IsGranted('EDIT', subject: 'user')]
  public function edit(
    Request $request, 
    User $user, 
    EntityManagerInterface $entityManager,
    UserPasswordHasherInterface $passwordHasher
    ): Response
  {
    $oldPassword = $user->getPassword();

    $form = $this->createForm(UserType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

      if ($user->getPassword() !== $oldPassword) {
          $hashedPassword = $passwordHasher->hashPassword(
          $user,
          $user->getPassword()
        );
        $user->setPassword($hashedPassword);
      }
      $entityManager->flush();

      return $this->redirectToRoute('app_user_index');
    }

    return $this->render('user/edit.html.twig', [
      'user' => $user,
      'form' => $form,
    ]);
  }

  #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
  #[IsGranted('ROLE_ADMIN')]
  public function delete(
    Request $request, 
    User $user, 
    EntityManagerInterface $entityManager
    ): Response
  {
    if ($this->isCsrfTokenValid(
      'delete'.$user->getId(),
      $request->getPayload()->getString('_token')
    )) {
      $entityManager->remove($user);
      $entityManager->flush();
    }
    return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
  }


	#[Route('/mon-profil/modifier', name: 'client_profile_edit')]
	public function editProfile(
		Request $request,
		EntityManagerInterface $entityManager
	): Response {
		$user = $this->getUser();

		$form = $this->createForm(UserType::class, $user);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->flush();

			$this->addFlash('success', 'Profil mis à jour avec succès.');

			return $this->redirectToRoute('client_dashboard');
		}

		return $this->render('client/profile_edit.html.twig', [
			'form' => $form,
		]);
	}

  #[Route('/mon-profil/desactiver', name: 'client_profile_disable', methods: ['POST'])]
  public function disableProfile(
      Request $request,
      EntityManagerInterface $entityManager
  ): Response {
    if (!$this->isCsrfTokenValid(
        'disable_account',
        $request->request->get('_token')
    )) {
        throw $this->createAccessDeniedException();
    }

    $user = $this->getUser();
    $user->setIsActive(false);

    $entityManager->flush();

    return $this->redirectToRoute('app_logout');
  }

}
