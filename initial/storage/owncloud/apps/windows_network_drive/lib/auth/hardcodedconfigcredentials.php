<?php
/**
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
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

namespace OCA\windows_network_drive\Lib\Auth;

use OCP\IConfig;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Files\External\DefinitionParameter;
use OCP\Files\External\Auth\AuthMechanism;
use OCP\Files\External\IStorageConfig;
use OCP\Files\External\InsufficientDataForMeaningfulAnswerException;
use OCP\Files\StorageNotAvailableException;
use OCA\windows_network_drive\lib\Utils;

/**
 * Username from the user id in the session, password harcoded in the config
 */
class HardcodedConfigCredentials extends AuthMechanism {
	/** @var IConfig */
	protected $config;
	/** @var IUserSession */
	protected $userSession;

	public function __construct(IL10N $l, IConfig $config, IUserSession $userSession) {
		$this->config = $config;
		$this->userSession = $userSession;

		$this->setIdentifier('password::hardcodedconfigcredentials')
			->setScheme(self::SCHEME_PASSWORD)
			->setText($l->t('Credentials hardcoded in config file'))
			->addParameters([
				new DefinitionParameter('key', $l->t('Config key'))
			]);
	}

	public function manipulateStorageConfig(IStorageConfig &$storage, IUser $user = null) {
		if ($user === null) {
			$user = $this->userSession->getUser();
		}

		if (!isset($user)) {
			throw new InsufficientDataForMeaningfulAnswerException('No login credentials saved');
		}

		$stringKey = $storage->getBackendOption('key');
		$keyParts = \explode('#', $stringKey);
		try {
			$value = Utils::getValueFromNestedArrayFromConfig($this->config, $keyParts);
		} catch (\InvalidArgumentException $e) {
			throw new InsufficientDataForMeaningfulAnswerException(
				'Wrong key provided',
				InsufficientDataForMeaningfulAnswerException::STATUS_ERROR,
				$e
			);
		}

		if (\is_string($value)) {
			$storage->setBackendOption('user', $user->getUID());
			$storage->setBackendOption('password', $value);
		} else {
			throw new InsufficientDataForMeaningfulAnswerException(
				'value not found in that key',
				InsufficientDataForMeaningfulAnswerException::STATUS_ERROR
			);
		}
	}
}
