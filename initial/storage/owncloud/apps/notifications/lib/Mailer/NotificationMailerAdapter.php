<?php
/**
 * @author Juan Pablo Villafáñez <jvillafanez@solidgear.es>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Notifications\Mailer;

use OCP\Notification\INotification;
use OCP\IUserManager;
use OCP\ILogger;
use OCP\IURLGenerator;

/**
 * Send notifications via mail. This class acts as an adapter of the NotificationMailer and the
 * application. It will provide logging and additional verifications on top of the
 * NotificationMailer class, as well as use a simpler interface.
 * This is intended to be used inside the notification consumer (see \OCA\Notifications\App), so
 * there is barely any error handling other than logging.
 */
class NotificationMailerAdapter {
	/** @var NotificationMailer */
	private $sender;

	/** @var IUserManager */
	private $userManager;

	/** @var ILogger */
	private $logger;

	/** @var IURLGenerator */
	private $urlGenerator;

	private $appName = 'notifications';

	public function __construct(NotificationMailer $sender, IUserManager $userManager, ILogger $logger, IURLGenerator $urlGenerator) {
		$this->sender = $sender;
		$this->userManager = $userManager;
		$this->logger = $logger;
		$this->urlGenerator = $urlGenerator;
	}

	public function sendMail(INotification $notification) {
		$nObjectType = $notification->getObjectType();
		$nObjectId = $notification->getObjectId();

		$targetUser = $notification->getUser();

		if (!$this->sender->willSendNotification($notification)) {
			$this->logger->debug(
				"notification $nObjectType#$nObjectId won't be sent to $targetUser via email: personal configuration for $targetUser prevents it",
				['app' => $this->appName]
			);
			return;
		}

		$userObject = $this->userManager->get($targetUser);

		if ($userObject === null) {
			$this->logger->warning(
				"notification $nObjectType#$nObjectId can't be sent to $targetUser via email: the user is missing",
				['app' => $this->appName]
			);
			return;
		}

		$targetEmail = $userObject->getEMailAddress();
		if ($targetEmail === null) {
			$this->logger->warning(
				"notification $nObjectType#$nObjectId can't be sent to $targetUser via email: email for the user isn't set",
				['app' => $this->appName]
			);
			return;
		}

		if ($this->sender->validateEmail($targetEmail)) {
			try {
				$serverUrl = $this->urlGenerator->getAbsoluteURL('/');
				$this->sender->sendNotification($notification, $serverUrl, $targetEmail);
			} catch (\Exception $ex) {
				$this->logger->logException($ex, ['app' => $this->appName]);
			}
		} else {
			$this->logger->warning("notification $nObjectType#$nObjectId can't be sent to $targetUser via email: user's email \"$targetEmail\" isn't valid");
		}
	}
}
