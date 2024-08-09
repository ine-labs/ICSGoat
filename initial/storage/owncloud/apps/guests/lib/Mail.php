<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Guests;

use OCP\Defaults;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Mail\IMailer;
use OCP\Share;
use OCP\Template;
use OCP\Util;

class Mail {

	/** @var ILogger */
	private $logger;

	/** @var IUserSession */
	private $userSession;

	/** @var IMailer */
	private $mailer;

	/** @var Defaults */
	private $defaults;

	/** @var IL10N */
	private $l10n;

	/** @var  IURLGenerator */
	private $urlGenerator;
	/**
	 * @var IUserManager
	 */
	private $userManager;

	public function __construct(
		ILogger $logger,
		IUserSession $userSession,
		IMailer $mailer,
		Defaults $defaults,
		IL10N $l10n,
		IUserManager $userManager,
		IURLGenerator $urlGenerator
	) {
		$this->logger = $logger;
		$this->userSession = $userSession;
		$this->mailer = $mailer;
		$this->defaults = $defaults;
		$this->l10n = $l10n;
		$this->userManager = $userManager;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * Sends out a reset password mail if the user is a guest and does not have
	 * a password set, yet.
	 *
	 * @param Share\IShare $share
	 * @param string $uid
	 * @param string $token
	 *
	 * @throws \Exception
	 */
	public function sendGuestInviteMail(Share\IShare $share, $uid, $token) {
		$shareWith = $share->getSharedWith();
		$shareWithEmail = $this->userManager->get($shareWith)->getEMailAddress();
		$replyTo = $this->userManager->get($uid)->getEMailAddress();
		$senderDisplayName = $this->userSession->getUser()->getDisplayName();

		$registerLink = $this->urlGenerator->linkToRouteAbsolute(
			'guests.register.showPasswordForm',
			['email' => $shareWithEmail, 'token' => $token]
		);

		$this->logger->debug("sending invite to $shareWith: $registerLink", ['app' => 'guests']);

		$filename = \trim($share->getTarget(), '/');
		$subject = (string)$this->l10n->t('%s shared »%s« with you', [$senderDisplayName, $filename]);
		$expiration = $share->getExpirationDate();
		if ($expiration instanceof \DateTime) {
			try {
				$expiration = $expiration->getTimestamp();
			} catch (\Exception $e) {
				$this->logger->error("Couldn't read date: " . $e->getMessage(), ['app' => 'sharing']);
			}
		}

		$link = $this->urlGenerator->linkToRouteAbsolute(
			'files.viewcontroller.showFile',
			['fileId' => $share->getNode()->getId()]
		);

		list($htmlBody, $textBody) = $this->createMailBody(
			$filename,
			$link,
			$registerLink,
			$this->defaults->getName(),
			$senderDisplayName,
			$expiration,
			$shareWithEmail
		);

		try {
			$message = $this->mailer->createMessage();
			$message->setTo([$shareWithEmail => $shareWith]);
			$message->setSubject($subject);
			$message->setHtmlBody($htmlBody);
			$message->setPlainBody($textBody);
			$message->setFrom([
				Util::getDefaultEmailAddress('sharing-noreply') =>
					(string)$this->l10n->t('%s via %s', [
						$senderDisplayName,
						$this->defaults->getName()
					]),
			]);

			if ($replyTo !== null) {
				$message->setReplyTo([$replyTo]);
			}

			$this->mailer->send($message);
		} catch (\Exception $e) {
			$this->logger->error("Failed to send reset email: " . $e->getMessage(), ['app' => 'guests']);
			throw new \Exception($this->l10n->t(
				'Couldn\'t send reset email. Please contact your administrator.'
			));
		}
	}

	/**
	 * create mail body for plain text and html mail
	 *
	 * @param string $filename the shared file
	 * @param string $link link to the shared file
	 * @param int $expiration expiration date (timestamp)
	 * @param string $guestEmail
	 * @return array an array of the html mail body and the plain text mail body
	 */
	private function createMailBody($filename, $link, $passwordLink, $cloudName, $displayName, $expiration, $guestEmail) {
		$formattedDate = $expiration ? $this->l10n->l('date', $expiration) : null;

		$html = new Template('guests', 'mail/invite');
		$html->assign('link', $link);
		$html->assign('password_link', $passwordLink);
		$html->assign('cloud_name', $cloudName);
		$html->assign('user_displayname', $displayName);
		$html->assign('filename', $filename);
		$html->assign('expiration', $formattedDate);
		$html->assign('guestEmail', $guestEmail);
		$htmlMail = $html->fetchPage();

		$plainText = new Template('guests', 'mail/altinvite');
		$plainText->assign('link', $link);
		$plainText->assign('password_link', $passwordLink);
		$plainText->assign('cloud_name', $cloudName);
		$plainText->assign('user_displayname', $displayName);
		$plainText->assign('filename', $filename);
		$plainText->assign('expiration', $formattedDate);
		$plainText->assign('guestEmail', $guestEmail);
		$plainTextMail = $plainText->fetchPage();

		return [$htmlMail, $plainTextMail];
	}
}
