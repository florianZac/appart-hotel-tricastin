<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\User;
use App\Form\ReservationType;
use App\Repository\UserRepository;
use App\Repository\AppartementRepository;
use App\Repository\LocalisationRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReservationController extends AbstractController
{
	#[Route('/reserver', name: 'app_reservation')]
	public function index(
		Request $request,
		EntityManagerInterface $em,
		AppartementRepository $appartementRepo,
		MailerService $mailerService,
		User $user
	): Response {
		$reservation = new Reservation();

		// Pré-remplir si un appartement est passé en paramètre
		$appartementId = $request->query->get('appartement');
		$preselectedLocalisation = null;

		if ($appartementId) {
			$appartement = $appartementRepo->find($appartementId);
			if ($appartement) {
				$reservation->setAppartement($appartement);
				$preselectedLocalisation = $appartement->getLocalisation();
			}
		}

		// Pré-remplir avec les données de l'utilisateur connecté
		$user = $this->getUser();
		if ($user) {
			$reservation->setUser($user);
			if (method_exists($user, 'getNom')) {
				$reservation->setNom($user->getNom());
			}
			if (method_exists($user, 'getPrenom')) {
				$reservation->setPrenom($user->getPrenom());
			}
			if (method_exists($user, 'getEmail')) {
				$reservation->setEmail($user->getEmail());
			}
			if (method_exists($user, 'getTelephone') && $user->getTelephone()) {
				$reservation->setTelephone($user->getTelephone());
			}
		}

		$form = $this->createForm(ReservationType::class, $reservation, [
			'preselected_localisation' => $preselectedLocalisation,
		]);

		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			// Sanitize les données texte (Point 5)
			$reservation->setNom(strip_tags(trim($reservation->getNom())));
			$reservation->setPrenom(strip_tags(trim($reservation->getPrenom())));
			$reservation->setEmail(strip_tags(trim($reservation->getEmail())));
			if ($reservation->getTelephone()) {
				$reservation->setTelephone(strip_tags(trim($reservation->getTelephone())));
			}

			// Associer l'utilisateur connecté
			if ($user) {
				$reservation->setUser($user);
			}

			// Calculer le montant total
			$reservation->calculerMontantTotal();

			$em->persist($reservation);
			$em->flush();

			// Envoyer les notifications email
			try {
				// Notification admin
				$mailerService->sendNouvelleReservationAdmin($reservation);

				// Confirmation client (si la réservation est directement confirmée)
				if ($reservation->getStatut() === Reservation::STATUT_CONFIRMEE) {
					$mailerService->sendConfirmationReservation($reservation);
				}
			} catch (\Exception $e) {
				// On ne bloque pas la réservation si l'email échoue
			}

			$this->addFlash('success', 'Votre demande de réservation a bien été envoyée ! Nous vous recontacterons dans les plus brefs délais.');

			return $this->redirectToRoute('app_reservation');
		}

		return $this->render('reservation/index.html.twig', [
			'form' => $form,
			'preselectedAppartementId' => $appartementId,
		]);
	}

	/**
	 * Route AJAX : retourne les appartements d'une localisation en JSON
	 */
	#[Route('/api/appartements-par-localisation/{id}', name: 'api_appartements_par_localisation', methods: ['GET'])]
	public function appartementsParLocalisation(
		int $id,
		AppartementRepository $appartementRepository,
		LocalisationRepository $localisationRepository
	): JsonResponse {
		$localisation = $localisationRepository->find($id);

		if (!$localisation) {
			return new JsonResponse([], Response::HTTP_NOT_FOUND);
		}

		$appartements = $appartementRepository->findBy(
			['localisation' => $localisation, 'actif' => true],
			['ordre' => 'ASC']
		);

		$data = [];
		foreach ($appartements as $appart) {
			$data[] = [
				'id' => $appart->getId(),
				'label' => sprintf('%s — %s · %dm² · %d-%d pers. · %s€/nuit',
					$appart->getNom(),
					$appart->getType(),
					$appart->getSurface(),
					$appart->getCapaciteMin(),
					$appart->getCapaciteMax(),
					$appart->getPrixParNuit()
				),
			];
		}

		return new JsonResponse($data);
	}
}
