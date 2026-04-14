<?php

namespace App\Controller;

use App\Repository\AppartementRepository;
use App\Service\TarifCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TarifController extends AbstractController
{
	/**
	 * API publique : estimation du prix pour un séjour.
	 * Utilisé par le formulaire de réservation côté client.
	 *
	 * GET /api/estimation-prix?appartement_id=61&date_arrivee=2026-06-01&date_depart=2026-06-15
	 */
	#[Route('/api/estimation-prix', name: 'api_estimation_prix', methods: ['GET'])]
	public function estimationPrix(
		Request $request,
		AppartementRepository $appartementRepo,
		TarifCalculator $calculator
	): JsonResponse {
		$appartementId = (int) $request->query->get('appartement_id', 0);
		$dateArrivee   = $request->query->get('date_arrivee', '');
		$dateDepart    = $request->query->get('date_depart', '');

		if (!$appartementId || !$dateArrivee || !$dateDepart) {
			return new JsonResponse(['error' => 'Paramètres manquants'], 400);
		}

		$appartement = $appartementRepo->find($appartementId);
		if (!$appartement) {
			return new JsonResponse(['error' => 'Appartement non trouvé'], 404);
		}

		try {
			$arrivee = new \DateTime($dateArrivee);
			$depart  = new \DateTime($dateDepart);
		} catch (\Exception $e) {
			return new JsonResponse(['error' => 'Dates invalides'], 400);
		}

		if ($depart <= $arrivee) {
			return new JsonResponse(['error' => 'La date de départ doit être après la date d\'arrivée.'], 400);
		}

		$result = $calculator->calculerPrix($appartement, $arrivee, $depart);

		return new JsonResponse([
			'total'       => $result['total'],
			'nombreNuits' => $result['nombreNuits'],
			'details'     => $result['details'],
			'formatted'   => number_format($result['total'], 2, ',', ' ') . ' €',
		]);
	}

	/**
	 * API publique : retourne les tarifs affichables pour un appartement.
	 * Utilisé par la page de détail de l'appartement.
	 */
	#[Route('/api/tarifs-publics/{appartementId}', name: 'api_tarifs_publics', methods: ['GET'])]
	public function tarifsPublics(
		int $appartementId,
		AppartementRepository $appartementRepo
	): JsonResponse {
		$appartement = $appartementRepo->find($appartementId);
		if (!$appartement) {
			return new JsonResponse([], Response::HTTP_NOT_FOUND);
		}

		$tarifs = $appartement->getTarifs();
		$data = [];

		foreach ($tarifs as $tarif) {
			// Ne montrer que les tarifs futurs ou en cours
			if ($tarif->getDateFin() >= new \DateTime('today')) {
				$data[] = [
					'saison'      => $tarif->getSaison(),
					'dateDebut'   => $tarif->getDateDebut()->format('d/m/Y'),
					'dateFin'     => $tarif->getDateFin()->format('d/m/Y'),
					'prixJour'    => $tarif->getPrixJour(),
					'prixSemaine' => $tarif->getPrixSemaine(),
					'prixMois'    => $tarif->getPrixMois(),
				];
			}
		}

		// Ajouter le tarif par défaut si aucun tarif saisonnier
		if (empty($data)) {
			$data[] = [
				'saison'      => 'Standard',
				'dateDebut'   => null,
				'dateFin'     => null,
				'prixJour'    => (float) $appartement->getPrixParNuit(),
				'prixSemaine' => (float) $appartement->getPrixParNuit() * 7,
				'prixMois'    => (float) $appartement->getPrixParNuit() * 30,
			];
		}

		return new JsonResponse($data);
	}
}
