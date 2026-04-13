<?php

namespace App\Command;

use App\Entity\Disponibilite;
use App\Repository\AppartementRepository;
use App\Repository\DisponibiliteRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
	name: 'app:clean-disponibilites',
	description: 'Nettoie les chevauchements entre Disponibilités et Réservations/autres statuts',
)]
class CleanDisponibilitesCommand extends Command
{
	public function __construct(
		private EntityManagerInterface $em,
		private AppartementRepository $appartementRepo,
		private DisponibiliteRepository $disponibiliteRepo,
		private ReservationRepository $reservationRepo,
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simuler sans modifier la base');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$io = new SymfonyStyle($input, $output);
		$dryRun = $input->getOption('dry-run');

		if ($dryRun) {
			$io->note('Mode simulation (dry-run) — aucune modification ne sera effectuée.');
		}

		$appartements = $this->appartementRepo->findAll();
		$start = new \DateTime('2024-01-01');
		$end = new \DateTime('+2 years');

		$totalSupprimees = 0;
		$totalCreees = 0;

		foreach ($appartements as $appart) {
			$io->section(sprintf('Appartement #%d — %s', $appart->getId(), $appart->getNom()));

			// ── Collecter TOUTES les périodes non-disponibles ──
			$periodesOccupees = [];

			// 1. Réservations confirmées + nettoyage
			$reservations = $this->reservationRepo->findConfirmeesParAppartement(
				$appart->getId(), $start, $end
			);
			foreach ($reservations as $res) {
				$periodesOccupees[] = [
					'start' => clone $res->getDateArrivee(),
					'end'   => (clone $res->getDateDepart())->modify('+1 day'),
					'label' => sprintf('Réservation #%d (%s → %s)',
						$res->getId(),
						$res->getDateArrivee()->format('d/m/Y'),
						$res->getDateDepart()->format('d/m/Y')
					),
				];
			}

			// 2. Disponibilités NON-disponibles (bloqué, nettoyage, réservé admin)
			$allDispos = $this->disponibiliteRepo->findByAppartementAndPeriode(
				$appart->getId(), $start, $end
			);
			foreach ($allDispos as $d) {
				if ($d->getStatut() !== Disponibilite::STATUT_DISPONIBLE) {
					$periodesOccupees[] = [
						'start' => clone $d->getDateDebut(),
						'end'   => (clone $d->getDateFin())->modify('+1 day'),
						'label' => sprintf('%s #%d (%s → %s)',
							$d->getStatutLabel(),
							$d->getId(),
							$d->getDateDebut()->format('d/m/Y'),
							$d->getDateFin()->format('d/m/Y')
						),
					];
				}
			}

			if (empty($periodesOccupees)) {
				$io->text('  Aucune période occupée, pas de nettoyage nécessaire.');
				continue;
			}

			usort($periodesOccupees, fn($a, $b) => $a['start'] <=> $b['start']);

			// ── Vérifier et découper chaque "Disponible" ──
			foreach ($allDispos as $dispo) {
				if ($dispo->getStatut() !== Disponibilite::STATUT_DISPONIBLE) {
					continue;
				}

				$dispoStart = clone $dispo->getDateDebut();
				$dispoEnd   = clone $dispo->getDateFin();

				// Vérifier si cette dispo chevauche une période occupée
				$chevauche = false;
				foreach ($periodesOccupees as $occ) {
					if ($dispoStart < $occ['end'] && $dispoEnd >= $occ['start']) {
						$chevauche = true;
						$io->text(sprintf('    ↳ chevauche: %s', $occ['label']));
					}
				}

				if (!$chevauche) {
					continue;
				}

				$io->text(sprintf('  ⚠ Disponibilité #%d [%s → %s] → découpage',
					$dispo->getId(),
					$dispoStart->format('d/m/Y'),
					$dispoEnd->format('d/m/Y')
				));

				$segments = $this->decouperPlage($dispoStart, $dispoEnd, $periodesOccupees);

				if (!$dryRun) {
					$this->em->remove($dispo);
					$this->em->flush();
					$totalSupprimees++;

					foreach ($segments as $seg) {
						$nouveau = new Disponibilite();
						$nouveau->setAppartement($appart);
						$nouveau->setDateDebut($seg['start']);
						$nouveau->setDateFin($seg['end']);
						$nouveau->setStatut(Disponibilite::STATUT_DISPONIBLE);
						$nouveau->setNote($dispo->getNote());
						$this->em->persist($nouveau);
						$totalCreees++;

						$io->text(sprintf('    ✅ Segment : [%s → %s]',
							$seg['start']->format('d/m/Y'),
							$seg['end']->format('d/m/Y')
						));
					}

					$this->em->flush();
				} else {
					$totalSupprimees++;
					foreach ($segments as $seg) {
						$totalCreees++;
						$io->text(sprintf('    [dry-run] Segment : [%s → %s]',
							$seg['start']->format('d/m/Y'),
							$seg['end']->format('d/m/Y')
						));
					}
				}
			}
		}

		$io->success(sprintf(
			'%s — %d disponibilité(s) supprimée(s), %d segment(s) créé(s).',
			$dryRun ? 'SIMULATION' : 'TERMINÉ',
			$totalSupprimees,
			$totalCreees
		));

		return Command::SUCCESS;
	}

	/**
	 * Découpe [plageStart, plageEnd] en retirant les périodes occupées.
	 * periodesOccupees[]['end'] est exclusif (jour après le dernier occupé).
	 */
	private function decouperPlage(
		\DateTimeInterface $plageStart,
		\DateTimeInterface $plageEnd,
		array $periodesOccupees
	): array {
		$segments = [];
		$cursor = clone $plageStart;

		foreach ($periodesOccupees as $occ) {
			$occStart = $occ['start'];
			$occEnd   = $occ['end'];

			if ($occEnd <= $cursor) {
				continue;
			}
			if ($occStart > $plageEnd) {
				break;
			}

			if ($cursor < $occStart) {
				$segEnd = (clone $occStart)->modify('-1 day');
				if ($segEnd >= $cursor) {
					$segments[] = [
						'start' => clone $cursor,
						'end'   => $segEnd,
					];
				}
			}

			if ($occEnd > $cursor) {
				$cursor = clone $occEnd;
			}
		}

		if ($cursor <= $plageEnd) {
			$segments[] = [
				'start' => clone $cursor,
				'end'   => clone $plageEnd,
			];
		}

		return $segments;
	}
}
