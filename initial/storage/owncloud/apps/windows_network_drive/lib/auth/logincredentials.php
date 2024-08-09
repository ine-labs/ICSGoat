<?php
/**
 * @author Robin McCorkell <rmccorkell@owncloud.com>
 * @author Ilja Neumann <ineumann@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

use \OCP\IL10N;
use \OCP\IUser;
use \OCP\Files\External\Auth\AuthMechanism;
use \OCP\Files\External\IStorageConfig;
use \OCP\Security\ICredentialsManager;
use \OCP\Files\External\InsufficientDataForMeaningfulAnswerException;

/**
 * Username and password from login credentials, saved in DB
 */
class LoginCredentials extends AuthMechanism {
	public const CREDENTIALS_IDENTIFIER = 'password::logincredentials/credentials';

	/** @var ICredentialsManager */
	protected $credentialsManager;

	public function __construct(IL10N $l, ICredentialsManager $credentialsManager) {
		$this->credentialsManager = $credentialsManager;

		$this
			->setIdentifier('password::logincredentials')
			->setScheme(self::SCHEME_PASSWORD)
			->setText($l->t('Log-in credentials, save in database'))
			->addParameters([
			])
		;
	}

	public function manipulateStorageConfig(IStorageConfig &$storage, IUser $user = null) {
		if (!isset($user)) {
			throw new InsufficientDataForMeaningfulAnswerException('No login credentials saved');
		}
		$uid = $user->getUID();
		// credentials are stored via post_login hook in Hooks::loginCredentialsHooks
		// which is automatically setup when the WND backends are loaded
		$credentials = $this->credentialsManager->retrieve($uid, self::CREDENTIALS_IDENTIFIER);

		if (!isset($credentials)) {
			throw new InsufficientDataForMeaningfulAnswerException('No login credentials saved');
		}

		$storage->setBackendOption('user', $credentials['user']);
		$storage->setBackendOption('password', $credentials['password']);
	}

	/**
	 * Fetch decrypted storage-credentials.
	 * Returns array in form of ['user' => 'uid', 'password' => 'secretWndPassword'
	 * @param IUser $user
	 * @return array|null
	 */
	public function getCredentials(IUser $user) {
		return $this->credentialsManager->retrieve($user->getUID(), self::CREDENTIALS_IDENTIFIER);
	}

	/**
	 * Sets the password of the storage credentials to '' (empty)
	 *
	 * @param string $uid
	 */
	public function resetPassword($uid) {
		$creds = $this->credentialsManager->retrieve($uid, self::CREDENTIALS_IDENTIFIER);
		$creds['password'] = '';
		$this->credentialsManager->store($uid, self::CREDENTIALS_IDENTIFIER, $creds);
	}
}
