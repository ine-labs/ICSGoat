<?php
/**
 *
 * @author Juan Pablo Villafáñez <jvillafanez@solidgear.es>
 * @copyright Copyright (c) 2018, ownCloud GmbH
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

namespace OCA\PasswordPolicy\Jobs;

use OC\BackgroundJob\TimedJob;
use OCP\Notification\IManager;
use OCP\IURLGenerator;
use OCP\ILogger;
use OCP\IUserManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCA\PasswordPolicy\Db\OldPasswordMapper;
use OCA\PasswordPolicy\Db\OldPassword;
use OCA\PasswordPolicy\UserNotificationConfigHandler;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;

class PasswordExpirationNotifierJob extends TimedJob {
	/** @var OldPasswordMapper */
	private $mapper;

	/** @var IManager $manager */
	private $manager;

	/** @var UserNotificationConfigHandler */
	private $unConfigHandler;

	/** @var IUserManager */
	private $userManager;

	/** @var ITimeFactory */
	private $timeFactory;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var ILogger */
	private $logger;

	/** @var EventDispatcher */
	private $eventDispatcher;

	public function __construct(
		OldPasswordMapper $mapper,
		IManager $manager,
		UserNotificationConfigHandler $unConfigHandler,
		IUserManager $userManager,
		ITimeFactory $timeFactory,
		IURLGenerator $urlGenerator,
		ILogger $logger,
		EventDispatcher $eventDispatcher
	) {
		$this->mapper = $mapper;
		$this->manager = $manager;
		$this->unConfigHandler = $unConfigHandler;
		$this->userManager = $userManager;
		$this->timeFactory = $timeFactory;
		$this->urlGenerator = $urlGenerator;
		$this->logger = $logger;
		$this->eventDispatcher = $eventDispatcher;

		$this->setInterval(12 * 60 * 60);
	}

	protected function run($arguments) {
		$expirationTime = $this->unConfigHandler->getExpirationTime();
		if ($expirationTime === null) {
			return;  // expiration not configured
		}

		$expirationTimeNotification = $this->unConfigHandler->getExpirationTimeForNormalNotification();
		if ($expirationTimeNotification === null) {
			$expirationTimeNotification = 0;
		}

		// ensure ranges are correct
		if ($expirationTime <= $expirationTimeNotification) {
			$message = "wrong expiration range: normal ($expirationTimeNotification) < expired ($expirationTime)";
			$this->logger->warning($message, ['app' => 'password_policy']);
			return;
		}

		$notifyAfter = $expirationTime - $expirationTimeNotification;

		$currentTime = $this->timeFactory->getTime();

		$maxTimestamp = $currentTime - $notifyAfter;
		// passwords changed before $maxTimestamp are expired or about to be expired
		// according to the expiration time range

		$oldPasswordsAboutToExpire = $this->mapper->getPasswordsAboutToExpire($maxTimestamp);
		foreach ($oldPasswordsAboutToExpire as $passInfo) {
			$elapsedTime = $currentTime - $passInfo->getChangeTime();
			$adminPart = '';
			if ($passInfo->getPassword() === OldPassword::EXPIRED) {
				$adminPart = '; initiated by admin';
			}

			if ($elapsedTime >= $expirationTime) {
				$this->logger->debug("password timestamp for {$passInfo->getUid()}: {$passInfo->getChangeTime()}; elapsed time: {$elapsedTime} -> EXPIRED${adminPart}", ['app' => 'password_policy']);
				$this->sendPassExpiredNotification($passInfo, $expirationTime);
			} elseif ($elapsedTime >= $notifyAfter) {
				$this->logger->debug("password timestamp for {$passInfo->getUid()}: {$passInfo->getChangeTime()}; elapsed time: {$elapsedTime} -> NOTIFY${adminPart}", ['app' => 'password_policy']);
				$this->sendAboutToExpireNotification($passInfo, $expirationTime);
			}
		}
	}

	/**
	 * Send an "about to expire" notification using the password information
	 * in $passInfo. The password should expire after $expirationTime (90 days
	 * by default). This information will also be used in the notification
	 * @param OldPassword $passInfo the password information used to send the
	 * notification
	 * @param int $expirationTime the time to expire the password in seconds
	 * (for example, 90 days - in seconds)
	 */
	private function sendAboutToExpireNotification(OldPassword $passInfo, $expirationTime) {
		if (!$this->userManager->userExists($passInfo->getUid()) ||
				$this->unConfigHandler->isSentAboutToExpireNotification($passInfo)) {
			return;  // notification already sent
		}

		$notificationTimestamp = $this->timeFactory->getTime();

		// we'll use the id of the passInfo as object id and marker
		$notification = $this->manager->createNotification();
		$notification->setApp('password_policy')
			->setUser($passInfo->getUid())
			->setDateTime(new \DateTime("@{$notificationTimestamp}"))
			->setObject('about_to_expire', (string)$passInfo->getId())
			->setSubject('about_to_expire', [$passInfo->getChangeTime(), $expirationTime])
			->setMessage('about_to_expire', [$passInfo->getChangeTime(), $expirationTime])
			->setLink($this->getNotificationLink());

		$linkAction = $notification->createAction();
		$linkAction->setLabel('Change password')
			->setLink($this->getNotificationLink(), 'GET');
		$notification->addAction($linkAction);

		$this->manager->notify($notification);

		$this->unConfigHandler->markAboutToExpireNotificationSentFor($passInfo);

		$aboutToExpireEvent = new GenericEvent(
			null,
			[
				'expireStatus' => 'about_to_expire',
				'user' => $this->userManager->get($passInfo->getUid()),
				'passwordExpireInSeconds' => $expirationTime
			]
		);
		$this->eventDispatcher->dispatch($aboutToExpireEvent, 'user.passwordAboutToExpire');
	}

	/**
	 * Send an "expired" notification using the password information
	 * in $passInfo. The password should expire after $expirationTime (90 days
	 * by default). This information will also be used in the notification
	 * @param OldPassword $passInfo the password information used to send the
	 * notification
	 * @param int $expirationTime the time to expire the password in seconds
	 * (for example, 90 days - in seconds)
	 */
	private function sendPassExpiredNotification(OldPassword $passInfo, $expirationTime) {
		if (!$this->userManager->userExists($passInfo->getUid()) ||
				$this->unConfigHandler->isSentExpiredNotification($passInfo)) {
			return;  // notification already sent
		}

		$notificationTimestamp = $this->timeFactory->getTime();

		// we'll use the id of the passInfo as object id and marker
		$notification = $this->manager->createNotification();
		$notification->setApp('password_policy')
			->setUser($passInfo->getUid())
			->setDateTime(new \DateTime("@{$notificationTimestamp}"))
			->setObject('expired', (string)$passInfo->getId())
			->setSubject('expired', [$passInfo->getChangeTime(), $expirationTime])
			->setMessage('expired', [$passInfo->getChangeTime(), $expirationTime])
			->setLink($this->getNotificationLink());

		$linkAction = $notification->createAction();
		$linkAction->setLabel('Change password')
			->setLink($this->getNotificationLink(), 'GET');
		$notification->addAction($linkAction);

		$this->manager->notify($notification);

		$this->unConfigHandler->markExpiredNotificationSentFor($passInfo);

		$expiredEvent = new GenericEvent(
			null,
			[
				'expireStatus' => 'expired',
				'user' => $this->userManager->get($passInfo->getUid()),
				'passwordExpireInSeconds' => $expirationTime
			]
		);
		$this->eventDispatcher->dispatch($expiredEvent, 'user.passwordExpired');
	}

	private function getNotificationLink() {
		return $this->urlGenerator->linkToRoute(
			'settings.SettingsPage.getPersonal',
			['sectionid' => 'general']
		);
	}
}
