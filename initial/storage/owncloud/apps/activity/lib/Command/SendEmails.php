<?php
/**
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
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

namespace OCA\Activity\Command;

use OCA\Activity\MailQueueHandler;
use OCP\IConfig;
use OCP\ILogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendEmails extends Command {
	public const BATCH_SIZE = 250;

	/** @var MailQueueHandler */
	private $mqHandler;

	/** @var IConfig */
	private $config;

	/** @var ILogger */
	private $logger;

	/**
	 * SendEmails constructor.
	 *
	 * @param MailQueueHandler $mailQueueHandler
	 * @param IConfig $config
	 * @param ILogger $logger
	 */
	public function __construct(
		MailQueueHandler $mailQueueHandler,
		IConfig $config,
		ILogger $logger
	) {
		parent::__construct();
		$this->mqHandler = $mailQueueHandler;
		$this->config = $config;
		$this->logger = $logger;
	}

	protected function configure() {
		$this->setName('activity:send-emails')
			->setDescription('Send all pending activity emails now');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int|void
	 */
	public function execute(InputInterface $input, OutputInterface $output) {
		$verbocity = $output->getVerbosity();
		if ($verbocity >= OutputInterface::VERBOSITY_VERBOSE) {
			$progress = new ProgressBar($output);
			$progress->start();
		}
		do {
			$users = $this->mqHandler->getAllUsers(self::BATCH_SIZE);
			$batchCount = \count($users);
			if ($batchCount === 0) {
				// queue is empty
				break;
			}

			$this->sendBatch($users, $output);
			if ($verbocity >= OutputInterface::VERBOSITY_VERBOSE) {
				$progress->advance($batchCount);
			}
		} while ($batchCount > 0);

		if ($verbocity >= OutputInterface::VERBOSITY_VERBOSE) {
			$progress->finish();
		}
	}

	/**
	 * @param array $users - should have at least uid and maxMailId for each entry
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function sendBatch($users, $output) {
		$affectedUIDs = \array_map(function ($u) {
			return $u['uid'];
		}, $users);
		$userLanguages = $this->config->getUserValueForUsers('core', 'lang', $affectedUIDs);
		$userTimezones = $this->config->getUserValueForUsers('core', 'timezone', $affectedUIDs);
		$defaultLang = $this->config->getSystemValue('default_language', 'en');
		$defaultTimeZone = \date_default_timezone_get();

		$sentMailForUsers = [];
		foreach ($users as $user) {
			$uid = $user['uid'];
			if (empty($user['email'])) {
				// The user did not setup an email address
				// So we will not send an email but still discard the queue entries
				$this->logger->debug("Couldn't send notification email to user '$uid' (email address isn't set for that user)", ['app' => 'activity']);
				$sentMailForUsers[] = [
					'uid' => $uid,
					'maxMailId' => $user['max_mail_id']
				];
				continue;
			}

			try {
				$language = (!empty($userLanguages[$uid])) ? $userLanguages[$uid] : $defaultLang;
				$timezone = (!empty($userTimezones[$uid])) ? $userTimezones[$uid] : $defaultTimeZone;
				$this->mqHandler->sendAllEmailsToUser($uid, $user['email'], $language, $timezone, $user['max_mail_id']);
				$sentMailForUsers[] = [
					'uid' => $uid,
					'maxMailId' => $user['max_mail_id']
				];
			} catch (\Exception $e) {
				$this->logger->error(
					'Got an error while flushing emails for user "{user}"',
					['app' => 'activity', 'user' => $uid]
				);
				$this->logger->logException($e);
				if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
					$output->writeln("\nNotification to user '{$uid}' has been not sent: " . $e->getMessage());
				}
			}
		}

		// Delete all entries we dealt with
		$this->mqHandler->deleteAllSentItems($sentMailForUsers);

		return \count($sentMailForUsers);
	}
}
