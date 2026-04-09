<?php

namespace App\Command;

use App\Repository\ReservationRepository;
use App\Service\MailerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author      Florian Aizac
 * @created     09/04/2026
 * @description Commande CRON envoyant des emails de rappel 3 jours avant l'arrivée du client
 *              Usage : php bin/console app:send-rappels-reservations
 *              CRON  : 0 9 * * * (tous les jours à 09h00)
 */
#[AsCommand(
	name: 'app:send-rappels-reservations',
	description: 'Envoie les emails de rappel pour les réservations confirmées à J-3',
)]
class SendRappelsReservationsCommand extends Command
{
	public function __construct(
		private ReservationRepository $reservationRepo,
		private MailerService $mailerService,
		private LoggerInterface $logger
	) {
		parent::__construct();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$io = new SymfonyStyle($input, $output);
		$io->title('Envoi des rappels de réservation (J-3)');

		// Récupérer les réservations confirmées dans les 3 prochains jours
		$dateCible = (new \DateTime('today'))->modify('+3 days');
		$reservations = $this->reservationRepo->createQueryBuilder('r')
			->andWhere('r.statut = :statut')
			->andWhere('r.dateArrivee = :dateCible')
			->setParameter('statut', 'confirmee')
			->setParameter('dateCible', $dateCible->format('Y-m-d'))
			->getQuery()
			->getResult();

		$envoyés = 0;
		$erreurs = 0;

		foreach ($reservations as $reservation) {
			try {
				$this->mailerService->sendRappelReservation($reservation);
				$envoyés++;
				$io->text(sprintf(
					'✓ Rappel envoyé à %s %s (%s) — %s',
					$reservation->getPrenom(),
					$reservation->getNom(),
					$reservation->getEmail(),
					$reservation->getAppartement()->getNom()
				));
			} catch (\Exception $e) {
				$erreurs++;
				$this->logger->error(sprintf(
					'Erreur envoi rappel réservation #%d : %s',
					$reservation->getId(),
					$e->getMessage()
				));
				$io->error(sprintf('✗ Erreur pour réservation #%d : %s', $reservation->getId(), $e->getMessage()));
			}
		}

		$io->success(sprintf(
			'Terminé : %d rappel(s) envoyé(s), %d erreur(s) sur %d réservation(s) trouvée(s).',
			$envoyés,
			$erreurs,
			count($reservations)
		));

		return $erreurs > 0 ? Command::FAILURE : Command::SUCCESS;
	}
}
