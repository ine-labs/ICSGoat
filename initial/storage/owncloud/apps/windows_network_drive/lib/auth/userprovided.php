<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
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

use OCP\Files\External\Auth\IUserProvided;
use OCP\Files\External\DefinitionParameter;
use OCP\IL10N;
use OCP\IUser;
use OCP\Files\External\Auth\AuthMechanism;
use OCP\Files\External\IStorageConfig;
use OCP\Security\ICredentialsManager;
use OCP\Files\External\InsufficientDataForMeaningfulAnswerException;
use OCP\Files\External\IStoragesBackendService;

/**
 * User provided Username and Password
 */
class UserProvided extends AuthMechanism implements IUserProvided {
	public const CREDENTIALS_IDENTIFIER_PREFIX = 'password::userprovided/';

	/** @var ICredentialsManager */
	protected $credentialsManager;

	public function __construct(IL10N $l, ICredentialsManager $credentialsManager) {
		$this->credentialsManager = $credentialsManager;

		$this
			->setIdentifier('password::userprovided')
			->setVisibility(IStoragesBackendService::VISIBILITY_ADMIN)
			->setScheme(self::SCHEME_PASSWORD)
			->setText($l->t('User entered, store in database'))
			->addParameters([
				(new DefinitionParameter('user', $l->t('Username')))
					->setFlag(DefinitionParameter::FLAG_USER_PROVIDED),
				(new DefinitionParameter('password', $l->t('Password')))
					->setType(DefinitionParameter::VALUE_PASSWORD)
					->setFlag(DefinitionParameter::FLAG_USER_PROVIDED),
			]);
	}

	private function getCredentialsIdentifier($externalConfigMountId) {
		return self::CREDENTIALS_IDENTIFIER_PREFIX . $externalConfigMountId;
	}

	public function resetPassword(IUser $user, $id = '') {
		$credentials = $this->getAuth($user->getUID(), $id);
		$this->saveBackendOptions($user, $id, ['user' => $credentials['user'], 'password' => '']);
	}

	public function getAuth($uid, $id) {
		$auth = $this->credentialsManager->retrieve($uid, $this->getCredentialsIdentifier($id));
		if (!\is_array($auth)) {
			return [
				'user' => '',
				'password' => ''
			];
		} else {
			return $auth;
		}
	}

	public function saveBackendOptions(IUser $user, $id, array $options) {
		$this->credentialsManager->store($user->getUID(), $this->getCredentialsIdentifier($id), [
			'user' => $options['user'], // explicitly copy the fields we want instead of just passing the entire $options array
			'password' => $options['password'] // this way we prevent users from being able to modify any other field
		]);
	}

	public function manipulateStorageConfig(IStorageConfig &$storage, IUser $user = null) {
		if (!isset($user)) {
			throw new InsufficientDataForMeaningfulAnswerException('No credentials saved');
		}
		$uid = $user->getUID();
		$credentials = $this->credentialsManager->retrieve($uid, $this->getCredentialsIdentifier($storage->getId()));

		if (!isset($credentials)) {
			throw new InsufficientDataForMeaningfulAnswerException('No credentials saved');
		}

		$storage->setBackendOption('user', $credentials['user']);
		$storage->setBackendOption('password', $credentials['password']);
	}
}
