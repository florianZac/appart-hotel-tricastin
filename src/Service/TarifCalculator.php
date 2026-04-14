<?php

namespace App\Service;

use App\Entity\Appartement;
use App\Entity\Tarif;
use App\Repository\TarifRepository;

/**
 * Calcule le prix d'un séjour en tenant compte des saisons,
 * avec optimisation mois/semaine/jour et gestion multi-saisons.
 */
class TarifCalculator
{
	public function __construct(
		private TarifRepository $tarifRepo
	) {}

	/**
	 * Calcule le prix total d'un séjour.
	 *
	 * @return array{
	 *   total: float,
	 *   details: array<int, array{saison: string, jours: int, prixJour: float, prixSemaine: float, prixMois: float, montant: float, detail: string}>,
	 *   nombreNuits: int
	 * }
	 */
	public function calculerPrix(
		Appartement $appartement,
		\DateTimeInterface $dateArrivee,
		\DateTimeInterface $dateDepart
	): array {
		$appartementId = $appartement->getId();
		$prixParNuitDefaut = (float) $appartement->getPrixParNuit();

		// 1. Construire la liste jour par jour avec le tarif applicable
		$segments = $this->construireSegments($appartementId, $dateArrivee, $dateDepart, $prixParNuitDefaut);

		// 2. Regrouper les jours consécutifs qui partagent le même tarif
		$groupes = $this->regrouperParTarif($segments);

		// 3. Pour chaque groupe, calculer le prix optimal (mois/semaine/jour)
		$total = 0.0;
		$details = [];

		foreach ($groupes as $groupe) {
			$nbJours = $groupe['jours'];
			$prixJour = $groupe['prixJour'];
			$prixSemaine = $groupe['prixSemaine'];
			$prixMois = $groupe['prixMois'];
			$saison = $groupe['saison'];

			$result = $this->calculerOptimal($nbJours, $prixJour, $prixSemaine, $prixMois);

			$details[] = [
				'saison'      => $saison,
				'jours'       => $nbJours,
				'prixJour'    => $prixJour,
				'prixSemaine' => $prixSemaine,
				'prixMois'    => $prixMois,
				'montant'     => $result['montant'],
				'detail'      => $result['detail'],
			];

			$total += $result['montant'];
		}

		$nombreNuits = (int) $dateDepart->diff($dateArrivee)->days;

		return [
			'total'       => round($total, 2),
			'details'     => $details,
			'nombreNuits' => $nombreNuits,
		];
	}

	/**
	 * Calcul rapide du montant total uniquement (sans détails).
	 */
	public function calculerMontantTotal(
		Appartement $appartement,
		\DateTimeInterface $dateArrivee,
		\DateTimeInterface $dateDepart
	): float {
		$result = $this->calculerPrix($appartement, $dateArrivee, $dateDepart);
		return $result['total'];
	}

	/**
	 * Construit un tableau jour par jour avec le tarif applicable.
	 */
	private function construireSegments(
		int $appartementId,
		\DateTimeInterface $dateArrivee,
		\DateTimeInterface $dateDepart,
		float $prixParNuitDefaut
	): array {
		$segments = [];
		$cursor = clone $dateArrivee;
		$end = clone $dateDepart;

		while ($cursor < $end) {
			$tarif = $this->tarifRepo->findTarifForDate($appartementId, $cursor);

			if ($tarif) {
				$segments[] = [
					'date'        => clone $cursor,
					'tarifId'     => $tarif->getId(),
					'saison'      => $tarif->getSaison(),
					'prixJour'    => $tarif->getPrixJour(),
					'prixSemaine' => $tarif->getPrixSemaine(),
					'prixMois'    => $tarif->getPrixMois(),
				];
			} else {
				// Fallback sur le prix par nuit de l'appartement
				$segments[] = [
					'date'        => clone $cursor,
					'tarifId'     => 0,
					'saison'      => 'Standard',
					'prixJour'    => $prixParNuitDefaut,
					'prixSemaine' => $prixParNuitDefaut * 7,
					'prixMois'    => $prixParNuitDefaut * 30,
				];
			}

			$cursor = (clone $cursor)->modify('+1 day');
		}

		return $segments;
	}

	/**
	 * Regroupe les jours consécutifs partageant le même tarifId.
	 */
	private function regrouperParTarif(array $segments): array
	{
		if (empty($segments)) {
			return [];
		}

		$groupes = [];
		$currentGroup = [
			'tarifId'     => $segments[0]['tarifId'],
			'saison'      => $segments[0]['saison'],
			'prixJour'    => $segments[0]['prixJour'],
			'prixSemaine' => $segments[0]['prixSemaine'],
			'prixMois'    => $segments[0]['prixMois'],
			'jours'       => 1,
		];

		for ($i = 1; $i < count($segments); $i++) {
			if ($segments[$i]['tarifId'] === $currentGroup['tarifId']) {
				$currentGroup['jours']++;
			} else {
				$groupes[] = $currentGroup;
				$currentGroup = [
					'tarifId'     => $segments[$i]['tarifId'],
					'saison'      => $segments[$i]['saison'],
					'prixJour'    => $segments[$i]['prixJour'],
					'prixSemaine' => $segments[$i]['prixSemaine'],
					'prixMois'    => $segments[$i]['prixMois'],
					'jours'       => 1,
				];
			}
		}

		$groupes[] = $currentGroup;

		return $groupes;
	}

	/**
	 * Calcule le prix optimal pour N jours avec mix mois/semaine/jour.
	 * Compare le prix linéaire (jour×N) vs le mix pour prendre le moins cher.
	 */
	private function calculerOptimal(float $nbJours, float $prixJour, float $prixSemaine, float $prixMois): array
	{
		// Prix simple au jour
		$prixLineaire = $nbJours * $prixJour;

		// Prix avec mix optimal
		$restant = (int) $nbJours;
		$montant = 0.0;
		$parts = [];

		// Mois complets (si un mois est avantageux vs 30 × prixJour)
		if ($restant >= 30 && $prixMois < (30 * $prixJour)) {
			$nbMois = intdiv($restant, 30);
			$montant += $nbMois * $prixMois;
			$restant -= $nbMois * 30;
			$parts[] = $nbMois . ' mois × ' . number_format($prixMois, 2) . '€';
		}

		// Semaines complètes (si une semaine est avantageuse vs 7 × prixJour)
		if ($restant >= 7 && $prixSemaine < (7 * $prixJour)) {
			$nbSemaines = intdiv($restant, 7);
			$montant += $nbSemaines * $prixSemaine;
			$restant -= $nbSemaines * 7;
			$parts[] = $nbSemaines . ' sem. × ' . number_format($prixSemaine, 2) . '€';
		}

		// Jours restants
		if ($restant > 0) {
			$montant += $restant * $prixJour;
			$parts[] = $restant . ' nuit' . ($restant > 1 ? 's' : '') . ' × ' . number_format($prixJour, 2) . '€';
		}

		// Comparer : si le prix linéaire est moins cher, on le prend
		if ($prixLineaire <= $montant) {
			return [
				'montant' => round($prixLineaire, 2),
				'detail'  => (int) $nbJours . ' nuit' . ($nbJours > 1 ? 's' : '') . ' × ' . number_format($prixJour, 2) . '€',
			];
		}

		return [
			'montant' => round($montant, 2),
			'detail'  => implode(' + ', $parts),
		];
	}
}
