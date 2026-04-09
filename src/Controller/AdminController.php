<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Disponibilite;
use App\Entity\Payment;

use App\Repository\AppartementRepository;
use App\Repository\ReservationRepository;
use App\Repository\TemoignageRepository;
use App\Repository\DisponibiliteRepository;
use App\Repository\PaymentRepository;

use App\Service\CloudinaryService;
use App\Service\MailerService;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;


#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{
	/**
	 * Dashboard admin
	 */
	#[Route('/', name: 'dashboard')]
	public function dashboard(
		AppartementRepository $appartementRepo,
		ReservationRepository $reservationRepo,
		TemoignageRepository $temoignageRepo,
		PaymentRepository $paymentRepo
	): Response {
		$reservations = $reservationRepo->findRecentes(10);

		// Compteurs
		$totalAppartements = count($appartementRepo->findAllActifs());
		$totalReservations = $reservationRepo->count([]);
		$reservationsEnAttente = $reservationRepo->count(['statut' => Reservation::STATUT_EN_ATTENTE]);
		$totalTemoignages = count($temoignageRepo->findActifs());

		// Paiements
		$paiementsRecents = $paymentRepo->findRecents(5);
		$paiementsEnRetard = $paymentRepo->findEnRetard();
		$revenusMois = $paymentRepo->getTotalRevenu(
			new \DateTime('first day of this month 00:00:00'),
			new \DateTime('last day of this month 23:59:59')
		);

		return $this->render('admin/dashboard.html.twig', [
			'reservations'          => $reservations,
			'totalAppartements'     => $totalAppartements,
			'totalReservations'     => $totalReservations,
			'reservationsEnAttente' => $reservationsEnAttente,
			'totalTemoignages'      => $totalTemoignages,
			'paiementsRecents'      => $paiementsRecents,
			'paiementsEnRetard'     => count($paiementsEnRetard),
			'revenusMois'           => $revenusMois,
		]);
	}

	/**
	 * Liste des réservations
	 */
	#[Route('/reservations', name: 'reservations')]
	public function reservations(ReservationRepository $reservationRepo): Response
	{
		return $this->render('admin/reservations.html.twig', [
			'reservations' => $reservationRepo->findRecentes(50),
		]);
	}

	/**
	 * Changer le statut d'une réservation + envoi email
	 */
	#[Route('/reservation/{id}/statut/{statut}', name: 'reservation_statut', methods: ['POST'])]
	public function changeStatut(
		int $id,
		string $statut,
		ReservationRepository $reservationRepo,
		EntityManagerInterface $em,
		MailerService $mailerService,
		Request $request
	): Response {
		$reservation = $reservationRepo->find($id);

		if (!$reservation) {
			throw $this->createNotFoundException('Réservation non trouvée.');
		}

		if ($this->isCsrfTokenValid('statut_' . $id, $request->request->get('_token'))) {
			$oldStatut = $reservation->getStatut();
			$reservation->setStatut($statut);
			$em->flush();

			// Envoi d'emails automatiques selon le changement de statut
			try {
				if ($statut === Reservation::STATUT_CONFIRMEE && $oldStatut === Reservation::STATUT_EN_ATTENTE) {
					$mailerService->sendConfirmationReservation($reservation);
				} elseif ($statut === Reservation::STATUT_ANNULEE) {
					$mailerService->sendReservationAnnuleeEmail($reservation);
				}
			} catch (\Exception $e) {
				// Log l'erreur mais ne bloque pas
			}

			$this->addFlash('success', sprintf(
				'Réservation #%d de %s %s → statut mis à jour : %s',
				$reservation->getId(),
				$reservation->getPrenom(),
				$reservation->getNom(),
				$statut
			));
		}

		return $this->redirectToRoute('admin_reservations');
	}

	// =========================================================================
	// PAIEMENTS
	// =========================================================================

	/**
	 * Liste des paiements (admin)
	 */
	#[Route('/paiements', name: 'paiements')]
	public function paiements(PaymentRepository $paymentRepo): Response
	{
		return $this->render('admin/paiements.html.twig', [
			'payments'        => $paymentRepo->findRecents(50),
			'paiementsRetard' => $paymentRepo->findEnRetard(),
			'revenus'         => $paymentRepo->getRevenusParType(),
		]);
	}

	// =========================================================================
	// UPLOAD CLOUDINARY
	// =========================================================================

	/**
	 * Upload d'image principale pour un appartement via Cloudinary
	 */
	#[Route('/appartement/{id}/upload-image', name: 'upload_image', methods: ['POST'])]
	public function uploadImage(
		int $id,
		Request $request,
		AppartementRepository $appartementRepo,
		CloudinaryService $cloudinaryService,
		EntityManagerInterface $em
	): JsonResponse {
		$appartement = $appartementRepo->find($id);
		if (!$appartement) {
			return new JsonResponse(['error' => 'Appartement non trouvé'], 404);
		}

		/** @var UploadedFile|null $file */
		$file = $request->files->get('image');
		if (!$file || !$file->isValid()) {
			return new JsonResponse(['error' => 'Fichier invalide'], 400);
		}

		// Vérifier le type MIME
		$allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
		if (!in_array($file->getMimeType(), $allowedMimes)) {
			return new JsonResponse(['error' => 'Format non autorisé. Utilisez JPG, PNG ou WebP.'], 400);
		}

		try {
			// Supprimer l'ancienne image si c'est une URL Cloudinary
			$oldImage = $appartement->getImagePrincipale();
			if ($oldImage && str_contains($oldImage, 'cloudinary.com')) {
				$cloudinaryService->deleteByUrl($oldImage);
			}

			// Upload vers Cloudinary
			$folder = 'appart-hotel-tricastin/appartements/' . $appartement->getSlug();
			$url = $cloudinaryService->upload($file->getPathname(), $folder);

			$appartement->setImagePrincipale($url);
			$em->flush();

			return new JsonResponse([
				'url'     => $url,
				'message' => 'Image uploadée avec succès',
			]);
		} catch (\Exception $e) {
			return new JsonResponse(['error' => 'Erreur upload : ' . $e->getMessage()], 500);
		}
	}

	/**
	 * Upload d'images de galerie pour un appartement via Cloudinary
	 */
	#[Route('/appartement/{id}/upload-galerie', name: 'upload_galerie', methods: ['POST'])]
	public function uploadGalerie(
		int $id,
		Request $request,
		AppartementRepository $appartementRepo,
		CloudinaryService $cloudinaryService,
		EntityManagerInterface $em
	): JsonResponse {
		$appartement = $appartementRepo->find($id);
		if (!$appartement) {
			return new JsonResponse(['error' => 'Appartement non trouvé'], 404);
		}

		$files = $request->files->get('images');
		if (!$files || !is_array($files)) {
			return new JsonResponse(['error' => 'Aucun fichier reçu'], 400);
		}

		$folder = 'appart-hotel-tricastin/appartements/' . $appartement->getSlug() . '/galerie';
		$galerie = $appartement->getGalerie() ?? [];
		$uploadedUrls = [];

		foreach ($files as $file) {
			if (!$file instanceof UploadedFile || !$file->isValid()) {
				continue;
			}

			$allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
			if (!in_array($file->getMimeType(), $allowedMimes)) {
				continue;
			}

			try {
				$url = $cloudinaryService->upload($file->getPathname(), $folder);
				$galerie[] = $url;
				$uploadedUrls[] = $url;
			} catch (\Exception $e) {
				// Skip les fichiers en erreur
			}
		}

		$appartement->setGalerie($galerie);
		$em->flush();

		return new JsonResponse([
			'urls'    => $uploadedUrls,
			'total'   => count($galerie),
			'message' => count($uploadedUrls) . ' image(s) uploadée(s)',
		]);
	}

	/**
	 * Supprimer une image de la galerie
	 */
	#[Route('/appartement/{id}/supprimer-image-galerie', name: 'delete_galerie_image', methods: ['POST'])]
	public function deleteGalerieImage(
		int $id,
		Request $request,
		AppartementRepository $appartementRepo,
		CloudinaryService $cloudinaryService,
		EntityManagerInterface $em
	): JsonResponse {
		$appartement = $appartementRepo->find($id);
		if (!$appartement) {
			return new JsonResponse(['error' => 'Appartement non trouvé'], 404);
		}

		$data = json_decode($request->getContent(), true);
		$urlToDelete = $data['url'] ?? null;

		if (!$urlToDelete) {
			return new JsonResponse(['error' => 'URL manquante'], 400);
		}

		// Supprimer de Cloudinary
		if (str_contains($urlToDelete, 'cloudinary.com')) {
			$cloudinaryService->deleteByUrl($urlToDelete);
		}

		// Retirer du tableau galerie
		$galerie = $appartement->getGalerie() ?? [];
		$galerie = array_values(array_filter($galerie, fn($url) => $url !== $urlToDelete));
		$appartement->setGalerie($galerie);
		$em->flush();

		return new JsonResponse(['message' => 'Image supprimée', 'total' => count($galerie)]);
	}

	// =========================================================================
	// CALENDRIER
	// =========================================================================

	/**
	 * Page de gestion du calendrier
	 */
	#[Route('/calendrier', name: 'calendrier')]
	public function calendrier(AppartementRepository $appartementRepo): Response
	{
		return $this->render('admin/calendrier.html.twig', [
			'appartements' => $appartementRepo->findAllActifs(),
		]);
	}

	/**
	 * API Admin : récupérer les disponibilités
	 */
	#[Route('/api/disponibilites/{appartementId}', name: 'api_admin_disponibilites', methods: ['GET'])]
	public function getAdminDisponibilites(
		int $appartementId,
		Request $request,
		DisponibiliteRepository $disponibiliteRepo
	): JsonResponse {
		$start = new \DateTime($request->query->get('start', 'first day of this month'));
		$end = new \DateTime($request->query->get('end', 'last day of +2 months'));

		$disponibilites = $disponibiliteRepo->findByAppartementAndPeriode($appartementId, $start, $end);

		$events = [];
		foreach ($disponibilites as $dispo) {
			$events[] = [
				'id'    => $dispo->getId(),
				'title' => $dispo->getStatutLabel() . ($dispo->getNote() ? ' - ' . $dispo->getNote() : ''),
				'start' => $dispo->getDateDebut()->format('Y-m-d'),
				'end'   => $dispo->getDateFin()->modify('+1 day')->format('Y-m-d'),
				'color' => $dispo->getCouleur(),
				'allDay' => true,
			];
		}

		return new JsonResponse($events);
	}

	/**
	 * API Admin : créer/modifier une disponibilité
	 */
	#[Route('/api/disponibilite', name: 'api_admin_disponibilite_create', methods: ['POST'])]
	public function createDisponibilite(
		Request $request,
		AppartementRepository $appartementRepo,
		EntityManagerInterface $em
	): JsonResponse {
		$data = json_decode($request->getContent(), true);

		$appartement = $appartementRepo->find($data['appartement_id'] ?? 0);
		if (!$appartement) {
			return new JsonResponse(['error' => 'Appartement non trouvé'], 404);
		}

		$dispo = new Disponibilite();
		$dispo->setAppartement($appartement);
		$dispo->setDateDebut(new \DateTime($data['date_debut']));
		$dispo->setDateFin(new \DateTime($data['date_fin']));
		$dispo->setStatut($data['statut'] ?? Disponibilite::STATUT_BLOQUE);
		$dispo->setNote($data['note'] ?? null);

		$em->persist($dispo);
		$em->flush();

		return new JsonResponse([
			'id'      => $dispo->getId(),
			'message' => 'Disponibilité créée',
		], 201);
	}

	/**
	 * API Admin : supprimer une disponibilité
	 */
	#[Route('/api/disponibilite/{id}', name: 'api_admin_disponibilite_delete', methods: ['DELETE'])]
	public function deleteDisponibilite(
		int $id,
		DisponibiliteRepository $disponibiliteRepo,
		EntityManagerInterface $em
	): JsonResponse {
		$dispo = $disponibiliteRepo->find($id);
		if (!$dispo) {
			return new JsonResponse(['error' => 'Non trouvé'], 404);
		}

		$em->remove($dispo);
		$em->flush();

		return new JsonResponse(['message' => 'Supprimé']);
	}
}
