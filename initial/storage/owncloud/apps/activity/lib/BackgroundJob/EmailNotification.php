<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCA\Activity\BackgroundJob;

use OC\BackgroundJob\TimedJob;
use OCA\Activity\AppInfo\Application;
use OCA\Activity\MailQueueHandler;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;

/**
 * Class EmailNotification
 *
 * @package OCA\Activity\BackgroundJob
 */
class EmailNotification extends TimedJob {
	public const CLI_EMAIL_BATCH_SIZE = 500;
	public const WEB_EMAIL_BATCH_SIZE = 25;

	/** @var MailQueueHandler */
	protected $mqHandler;

	/** @var IUserManager */
	protected $userManager;

	/** @var IConfig */
	protected $config;

	/** @var ILogger */
	protected $logger;

	/** @var bool */
	protected $isCLI;

	/**
	 * @param MailQueueHandler $mailQueueHandler
	 * @param IConfig $config
	 * @param ILogger $logger
	 * @param bool|null $isCLI
	 */
	public function __construct(
		MailQueueHandler $mailQueueHandler = null,
		IUserManager $userManager,
		IConfig $config = null,
		ILogger $logger = null,
		$isCLI = null
	) {
		// Run all 15 Minutes
		$this->setInterval(15 * 60);

		$this->mqHandler = $mailQueueHandler;
		$this->userManager = $userManager;
		$this->config = $config;
		$this->logger = $logger;
		$this->isCLI = $isCLI;
	}

	protected function run($argument) {
		// We don't use time() but "time() - 1" here, so we don't run into
		// runtime issues later and delete emails, which were created in the
		// same second, but were not collected for the emails.
		$sendTime = \time() - 1;

		if ($this->isCLI) {
			do {
				// If we are in CLI mode, we keep sending emails
				// until we are done.
				$emails_sent = $this->runStep(self::CLI_EMAIL_BATCH_SIZE, $sendTime);
			} while ($emails_sent === self::CLI_EMAIL_BATCH_SIZE);
		} else {
			// Only send 25 Emails in one go for web cron
			$this->runStep(self::WEB_EMAIL_BATCH_SIZE, $sendTime);
		}
	}

	/**
	 * Send an email to {$limit} users
	 *
	 * @param int $limit Number of users we want to send an email to
	 * @param int $sendTime The latest send time
	 * @return int Number of users we sent an email to
	 * @throws \Exception
	 */
	protected function runStep($limit, $sendTime) {
		// Get all users which should receive an email
		$affectedUsers = $this->mqHandler->getAffectedUsers($limit, $sendTime);
		if (empty($affectedUsers)) {
			// No users found to notify, mission abort
			return 0;
		}
		$affectedUIDs = \array_map(function ($u) {
			return $u['uid'];
		}, $affectedUsers);

		$userLanguages = $this->config->getUserValueForUsers('core', 'lang', $affectedUIDs);
		$userTimezones = $this->config->getUserValueForUsers('core', 'timezone', $affectedUIDs);

		// Send Email
		$default_lang = $this->config->getSystemValue('default_language', 'en');
		$defaultTimeZone = \date_default_timezone_get();

		$sentMailForUsers = [];

		foreach ($affectedUsers as $user) {
			$uid = $user['uid'];
			$userObject = $this->userManager->get($uid);
			if (empty($user['email'])
				|| !$userObject instanceof IUser
				|| $userObject->isEnabled() === false
			) {
				// The user did not setup an email address
				// So we will not send an email but still discard the queue entries
				$this->logger->debug("Couldn't send notification email to user '$uid' (email address isn't set for that user)", ['app' => 'activity']);
				$sentMailForUsers[] = $uid;
				continue;
			}

			$language = (!empty($userLanguages[$uid])) ? $userLanguages[$uid] : $default_lang;
			$timezone = (!empty($userTimezones[$uid])) ? $userTimezones[$uid] : $defaultTimeZone;

			try {
				$this->mqHandler->sendEmailToUser($uid, $user['email'], $language, $timezone, $sendTime);
				$sentMailForUsers[] = $uid;
			} catch (\Exception $e) {
				$this->logger->warning(
					"Couldn't send notification email to user {user} ({reason}})",
					[
						'app' => 'activity',
						'user' => $uid,
						'reason' => $e->getMessage()
					]
				);
			}
		}

		// Delete all entries we dealt with
		$this->mqHandler->deleteSentItems($sentMailForUsers, $sendTime);

		return \sizeof($affectedUsers);
	}
}
