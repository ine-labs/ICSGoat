<?php
/**
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 *
 * @copyright Copyright (c) 2021, ownCloud GmbH
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
use OCA\windows_network_drive\lib\Utils;
use OCA\windows_network_drive\lib\fs_backend\WND2;
use OCA\windows_network_drive\lib\custom_loggers\ConsoleLogger;
use OCP\Files\External\Service\IGlobalStoragesService;
use OCP\Files\External\NotFoundException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;

class SetServiceAccount extends Base {
	public const ERROR_INVALID_MOUNT_ID = 1;
	public const ERROR_STORAGE_CONFIG_NOT_FOUND = 2;
	public const ERROR_NOT_WND2_MOUNT = 3;
	public const ERROR_PASSWORD_MISMATCH = 255;

	/** @var IGlobalStoragesService */
	private $globalService;
	/** @var QuestionHelper */
	private $questionHelper;

	public function __construct(
		IGlobalStoragesService $globalService,
		QuestionHelper $questionHelper
	) {
		parent::__construct();
		$this->globalService = $globalService;
		$this->questionHelper = $questionHelper;
	}

	protected function configure() {
		$this
			->setName('wnd:set-service-account')
			->setDescription('Sets the service account for the target mount point. You\'ll be asked for the password of the service account.')
			->addArgument(
				'mount-id',
				InputArgument::REQUIRED,
				'Id of the mount point. Use "occ files_external:list --short" to find it'
			);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		ConsoleLogger::setGlobalConsoleLogger(new ConsoleLogger($output));
		$globalLogger = ConsoleLogger::getGlobalConsoleLogger();

		$mountId = \intval($input->getArgument('mount-id'));
		if ($mountId < 1) {
			$globalLogger->error('Invalid mount id');
			return self::ERROR_INVALID_MOUNT_ID;
		}

		try {
			$storageConfig = $this->globalService->getStorage($mountId);
		} catch (NotFoundException $e) {
			$globalLogger->error($e->getMessage());
			return self::ERROR_STORAGE_CONFIG_NOT_FOUND;
		}

		if (!$storageConfig->getBackend() instanceof WND2) {
			$globalLogger->error('Only "Windows Network Drive (collaborative)" mounts are allowed');
			return self::ERROR_NOT_WND2_MOUNT;
		}

		$question = new Question('Enter the password for the service account. ');
		$question->setHidden(true);
		$question->setHiddenFallback(false);
		$question2 = new Question('Repeat the password. ');
		$question2->setHidden(true);
		$question2->setHiddenFallback(false);

		$servicePassword = $this->questionHelper->ask($input, $output, $question);
		$servicePassword2 = $this->questionHelper->ask($input, $output, $question2);
		if ($servicePassword !== $servicePassword2) {
			$globalLogger->error('Password mismatch!');
			return self::ERROR_PASSWORD_MISMATCH;
		}

		$encryptedPassword = Utils::encryptPassword($servicePassword);

		$storageConfig->setBackendOption('service-account-password', $encryptedPassword);
		$this->globalService->updateStorage($storageConfig);
	}
}
