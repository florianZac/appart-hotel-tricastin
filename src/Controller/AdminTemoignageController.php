<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Temoignage;
use App\Repository\ReservationRepository;
use App\Repository\TemoignageRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @author      Florian Aizac
 * @created     12/04/2026
 * @description Gestion des témoignages côté admin : approuver, refuser, relancer
 */
#[Route('/admin/temoignages', name: 'admin_temoignages_')]
#[IsGranted('ROLE_ADMIN')]
class AdminTemoignageController extends AbstractController
{
	/**
	 * Dashboard témoignages : en attente, approuvés, refusés + réservations sans avis
	 */
	#[Route('', name: 'index')]
	public function index(
		TemoignageRepository $temoignageRepo,
		ReservationRepository $reservationRepo,
	): Response {
		// Réservations terminées dont le client n'a pas encore laissé d'avis
		$reservationsTerminees = $reservationRepo->findSejoursTerminesSansDemandeAvis(new \DateTime('-1 day'));
		$reservationsAvecAvis = [];

		// Vérifier quelles réservations ont déjà un témoignage
		$allTerminees = $reservationRepo->createQueryBuilder('r')
			->andWhere('r.dateDepart <= :now')
			->andWhere('r.user IS NOT NULL')
			->andWhere('r.statut IN (:statuts)')
			->setParameter('now', new \DateTime())
			->setParameter('statuts', [Reservation::STATUT_CONFIRMEE, Reservation::STATUT_TERMINEE])
			->orderBy('r.dateDepart', 'DESC')
			->setMaxResults(30)
			->getQuery()
			->getResult();

		foreach ($allTerminees as $resa) {
			$temoignage = $temoignageRepo->findByUserAndReservation($resa->getUser(), $resa);
			$reservationsAvecAvis[] = [
				'reservation' => $resa,
				'temoignage'  => $temoignage,
			];
		}

		return $this->render('admin/temoignages.html.twig', [
			'en_attente'          => $temoignageRepo->findByStatut(Temoignage::STATUT_EN_ATTENTE),
			'approuves'           => $temoignageRepo->findByStatut(Temoignage::STATUT_APPROUVE),
			'refuses'             => $temoignageRepo->findByStatut(Temoignage::STATUT_REFUSE),
			'reservationsAvecAvis' => $reservationsAvecAvis,
		]);
	}

	/**
	 * Approuver un témoignage
	 */
	#[Route('/{id}/approuver', name: 'approuver', methods: ['POST'])]
	public function approuver(
		Temoignage $temoignage,
		Request $request,
		EntityManagerInterface $em,
	): Response {
		if ($this->isCsrfTokenValid('approuver' . $temoignage->getId(), $request->request->get('_token'))) {
			$temoignage->setStatut(Temoignage::STATUT_APPROUVE);
			$temoignage->setValidatedAt(new \DateTime());
			$em->flush();
			$this->addFlash('success', 'Témoignage approuvé et publié sur le site.');
		}

		return $this->redirectToRoute('admin_temoignages_index');
	}

	/**
	 * Refuser un témoignage
	 */
	#[Route('/{id}/refuser', name: 'refuser', methods: ['POST'])]
	public function refuser(
		Temoignage $temoignage,
		Request $request,
		EntityManagerInterface $em,
	): Response {
		if ($this->isCsrfTokenValid('refuser' . $temoignage->getId(), $request->request->get('_token'))) {
			$temoignage->setStatut(Temoignage::STATUT_REFUSE);
			$em->flush();
			$this->addFlash('warning', 'Témoignage refusé.');
		}

		return $this->redirectToRoute('admin_temoignages_index');
	}

	/**
	 * Relancer un client pour une réservation sans avis
	 */
	#[Route('/relancer/{id}', name: 'relancer', methods: ['POST'])]
	public function relancer(
		Reservation $reservation,
		Request $request,
		MailerService $mailerService,
	): Response {
		if ($this->isCsrfTokenValid('relancer' . $reservation->getId(), $request->request->get('_token'))) {
			try {
				$mailerService->sendDemandeAvis($reservation);
				$this->addFlash('info', sprintf(
					'Relance envoyée à %s %s.',
					$reservation->getPrenom(),
					$reservation->getNom()
				));
			} catch (\Exception $e) {
				$this->addFlash('danger', 'Erreur lors de l\'envoi : ' . $e->getMessage());
			}
		}

		return $this->redirectToRoute('admin_temoignages_index');
	}
}
