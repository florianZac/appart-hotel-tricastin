<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Temoignage;
use App\Form\TemoignageType;
use App\Repository\TemoignageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @author      Florian Aizac
 * @created     12/04/2026
 * @description Contrôleur permettant aux clients de laisser un avis après leur séjour
 */
#[Route('/espace-client/avis', name: 'client_avis_')]
#[IsGranted('ROLE_USER')]
class TemoignageController extends AbstractController
{
	/**
	 * Formulaire de dépôt d'avis lié à une réservation
	 */
	#[Route('/nouveau/{id}', name: 'new')]
	public function new(
		Reservation $reservation,
		Request $request,
		TemoignageRepository $temoignageRepo,
		EntityManagerInterface $em,
	): Response {
		$user = $this->getUser();

		// Sécurité : seul le client de la réservation peut laisser un avis
		if ($reservation->getUser() !== $user) {
			throw $this->createAccessDeniedException();
		}

		// Vérifie que le séjour est bien terminé
		if ($reservation->getDateDepart() > new \DateTime()) {
			$this->addFlash('warning', 'Votre séjour n\'est pas encore terminé.');
			return $this->redirectToRoute('client_dashboard');
		}

		// Vérifie qu'il n'a pas déjà laissé un avis
		$existant = $temoignageRepo->findByUserAndReservation($user, $reservation);
		if ($existant) {
			$this->addFlash('info', 'Vous avez déjà laissé un avis pour ce séjour.');
			return $this->redirectToRoute('client_dashboard');
		}

		$temoignage = new Temoignage();
		$temoignage->setUser($user);
		$temoignage->setAppartement($reservation->getAppartement());
		$temoignage->setReservation($reservation);
		$temoignage->setAuteur($user->getPrenom() . ' ' . mb_substr($user->getNom(), 0, 1) . '.');

		$form = $this->createForm(TemoignageType::class, $temoignage);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$em->persist($temoignage);
			$em->flush();

			$this->addFlash('success', 'Merci pour votre avis ! Il sera publié après validation par notre équipe.');
			return $this->redirectToRoute('client_dashboard');
		}

		return $this->render('temoignage/new.html.twig', [
			'form'        => $form,
			'reservation' => $reservation,
		]);
	}
}
