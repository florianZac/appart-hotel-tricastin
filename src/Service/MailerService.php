<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\Reservation;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * @author      Florian Aizac
 * @created     09/04/2026
 * @description Service gérant l'envoi de tous les emails de l'application Appart Hôtel Tricastin
 *
 *  1. sendContactEmail()                : Email de contact reçu par l'admin
 *  2. sendConfirmationReservation()     : Confirmation de réservation au client
 *  3. sendRappelReservation()           : Rappel J-3 avant arrivée
 *  4. sendReservationAnnuleeEmail()     : Notification d'annulation
 *  5. sendPaiementConfirmationEmail()   : Confirmation de paiement Stripe
 *  6. sendPaiementEcheanceEmail()       : Rappel d'échéance de paiement
 *  7. sendWelcomeEmail()                : Email de bienvenue à l'inscription
 */
class MailerService
{
	private const NOREPLY_EMAIL = 'noreply@appart-hotel-tricastin.fr';
	private const ADMIN_EMAIL   = 'contact@appart-hotel-tricastin.fr';
	private const SITE_NAME     = 'Appart Hôtel Tricastin';

	public function __construct(
		private MailerInterface $mailer,
		private Environment $twig,
		private string $appUrl
	) {}

	/**
	 * Email de contact envoyé à l'admin
	 */
	public function sendContactEmail(array $data): void
	{
		$html = $this->twig->render('emails/contact.html.twig', [
			'sujet'   => $data['sujet'],
			'email'   => $data['email'],
			'message' => $data['message'],
		]);

		$email = (new Email())
			->from(self::NOREPLY_EMAIL)
			->to(self::ADMIN_EMAIL)
			->subject('[Contact] ' . ($data['sujet'] ?? 'Nouveau message'))
			->html($html);

		$this->mailer->send($email);
	}

	/**
	 * Confirmation de réservation envoyée au client
	 */
	public function sendConfirmationReservation(Reservation $reservation): void
	{
		$html = $this->twig->render('emails/confirmation_reservation.html.twig', [
			'reservation' => $reservation,
			'app_url'     => $this->appUrl,
		]);

		$destinataire = $reservation->getUser()
			? $reservation->getUser()->getEmail()
			: $reservation->getEmail();

		$email = (new Email())
			->from(self::NOREPLY_EMAIL)
			->to($destinataire)
			->subject(sprintf(
				'Confirmation de réservation — %s du %s au %s',
				$reservation->getAppartement()->getNom(),
				$reservation->getDateArrivee()->format('d/m/Y'),
				$reservation->getDateDepart()->format('d/m/Y')
			))
			->html($html);

		$this->mailer->send($email);
	}

	/**
	 * Rappel envoyé 3 jours avant l'arrivée
	 */
	public function sendRappelReservation(Reservation $reservation): void
	{
		$html = $this->twig->render('emails/rappel_reservation.html.twig', [
			'reservation' => $reservation,
			'app_url'     => $this->appUrl,
		]);

		$destinataire = $reservation->getUser()
			? $reservation->getUser()->getEmail()
			: $reservation->getEmail();

		$email = (new Email())
			->from(self::NOREPLY_EMAIL)
			->to($destinataire)
			->subject(sprintf(
				'Rappel : votre arrivée le %s — %s',
				$reservation->getDateArrivee()->format('d/m/Y'),
				$reservation->getAppartement()->getNom()
			))
			->html($html);

		$this->mailer->send($email);
	}

	/**
	 * Notification d'annulation
	 */
	public function sendReservationAnnuleeEmail(Reservation $reservation): void
	{
		$html = $this->twig->render('emails/annulation_reservation.html.twig', [
			'reservation' => $reservation,
			'app_url'     => $this->appUrl,
		]);

		$destinataire = $reservation->getUser()
			? $reservation->getUser()->getEmail()
			: $reservation->getEmail();

		$email = (new Email())
			->from(self::NOREPLY_EMAIL)
			->to($destinataire)
			->subject('Annulation de votre réservation — ' . self::SITE_NAME)
			->html($html);

		$this->mailer->send($email);
	}

	/**
	 * Confirmation de paiement Stripe
	 */
	public function sendPaiementConfirmationEmail(Payment $payment): void
	{
		$html = $this->twig->render('emails/confirmation_paiement.html.twig', [
			'payment' => $payment,
			'app_url' => $this->appUrl,
		]);

		$email = (new Email())
			->from(self::NOREPLY_EMAIL)
			->to($payment->getUser()->getEmail())
			->subject(sprintf(
				'Confirmation de paiement — %s — %.2f€',
				$payment->getTypeLabel(),
				$payment->getMontant()
			))
			->html($html);

		$this->mailer->send($email);
	}

	/**
	 * Rappel d'échéance de paiement (loyer, charges, etc.)
	 */
	public function sendPaiementEcheanceEmail(Payment $payment): void
	{
		$html = $this->twig->render('emails/echeance_paiement.html.twig', [
			'payment' => $payment,
			'app_url' => $this->appUrl,
		]);

		$email = (new Email())
			->from(self::NOREPLY_EMAIL)
			->to($payment->getUser()->getEmail())
			->subject(sprintf(
				'Échéance de paiement — %s — %.2f€',
				$payment->getTypeLabel(),
				$payment->getMontant()
			))
			->html($html);

		$this->mailer->send($email);
	}

	/**
	 * Email de bienvenue à l'inscription
	 */
	public function sendWelcomeEmail(User $user): void
	{
		$html = $this->twig->render('emails/bienvenue.html.twig', [
			'user'    => $user,
			'app_url' => $this->appUrl,
		]);

		$email = (new Email())
			->from(self::NOREPLY_EMAIL)
			->to($user->getEmail())
			->subject('Bienvenue chez ' . self::SITE_NAME)
			->html($html);

		$this->mailer->send($email);
	}

	/**
	 * Notification admin : nouvelle réservation reçue
	 */
	public function sendNouvelleReservationAdmin(Reservation $reservation): void
	{
		$html = $this->twig->render('emails/admin_nouvelle_reservation.html.twig', [
			'reservation' => $reservation,
			'app_url'     => $this->appUrl,
		]);

		$email = (new Email())
			->from(self::NOREPLY_EMAIL)
			->to(self::ADMIN_EMAIL)
			->subject(sprintf(
				'[Nouvelle réservation] %s %s — %s',
				$reservation->getPrenom(),
				$reservation->getNom(),
				$reservation->getAppartement()->getNom()
			))
			->html($html);

		$this->mailer->send($email);
	}

	/**
	 * Email de réinitialisation du mot de passe (Point 7)
	 */
	public function sendPasswordResetEmail(User $user, string $token): void
	{
		$html = $this->twig->render('emails/reset_password.html.twig', [
			'user'      => $user,
			'token'     => $token,
			'app_url'   => $this->appUrl,
			'reset_url' => $this->appUrl . '/reinitialiser-mot-de-passe/' . $token,
		]);

		$email = (new Email())
			->from(self::NOREPLY_EMAIL)
			->to($user->getEmail())
			->subject('Réinitialisation de votre mot de passe — ' . self::SITE_NAME)
			->html($html);

		$this->mailer->send($email);
	}
}
