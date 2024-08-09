<?php
/**
 * @author Project Seminar "sciebo@Learnweb" of the University of Muenster
 * @copyright Copyright (c) 2017, University of Muenster
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
 */

namespace OCA\OAuth2\Hooks;

use OC\User\Manager;
use OC\User\User;
use OCA\OAuth2\Db\AccessTokenMapper;
use OCA\OAuth2\Db\AuthorizationCodeMapper;
use OCA\OAuth2\Db\RefreshTokenMapper;
use OCP\ILogger;

class UserHooks {

	/** @var Manager */
	private $userManager;

	/** @var AuthorizationCodeMapper */
	private $authorizationCodeMapper;

	/** @var  AccessTokenMapper */
	private $accessTokenMapper;

	/** @var RefreshTokenMapper */
	private $refreshTokenMapper;

	/** @var ILogger */
	private $logger;

	/** @var string */
	private $appName;

	/**
	 * UserHooks constructor.
	 *
	 * @param Manager $userManager The user manager.
	 * @param AuthorizationCodeMapper $authorizationCodeMapper The authorization code mapper.
	 * @param AccessTokenMapper $accessTokenMapper The access token mapper.
	 * @param RefreshTokenMapper $refreshTokenMapper The refresh token mapper.
	 * @param ILogger $logger The logger.
	 * @param string $AppName The app's name.
	 */
	public function __construct(
		Manager $userManager,
		AuthorizationCodeMapper $authorizationCodeMapper,
		AccessTokenMapper $accessTokenMapper,
		RefreshTokenMapper $refreshTokenMapper,
		ILogger $logger,
		$AppName
	) {
		$this->userManager = $userManager;
		$this->authorizationCodeMapper = $authorizationCodeMapper;
		$this->accessTokenMapper = $accessTokenMapper;
		$this->refreshTokenMapper = $refreshTokenMapper;
		$this->logger = $logger;
		$this->appName = $AppName;
	}

	/**
	 * Registers a pre-delete hook for users to delete authorization codes,
	 * access tokens and refresh tokens that reference the user.
	 */
	public function register() {
		/**
		 * @param User $user .
		 */
		$callback = function ($user) {
			if ($user->getUID() !== null) {
				$this->logger->info('Deleting authorization codes, access tokens and refresh tokens referencing the user to be deleted "' . $user->getUID() . '".', ['app' => $this->appName]);

				$this->authorizationCodeMapper->deleteByUser($user->getUID());
				$this->accessTokenMapper->deleteByUser($user->getUID());
				$this->refreshTokenMapper->deleteByUser($user->getUID());
			}
		};
		/** @phan-suppress-next-line PhanUndeclaredMethod */
		$this->userManager->listen('\OC\User', 'preDelete', $callback);
	}
}
