<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Disponibilite;
use App\Entity\Payment;
use App\Entity\Tarif;
use App\Entity\Appartement;

use App\Form\TarifType;

use App\Repository\AppartementRepository;
use App\Repository\ReservationRepository;
use App\Repository\TemoignageRepository;
use App\Repository\DisponibiliteRepository;
use App\Repository\PaymentRepository;
use App\Repository\TarifRepository;

use App\Service\CloudinaryService;
use App\Service\MailerService;
use App\Service\AnalyticsService;

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
	 * Vérifie que la requête est bien un appel AJAX interne
	 */
	private function isAjaxRequest(Request $request): bool
	{
		return $request->headers->get('X-Requested-With') === 'XMLHttpRequest';
	}
	/**
	 * Dashboard admin
	 */
	#[Route('/', name: 'dashboard')]
	public function dashboard(
		AppartementRepository $appartementRepo,
		ReservationRepository $reservationRepo,
		TemoignageRepository $temoignageRepo,
		PaymentRepository $paymentRepo,
		AnalyticsService $analytics
	): Response {
		$reservations = $reservationRepo->findRecentes(10);

		// Compteurs
		$appartements = $appartementRepo->findAllActifs();
		$totalAppartements = count($appartements);
		$totalReservations = $reservationRepo->count([]);
		$reservationsEnAttente = $reservationRepo->count(['statut' => Reservation::STATUT_EN_ATTENTE]);
		$totalTemoignages = count($temoignageRepo->findActifs());
		$temoignagesEnAttente = $temoignageRepo->countEnAttente();

		// Paiements
		$paiementsRecents = $paymentRepo->findRecents(5);
		$paiementsEnRetard = $paymentRepo->findEnRetard();
		$revenusMois = $paymentRepo->getTotalRevenu(
			new \DateTime('first day of this month 00:00:00'),
			new \DateTime('last day of this month 23:59:59')
		);

		// Analytics Chart.js
		$annee = (int) date('Y');

		return $this->render('admin/dashboard.html.twig', [
			'reservations'          => $reservations,
			'totalAppartements'     => $totalAppartements,
			'totalReservations'     => $totalReservations,
			'reservationsEnAttente' => $reservationsEnAttente,
			'totalTemoignages'      => $totalTemoignages,
			'temoignagesEnAttente'  => $temoignagesEnAttente,
			'paiementsRecents'      => $paiementsRecents,
			'paiementsEnRetard'     => count($paiementsEnRetard),
			'revenusMois'           => $revenusMois,
			// Données Chart.js
			'anneeEnCours'          => $annee,
			'revenusParMois'        => $analytics->getRevenusParMois($annee),
			'reservationsParStatut' => $analytics->getReservationsParStatut(),
			'tauxOccupation'        => $analytics->getTauxOccupationParMois($annee, $totalAppartements),
			'topAppartements'       => $analytics->getTopAppartements(5),
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
		if (!$this->isAjaxRequest($request)) {
			return new JsonResponse(['error' => 'Accès interdit'], 403);
		}

		$appartement = $appartementRepo->find($id);
		if (!$appartement) {
			return new JsonResponse(['error' => 'Appartement non trouvé'], 404);
		}

		/** @var UploadedFile|null $file */
		$file = $request->files->get('image');
		if (!$file || !$file->isValid()) {
			return new JsonResponse(['error' => 'Fichier invalide'], 400);
		}

		/** @var UploadedFile|null $file */
		// Vérifier le type MIME
		$allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
		if (!in_array($file->getMimeType(), $allowedMimes)) {
			return new JsonResponse(['error' => 'Format non autorisé. Utilisez JPG, PNG ou WebP.'], 400);
		}
		/** @var UploadedFile|null $file */
		// Limite de taille : 5 Mo
		if ($file->getSize() > 5 * 1024 * 1024) {
			return new JsonResponse(['error' => 'Fichier trop volumineux (max 5 Mo).'], 400);
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
		if (!$this->isAjaxRequest($request)) {
			return new JsonResponse(['error' => 'Accès interdit'], 403);
		}
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

			// Limite de taille : 5 Mo
			if ($file->getSize() > 5 * 1024 * 1024) {
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
		if (!$this->isAjaxRequest($request)) {
			return new JsonResponse(['error' => 'Accès interdit'], 403);
		}
		$appartement = $appartementRepo->find($id);
		if (!$appartement) {
			return new JsonResponse(['error' => 'Appartement non trouvé'], 404);
		}

		$data = json_decode($request->getContent(), true);
		$urlToDelete = isset($data['url']) ? filter_var($data['url'], FILTER_SANITIZE_URL) : null;

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
		DisponibiliteRepository $disponibiliteRepo,
		ReservationRepository $reservationRepo
	): JsonResponse {
		$startParam = $request->query->get('start');
		$endParam = $request->query->get('end');
		$start = $startParam ? new \DateTime(substr($startParam, 0, 10)) : new \DateTime('first day of this month');
		$end = $endParam ? new \DateTime(substr($endParam, 0, 10)) : new \DateTime('last day of +2 months');

		$events = [];

		// ── 1. Disponibilités manuelles (admin) — éditables ──
		$disponibilites = $disponibiliteRepo->findByAppartementAndPeriode($appartementId, $start, $end);

		foreach ($disponibilites as $dispo) {
			$events[] = [
				'id'    => $dispo->getId(),
				'title' => $dispo->getStatutLabel() . ($dispo->getNote() ? ' - ' . $dispo->getNote() : ''),
				'start' => $dispo->getDateDebut()->format('Y-m-d'),
				'end'   => $dispo->getDateFin()->modify('+1 day')->format('Y-m-d'),
				'color' => $dispo->getCouleur(),
				'allDay' => true,
				'extendedProps' => [
					'statut' => $dispo->getStatut(),
					'note'   => $dispo->getNote(),
					'source' => 'disponibilite',
				],
			];
		}

		// ── 2. Réservations confirmées (lecture seule) ──
		$reservations = $reservationRepo->findConfirmeesParAppartement($appartementId, $start, $end);

		foreach ($reservations as $reservation) {
			// Période réservée
			$events[] = [
				'id'    => 'res-' . $reservation->getId(),
				'title' => 'Réservé (Client : ' . $reservation->getPrenom() . ' ' . $reservation->getNom() . ')',
				'start' => $reservation->getDateArrivee()->format('Y-m-d'),
				'end'   => $reservation->getDateDepart()->format('Y-m-d'),
				'color' => '#c0392b',
				'allDay' => true,
				'extendedProps' => [
					'statut' => 'reserve',
					'note'   => 'Réservation #' . $reservation->getId(),
					'source' => 'reservation',
					'readonly' => true,
				],
			];

			// Jour de nettoyage auto après départ
			$nettoyageEnd = (clone $reservation->getDateDepart())->modify('+1 day');
			$events[] = [
				'id'    => 'maint-' . $reservation->getId(),
				'title' => 'Nettoyage (auto)',
				'start' => $reservation->getDateDepart()->format('Y-m-d'),
				'end'   => $nettoyageEnd->format('Y-m-d'),
				'color' => '#95a5a6',
				'allDay' => true,
				'extendedProps' => [
					'statut' => 'nettoyage',
					'note'   => 'Nettoyage automatique après réservation #' . $reservation->getId(),
					'source' => 'reservation',
					'readonly' => true,
				],
			];
		}

		return new JsonResponse($events);
	}

	/**
	 * API Admin : créer/modifier une disponibilité
	 * Découpe intelligemment les plages existantes pour éviter tout chevauchement.
	 */
	#[Route('/api/disponibilite', name: 'api_admin_disponibilite_create', methods: ['POST'])]
	public function createDisponibilite(
		Request $request,
		AppartementRepository $appartementRepo,
		DisponibiliteRepository $disponibiliteRepo,
		EntityManagerInterface $em
	): JsonResponse {
		if (!$this->isAjaxRequest($request)) {
			return new JsonResponse(['error' => 'Accès interdit'], 403);
		}
		$data = json_decode($request->getContent(), true);

		if (!is_array($data)) {
			return new JsonResponse(['error' => 'Données invalides'], 400);
		}

		$appartement = $appartementRepo->find((int) ($data['appartement_id'] ?? 0));
		if (!$appartement) {
			return new JsonResponse(['error' => 'Appartement non trouvé'], 404);
		}

		$dateDebut = new \DateTime($data['date_debut']);
		$dateFin   = new \DateTime($data['date_fin']);
		$newStatut = $data['statut'] ?? Disponibilite::STATUT_BLOQUE;
		$newNote   = isset($data['note']) ? strip_tags(trim($data['note'])) : null;

		// ── Phase 1 : Découper et supprimer les chevauchements ──
		$chevauchements = $disponibiliteRepo->findByAppartementAndPeriode(
			$appartement->getId(), $dateDebut, $dateFin
		);

		// Collecter les morceaux à créer AVANT de toucher aux entités
		$morceauxACreer = [];

		foreach ($chevauchements as $ancien) {
			// Copier les dates AVANT toute modification
			$ancienDebut  = clone $ancien->getDateDebut();
			$ancienFin    = clone $ancien->getDateFin();
			$ancienStatut = $ancien->getStatut();
			$ancienNote   = $ancien->getNote();

			// Partie AVANT la nouvelle plage : [ancienDebut .. dateDebut - 1j]
			if ($ancienDebut < $dateDebut) {
				$finAvant = (clone $dateDebut)->modify('-1 day');
				if ($finAvant >= $ancienDebut) {
					$morceauxACreer[] = [
						'debut'  => clone $ancienDebut,
						'fin'    => $finAvant,
						'statut' => $ancienStatut,
						'note'   => $ancienNote,
					];
				}
			}

			// Partie APRÈS la nouvelle plage : [dateFin + 1j .. ancienFin]
			if ($ancienFin > $dateFin) {
				$debutApres = (clone $dateFin)->modify('+1 day');
				if ($debutApres <= $ancienFin) {
					$morceauxACreer[] = [
						'debut'  => $debutApres,
						'fin'    => clone $ancienFin,
						'statut' => $ancienStatut,
						'note'   => $ancienNote,
					];
				}
			}

			// Supprimer l'ancienne plage
			$em->remove($ancien);
		}

		// Flush les suppressions d'abord
		$em->flush();

		// ── Phase 2 : Créer les morceaux conservés + la nouvelle entrée ──
		foreach ($morceauxACreer as $m) {
			$morceau = new Disponibilite();
			$morceau->setAppartement($appartement);
			$morceau->setDateDebut($m['debut']);
			$morceau->setDateFin($m['fin']);
			$morceau->setStatut($m['statut']);
			$morceau->setNote($m['note']);
			$em->persist($morceau);
		}

		$dispo = new Disponibilite();
		$dispo->setAppartement($appartement);
		$dispo->setDateDebut($dateDebut);
		$dispo->setDateFin($dateFin);
		$dispo->setStatut($newStatut);
		$dispo->setNote($newNote);

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
		Request $request,
		EntityManagerInterface $em
	): JsonResponse {
		if (!$this->isAjaxRequest($request)) {
			return new JsonResponse(['error' => 'Accès interdit'], 403);
		}

		$dispo = $disponibiliteRepo->find($id);

		if (!$dispo) {
			return new JsonResponse(['error' => 'Non trouvé'], 404);
		}

		$em->remove($dispo);
		$em->flush();

		return new JsonResponse(['message' => 'Supprimé']);
	}

	// =========================================================================
	// GESTION DES TARIFS
	// =========================================================================

	/**
	 * Page de gestion des tarifs d'un appartement
	 */
	#[Route('/tarifs', name: 'tarifs')]
	public function tarifsIndex(AppartementRepository $appartementRepo): Response
	{
		return $this->render('admin/tarifs.html.twig', [
			'appartements' => $appartementRepo->findAllActifs(),
		]);
	}

	/**
	 * API : récupérer les tarifs d'un appartement (JSON)
	 */
	#[Route('/api/tarifs/{appartementId}', name: 'api_tarifs', methods: ['GET'])]
	public function getTarifs(int $appartementId, TarifRepository $tarifRepo): JsonResponse
	{
		$tarifs = $tarifRepo->findByAppartement($appartementId);
		$data = [];

		foreach ($tarifs as $tarif) {
			$data[] = [
				'id'          => $tarif->getId(),
				'saison'      => $tarif->getSaison(),
				'dateDebut'   => $tarif->getDateDebut()->format('Y-m-d'),
				'dateFin'     => $tarif->getDateFin()->format('Y-m-d'),
				'prixJour'    => $tarif->getPrixJour(),
				'prixSemaine' => $tarif->getPrixSemaine(),
				'prixMois'    => $tarif->getPrixMois(),
			];
		}

		return new JsonResponse($data);
	}

	/**
	 * API : créer un tarif
	 */
	#[Route('/api/tarif', name: 'api_tarif_create', methods: ['POST'])]
	public function createTarif(
		Request $request,
		AppartementRepository $appartementRepo,
		TarifRepository $tarifRepo,
		EntityManagerInterface $em
	): JsonResponse {
		if (!$this->isAjaxRequest($request)) {
			return new JsonResponse(['error' => 'Accès interdit'], 403);
		}

		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return new JsonResponse(['error' => 'Données invalides'], 400);
		}

		$appartement = $appartementRepo->find((int) ($data['appartement_id'] ?? 0));
		if (!$appartement) {
			return new JsonResponse(['error' => 'Appartement non trouvé'], 404);
		}

		$dateDebut = new \DateTime($data['date_debut']);
		$dateFin   = new \DateTime($data['date_fin']);

		if ($dateFin <= $dateDebut) {
			return new JsonResponse(['error' => 'La date de fin doit être après la date de début.'], 400);
		}

		// Vérification anti-chevauchement
		$chevauchements = $tarifRepo->findChevauchements($appartement->getId(), $dateDebut, $dateFin);
		if (!empty($chevauchements)) {
			$noms = array_map(fn($t) => $t->getSaison() . ' (' . $t->getDateDebut()->format('d/m/Y') . ' → ' . $t->getDateFin()->format('d/m/Y') . ')', $chevauchements);
			return new JsonResponse([
				'error' => 'Chevauchement avec : ' . implode(', ', $noms),
			], 409);
		}

		$tarif = new Tarif();
		$tarif->setAppartement($appartement);
		$tarif->setSaison(strip_tags(trim($data['saison'] ?? '')));
		$tarif->setDateDebut($dateDebut);
		$tarif->setDateFin($dateFin);
		$tarif->setPrixJour((float) ($data['prix_jour'] ?? 0));
		$tarif->setPrixSemaine((float) ($data['prix_semaine'] ?? 0));
		$tarif->setPrixMois((float) ($data['prix_mois'] ?? 0));

		$em->persist($tarif);
		$em->flush();

		return new JsonResponse([
			'id'      => $tarif->getId(),
			'message' => 'Tarif créé avec succès',
		], 201);
	}

	/**
	 * API : modifier un tarif
	 */
	#[Route('/api/tarif/{id}', name: 'api_tarif_update', methods: ['PUT'])]
	public function updateTarif(
		int $id,
		Request $request,
		TarifRepository $tarifRepo,
		EntityManagerInterface $em
	): JsonResponse {
		if (!$this->isAjaxRequest($request)) {
			return new JsonResponse(['error' => 'Accès interdit'], 403);
		}

		$tarif = $tarifRepo->find($id);
		if (!$tarif) {
			return new JsonResponse(['error' => 'Tarif non trouvé'], 404);
		}

		$data = json_decode($request->getContent(), true);
		if (!is_array($data)) {
			return new JsonResponse(['error' => 'Données invalides'], 400);
		}

		$dateDebut = new \DateTime($data['date_debut']);
		$dateFin   = new \DateTime($data['date_fin']);

		if ($dateFin <= $dateDebut) {
			return new JsonResponse(['error' => 'La date de fin doit être après la date de début.'], 400);
		}

		// Vérification anti-chevauchement (en excluant le tarif courant)
		$chevauchements = $tarifRepo->findChevauchements(
			$tarif->getAppartement()->getId(), $dateDebut, $dateFin, $tarif->getId()
		);
		if (!empty($chevauchements)) {
			$noms = array_map(fn($t) => $t->getSaison(), $chevauchements);
			return new JsonResponse([
				'error' => 'Chevauchement avec : ' . implode(', ', $noms),
			], 409);
		}

		$tarif->setSaison(strip_tags(trim($data['saison'] ?? $tarif->getSaison())));
		$tarif->setDateDebut($dateDebut);
		$tarif->setDateFin($dateFin);
		$tarif->setPrixJour((float) ($data['prix_jour'] ?? $tarif->getPrixJour()));
		$tarif->setPrixSemaine((float) ($data['prix_semaine'] ?? $tarif->getPrixSemaine()));
		$tarif->setPrixMois((float) ($data['prix_mois'] ?? $tarif->getPrixMois()));

		$em->flush();

		return new JsonResponse(['message' => 'Tarif mis à jour']);
	}

	/**
	 * API : supprimer un tarif
	 */
	#[Route('/api/tarif/{id}', name: 'api_tarif_delete', methods: ['DELETE'])]
	public function deleteTarif(
		int $id,
		TarifRepository $tarifRepo,
		Request $request,
		EntityManagerInterface $em
	): JsonResponse {
		if (!$this->isAjaxRequest($request)) {
			return new JsonResponse(['error' => 'Accès interdit'], 403);
		}

		$tarif = $tarifRepo->find($id);
		if (!$tarif) {
			return new JsonResponse(['error' => 'Tarif non trouvé'], 404);
		}

		$em->remove($tarif);
		$em->flush();

		return new JsonResponse(['message' => 'Tarif supprimé']);
	}

	/**
	 * Page de gestion des tarifs d'un appartement spécifique (ancienne route conservée)
	 */
	#[Route('/appartement/{id}/tarifs', name: 'appartement_tarifs')]
	public function tarifsAppartement(
		Appartement $appartement,
		Request $request,
		TarifRepository $tarifRepo,
		EntityManagerInterface $em
	): Response {
		$tarif = new Tarif();
		$tarif->setAppartement($appartement);

		$form = $this->createForm(TarifType::class, $tarif);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			// Vérification anti-chevauchement
			$chevauchements = $tarifRepo->findChevauchements(
				$appartement->getId(),
				$tarif->getDateDebut(),
				$tarif->getDateFin()
			);

			if (!empty($chevauchements)) {
				$this->addFlash('danger', 'Ce tarif chevauche une saison existante.');
			} else {
				$em->persist($tarif);
				$em->flush();
				$this->addFlash('success', 'Tarif ajouté avec succès.');
			}

			return $this->redirectToRoute('admin_appartement_tarifs', [
				'id' => $appartement->getId(),
			]);
		}

		return $this->render('admin/tarifs.html.twig', [
			'form'         => $form->createView(),
			'appartement'  => $appartement,
			'appartements' => [$appartement],
			'tarifs'       => $tarifRepo->findByAppartement($appartement->getId()),
		]);
	}

}
