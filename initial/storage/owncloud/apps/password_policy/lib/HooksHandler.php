<?php
/**
 *
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

namespace OCA\PasswordPolicy;

use OCA\PasswordPolicy\Controller\SettingsController;
use OCA\PasswordPolicy\Db\OldPassword;
use OCA\PasswordPolicy\Db\OldPasswordMapper;
use OCA\PasswordPolicy\Rules\PasswordExpired;
use OCA\PasswordPolicy\Rules\PolicyException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ISession;
use OCP\IUser;
use OCP\Security\IHasher;
use OCP\Notification\IManager;
use OCP\Share;
use Symfony\Component\EventDispatcher\GenericEvent;

class HooksHandler {

	/** @var IConfig */
	private $config;

	/** @var Engine */
	private $engine;

	/** @var IHasher */
	private $hasher;

	/** @var ITimeFactory */
	private $timeFactory;

	/** @var IL10N */
	private $l10n;

	/** @var PasswordExpired */
	private $passwordExpiredRule;

	/** @var OldPasswordMapper */
	private $oldPasswordMapper;

	/** @var ISession */
	private $session;

	/** @var IManager */
	private $notificationManager;

	/** @var UserNotificationConfigHandler */
	private $userNotificationConfigHandler;

	public function __construct(
		IConfig $config = null,
		Engine $engine = null,
		IHasher $hasher = null,
		ITimeFactory $timeFactory = null,
		IL10N $l10n = null,
		PasswordExpired $passwordExpiredRule = null,
		OldPasswordMapper $oldPasswordMapper = null,
		ISession $session = null,
		IManager $notificationManager = null,
		UserNotificationConfigHandler $userNotificationConfigHandler = null
	) {
		$this->config = $config;
		$this->engine = $engine;
		$this->hasher = $hasher;
		$this->timeFactory = $timeFactory;
		$this->l10n = $l10n;
		$this->passwordExpiredRule = $passwordExpiredRule;
		$this->oldPasswordMapper = $oldPasswordMapper;
		$this->session = $session;
		$this->notificationManager = $notificationManager;
		$this->userNotificationConfigHandler = $userNotificationConfigHandler;
	}

	private function fixDI() {
		if ($this->engine === null) {
			$this->config = \OC::$server->getConfig();
			$this->hasher = \OC::$server->getHasher();
			$this->timeFactory = \OC::$server->getTimeFactory();
			$this->l10n = \OC::$server->getL10NFactory()->get('password_policy');
			$db = \OC::$server->getDatabaseConnection();
			$this->oldPasswordMapper = new OldPasswordMapper($db);
			$this->passwordExpiredRule = new PasswordExpired(
				$this->l10n,
				\OC::$server->getLogger(),
				$this->oldPasswordMapper,
				$this->hasher,
				$this->timeFactory
			);
			$this->engine = new Engine(
				$this->loadConfiguration(),
				$this->l10n,
				\OC::$server->getSecureRandom(),
				$db,
				$this->hasher
			);
			$this->session = \OC::$server->getSession();
			$this->notificationManager = \OC::$server->getNotificationManager();
			$this->userNotificationConfigHandler = new UserNotificationConfigHandler($this->config);
		}
	}

	/**
	 * @return array
	 */
	private function loadConfiguration() {
		$appValues = SettingsController::DEFAULTS;

		$configValues = [];
		foreach ($appValues as $key => $default) {
			$configValues[$key] = $this->config->getAppValue('password_policy', $key, $default);
		}
		return $configValues;
	}

	public function generatePassword(GenericEvent $event) {
		$this->fixDI();
		$event['password'] = $this->engine->generatePassword();
		/* ToDo: issue 278 - deprecated since Symfony 4.3, use "Symfony\Contracts\EventDispatcher\Event" instead */
		/* @phan-suppress-next-line PhanDeprecatedFunction */
		$event->stopPropagation();
	}

	/**
	 * @param GenericEvent $event
	 * @throws PolicyException
	 */
	public function verifyUserPassword(GenericEvent $event) {
		$this->verifyPassword($event, 'user');
	}

	/**
	 * @param GenericEvent $event
	 * @throws PolicyException
	 */
	public function verifyPublicPassword(GenericEvent $event) {
		$this->verifyPassword($event, 'public');
	}

	/**
	 * @param GenericEvent $event
	 * @throws PolicyException
	 */
	private function verifyPassword(GenericEvent $event, $type = 'user') {
		$this->fixDI();
		$password = $event->getArguments()['password'];
		if ($event->hasArgument('uid')) {
			$uid = $event->getArguments()['uid'];
		} else {
			$uid = null;
		}
		$this->engine->verifyPassword($password, $uid, $type);
	}

	public function updateLinkExpiry($params) {
		$this->fixDI();
		$configValues = $this->engine->getConfigValues();
		if (!isset($params['shareType'])) {
			// shareType parameter added after OC 10.4.0
			$params['shareType'] = Share::SHARE_TYPE_LINK;
		}

		$days = null;

		if ($params['passwordSet'] === true) {
			if ($configValues['spv_expiration_password_checked'] === 'on') {
				$days = $configValues['spv_expiration_password_value'];
			}
		} else {
			if ($configValues['spv_expiration_nopassword_checked'] === 'on') {
				$days = $configValues['spv_expiration_nopassword_value'];
			}
		}

		if ($days !== null) {
			$date = new \DateTime();
			$date->setTime(0, 0, 0);
			$date->add(new \DateInterval('P'.$days.'D'));

			if ($params['expirationDate'] === null && $params['shareType'] === Share::SHARE_TYPE_LINK) {
				$params['accepted'] = false;
				$params['message'] = (string) $this->l10n->t('An expiration date is required.');
			}

			// $date is the max expiration date
			if ($date < $params['expirationDate'] && $params['shareType'] === Share::SHARE_TYPE_LINK) {
				$params['accepted'] = false;
				$params['message'] = (string) $this->l10n->n('The expiration date cannot exceed %n day.', 'The expiration date cannot exceed %n days.', $days);
			}
		}
	}

	public function saveOldPassword(GenericEvent $event) {
		$this->fixDI();

		$user = $this->getUser($event);
		$password = $event->getArgument('password');

		$userId = $user->getUID();

		$oldPassword = new OldPassword();
		$oldPassword->setUid($userId);
		$oldPassword->setPassword($this->hasher->hash($password));
		$oldPassword->setChangeTime($this->timeFactory->getTime());
		$this->oldPasswordMapper->insert($oldPassword);

		// get previous marks
		$aboutToExpireMark = $this->userNotificationConfigHandler->getMarkAboutToExpireNotificationSentFor($userId);
		$expiredMark = $this->userNotificationConfigHandler->getMarkExpiredNotificationSentFor($userId);

		$this->userNotificationConfigHandler->resetExpirationMarks($userId);

		if ($aboutToExpireMark !== null) {
			$notification = $this->notificationManager->createNotification();
			$notification->setApp('password_policy')
				->setUser($userId)
				->setObject('about_to_expire', $aboutToExpireMark);
			$this->notificationManager->markProcessed($notification);
		}
		if ($expiredMark !== null) {
			$notification = $this->notificationManager->createNotification();
			$notification->setApp('password_policy')
				->setUser($userId)
				->setObject('expired', $expiredMark);
			$this->notificationManager->markProcessed($notification);
		}
	}

	public function savePasswordForCreatedUser(GenericEvent $event) {
		$this->fixDI();

		$userid = $event->getArgument('uid');
		$password = $event->getArgument('password');

		$oldPassword = new OldPassword();
		$oldPassword->setUid($userid);
		$oldPassword->setPassword($this->hasher->hash($password));
		$oldPassword->setChangeTime($this->timeFactory->getTime());
		$this->oldPasswordMapper->insert($oldPassword);
		$this->userNotificationConfigHandler->resetExpirationMarks($userid);
	}

	/**
	 * Flags the session to require a password change
	 */
	protected function forcePasswordChange($firstLogin = null, IUser $user = null) {
		if (($firstLogin === true) && ($user !== null)) {
			$this->config->setUserValue($user->getUID(), 'password_policy', 'firstLoginPasswordChange', '1');
		}
		$this->session->set('password_policy.forcePasswordChange', true);
	}

	/**
	 * check we really get an object, die hard in case we don't
	 *
	 * @param GenericEvent $event
	 * @return IUser
	 */
	private function getUser(GenericEvent $event) {
		$user = $event->getArgument('user');
		if (!$user instanceof IUser) {
			throw new \UnexpectedValueException("'$user' is not an instance of IUser.");
		}
		return $user;
	}

	public function checkPasswordExpired(GenericEvent $event) {
		$this->fixDI();

		$user = $this->getUser($event);
		$password = $event->getArgument('password');

		// If option is enabled in security settings check password age
		if ($this->engine->yes('spv_user_password_expiration_checked')) {
			$days = $this->engine->getConfigValue('spv_user_password_expiration_value');
			try {
				// check if password expired
				$this->passwordExpiredRule->verify($password, $days, $user->getUID());
			} catch (PolicyException $e) {
				$this->forcePasswordChange();
			}
		}
	}

	public function checkAdminRequestedPasswordChange(GenericEvent $event) {
		$this->fixDI();

		$user = $this->getUser($event);

		// Always check the user config
		$expires = $this->config->getUserValue(
			$user->getUID(),
			'password_policy',
			'forcePasswordChange'
		);
		if ($expires !== '') {
			$expireTime = (new \DateTime($expires))->getTimestamp();
			$now = $this->timeFactory->getTime();
			if ($expireTime < $now) {
				$this->forcePasswordChange();
			}
		}
		/* if this flag still exists, force to password change */
		if ($this->config->getUserValue($user->getUID(), 'password_policy', 'firstLoginPasswordChange')) {
			$this->forcePasswordChange();
		}
	}

	public function checkForcePasswordChangeOnFirstLogin(GenericEvent $event) {
		$this->fixDI();
		$user = $event->getSubject();
		if (!$user instanceof IUser) {
			throw new \UnexpectedValueException("'$user' is not an instance of IUser.");
		}
		// If option is enabled in security settings check first login
		if ($this->engine->yes('spv_user_password_force_change_on_first_login_checked')) {
			$last2PasswordOfUser = $this->oldPasswordMapper->getOldPasswords($user->getUID(), 2, true);

			/**
			 * Trigger password change on first login only if there is one entry
			 * for the user in the table. There can be cases where other apps
			 * allow user to set the password, say by email. Then the table would
			 * have more than one entry for the same user. On such circumstances
			 * we can ignore the password change for the user.
			 */
			if (\count($last2PasswordOfUser) <= 1) {
				if ($user->getBackendClassName() === 'Database') {
					// try to find user in account table, needs find to search additional search terms,
					$this->forcePasswordChange(true, $user);
				}
			}
		}
	}

	/**
	 * This method removes the entries which remain in oc_user_password_history
	 * table and oc_notifications table after the deletion of the user
	 *
	 * @param GenericEvent $event
	 */
	public function removeUserEntriesFromTable(GenericEvent $event) {
		$this->fixDI();
		$uid = $event->getArgument('uid');

		$this->oldPasswordMapper->cleanUserHistory($uid);
	}
}
