<?php

namespace App\Command;

use App\Repository\ReservationRepository;
use App\Repository\TemoignageRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author      Florian Aizac
 * @created     12/04/2026
 * @description Commande CRON envoyant un email de demande d'avis aux clients
 *              dont le séjour est terminé depuis au moins 1 jour
 *              Usage : php bin/console app:send-demande-avis
 *              CRON  : 0 10 * * * (tous les jours à 10h00)
 */
#[AsCommand(
	name: 'app:send-demande-avis',
	description: 'Envoie un email de demande d\'avis aux clients dont le séjour est terminé',
)]
class SendDemandeAvisCommand extends Command
{
	public function __construct(
		private ReservationRepository $reservationRepo,
		private TemoignageRepository $temoignageRepo,
		private MailerService $mailerService,
		private EntityManagerInterface $em,
		private LoggerInterface $logger,
	) {
		parent::__construct();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$io = new SymfonyStyle($input, $output);
		$io->title('Envoi des demandes d\'avis post-séjour');

		$hier = new \DateTime('-1 day');
		$reservations = $this->reservationRepo->findSejoursTerminesSansDemandeAvis($hier);

		$envoyes = 0;
		$erreurs = 0;

		foreach ($reservations as $reservation) {
			$user = $reservation->getUser();
			if (!$user) {
				continue;
			}

			// Vérifie qu'un témoignage n'existe pas déjà
			$dejaFait = $this->temoignageRepo->findByUserAndReservation($user, $reservation);
			if ($dejaFait) {
				// Marque comme envoyé pour ne plus le retraiter
				$reservation->setAvisEmailEnvoye(true);
				continue;
			}

			try {
				$this->mailerService->sendDemandeAvis($reservation);

				$reservation->setAvisEmailEnvoye(true);
				$reservation->setAvisEmailEnvoyeAt(new \DateTime());

				$envoyes++;
				$io->text(sprintf(
					'✓ Demande envoyée à %s %s (%s) — %s',
					$reservation->getPrenom(),
					$reservation->getNom(),
					$user->getEmail(),
					$reservation->getAppartement()->getNom()
				));
			} catch (\Exception $e) {
				$erreurs++;
				$this->logger->error(sprintf(
					'Erreur envoi demande avis réservation #%d : %s',
					$reservation->getId(),
					$e->getMessage()
				));
				$io->error(sprintf(
					'✗ Erreur pour réservation #%d : %s',
					$reservation->getId(),
					$e->getMessage()
				));
			}
		}

		$this->em->flush();

		$io->success(sprintf(
			'Terminé : %d demande(s) envoyée(s), %d erreur(s) sur %d réservation(s) trouvée(s).',
			$envoyes,
			$erreurs,
			count($reservations)
		));

		return $erreurs > 0 ? Command::FAILURE : Command::SUCCESS;
	}
}
