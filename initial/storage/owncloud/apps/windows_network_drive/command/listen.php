<?php
/**
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH
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

namespace OCA\windows_network_drive\Command;

use OC\Core\Command\Base;
use OCA\windows_network_drive\lib\custom_loggers\ConsoleLogger;
use OCA\windows_network_drive\lib\notification_queue\NotificationQueueDBHandler;
use OCA\windows_network_drive\lib\notification_queue\StorageFactory;
use OCA\windows_network_drive\lib\listener\Listener;
use OCA\windows_network_drive\lib\listener\ListenerProcess;
use OCA\windows_network_drive\lib\listener\ListenerCallbacks;
use OCA\windows_network_drive\lib\listener\ListenerException;
use OCA\windows_network_drive\lib\fs_backend\WND2;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class Listen extends Base {
	/** @var NotificationQueueDBHandler */
	private $queueHandler;
	/** @var StorageFactory */
	private $storageFactory;

	/**
	 * @param NotificationQueueDBHandler $queueHandler the queue handler to insert the notifications
	 */
	public function __construct(NotificationQueueDBHandler $queueHandler, StorageFactory $storageFactory) {
		parent::__construct();
		$this->queueHandler = $queueHandler;
		$this->storageFactory = $storageFactory;
	}

	protected function configure() {
		$this
			->setName('wnd:listen')
			->setDescription('Listen to smb changes and store notifications for later processing')
			->addArgument(
				'host',
				InputArgument::REQUIRED,
				'The hostname or IP address of the server to listen to'
			)
			->addArgument(
				'share',
				InputArgument::REQUIRED,
				'The share inside the host to listen to for changes'
			)->addArgument(
				'username',
				InputArgument::REQUIRED,
				'The username that will be used to connect to the share'
			)->addArgument(
				'password',
				InputArgument::OPTIONAL,
				'The user\'s password (will be asked for if it isn\'t provided)'
			)->addOption(
				'path',
				'p',
				InputOption::VALUE_REQUIRED,
				'The path inside the share to watch for changes',
				''
			)->addOption(
				'password-file',
				null,
				InputOption::VALUE_REQUIRED,
				'The file containing the password for the account to be used to listen'
			)->addOption(
				'password-from-service-account',
				null,
				InputOption::VALUE_NONE,
				'Use the password from the matching service account. This works only for collaborative WND mounts'
			)->addOption(
				'password-trim',
				null,
				InputOption::VALUE_NONE,
				'Trim blank characters from the password'
			)->addOption(
				'unbuffering-option',
				null,
				InputOption::VALUE_REQUIRED,
				'Force the usage of that unbuffering option for the underlying smbclient command. Possible options are either "auto", "pty" or "stdbuf"',
				'auto'
			);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$consoleLogger = new ConsoleLogger($output);
		ConsoleLogger::setGlobalConsoleLogger($consoleLogger);

		if (
			$input->getArgument('password') === null &&
			$input->getOption('password-file') === null &&
			!$input->getOption('password-from-service-account')
		) {
			$question = new Question('Please enter the password to access the share: ');
			$question->setHidden(true);
			$password = $this->getHelper('question')->ask($input, $output, $question);
		} else {
			// prioritize password-from-service-account first
			if ($input->getOption('password-from-service-account')) {
				$password = $this->findPassword($input->getArgument('host'), $input->getArgument('share'), $input->getArgument('username'));
			} else {
				// prioritize password file over command-line argument
				$passwordFile = $input->getOption('password-file');
				if ($passwordFile !== null) {
					if ($passwordFile === '-') {
						// read from stdin
						$password = \file_get_contents('php://stdin');
					} else {
						$password = \file_get_contents($passwordFile);
					}
				} else {
					$password = $input->getArgument('password');
				}
			}
		}

		if ($input->getOption('password-trim')) {
			$password = \trim($password);
		}

		$path = \trim(\str_replace('\\', '/', $input->getOption('path')), '/');

		$unbufferingOption = $input->getOption('unbuffering-option');

		$lProcess = new ListenerProcess(
			$input->getArgument('host'),
			$input->getArgument('share'),
			$input->getArgument('username'),
			$password,
			$path,
			$unbufferingOption
		);
		$listener = new Listener($lProcess);
		unset($password);

		// create the listenerCallbacks object to handle the callbacks because the callbacks will be
		// depending on each other.
		$listenerCallbacks = new ListenerCallbacks($this->queueHandler, $consoleLogger);

		// try to set up the signal handling
		$this->conditionalSignalHandlingSetup();
		try {
			$listener->start(
				[$listenerCallbacks, 'notifyCallback'],
				[$listenerCallbacks, 'errorCallback'],
				function () use ($listenerCallbacks) {
					// we'll need to try to process the OS signal in the idle callback if possible.
					$listenerCallbacks->idleCallback();
					if (\function_exists("pcntl_signal")) {
						// process the OS signals here
						\pcntl_signal_dispatch();
					}
				}
			);
		} catch (\Exception $e) {
			$consoleLogger->error($e->getMessage());
			$errorCode = $e->getCode();
			if ($errorCode === 0) {
				return 1;
			} else {
				return $errorCode;
			}
		}
	}

	/**
	 * If possible, setup signal handler to stop the process in a a clean way. The code is expected
	 * to be executed inside the idleCallback context of the listener, so throwing a ListenerException
	 * with the LISTENER_EXIT_STOP_PROCESS action should exit the listener loop and stop the
	 * underlying smbclient process.
	 * NOTE: in case there were other handlers attached to those signals, those handlers will be
	 * overwritten and won't be restored.
	 * @return bool true if the "pcntl_signal" function is available and the handlers are attached,
	 * false otherwise.
	 */
	private function conditionalSignalHandlingSetup() {
		if (\function_exists("pcntl_signal")) {
			$handler = function ($signo) {
				$signalMap = [
					SIGTERM => 'SIGTERM',
					SIGHUP => 'SIGHUP',
					SIGINT => 'SIGINT',
				];
				$exception = new ListenerException("{$signalMap[$signo]} received", $signo);
				$exception->setAction(ListenerException::LISTENER_EXIT_STOP_PROCESS);
				throw $exception;
			};
			\pcntl_signal(SIGTERM, $handler);
			\pcntl_signal(SIGHUP, $handler);
			\pcntl_signal(SIGINT, $handler);
			return true;
		}
		return false;
	}

	private function findPassword(string $host, string $share, string $username) {
		$classes = [WND2::class];
		$opts = [
			'host' => $host,
			'share' => $share,
			'service-account' => $username,
		];

		$password = null;
		$sConfigs = $this->storageFactory->findMatchingStorageConfig($classes, $opts);
		foreach ($sConfigs as $sConfig) {
			$password = $sConfig->getBackendOption('service-account-password');
			if ($password !== '') {
				return $password;  // return immediately the first found password
			}
		}
		return $password;  // return either null, or the last password fetched (might be empty)
	}
}
