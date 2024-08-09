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

namespace OCA\OAuth2\Sabre;

use OC\User\Session;
use OCA\DAV\Connector\Sabre\Auth;
use OCA\OAuth2\AuthModule;
use OCP\IRequest;
use OCP\ISession;

class OAuth2 extends AbstractBearer {
	/** @phan-suppress-next-line PhanUndeclaredClassConstant */
	public const DAV_AUTHENTICATED = Auth::DAV_AUTHENTICATED;

	/**
	 * This is the prefix that will be used to generate principal urls.
	 *
	 * @var string
	 */
	protected $principalPrefix;

	/** @var ISession */
	private $session;

	/** @var Session */
	private $userSession;

	/** @var IRequest */
	private $request;

	/** @var AuthModule */
	private $authModule;

	/**
	 * OAuth2 constructor.
	 *
	 * @param ISession $session The session.
	 * @param Session $userSession The user session.
	 * @param IRequest $request The request.
	 * @param string $principalPrefix The principal prefix.
	 */
	public function __construct(
		ISession $session,
		Session $userSession,
		IRequest $request,
		AuthModule $authModule,
		$principalPrefix = 'principals/users/'
	) {
		$this->session = $session;
		$this->userSession = $userSession;
		$this->request = $request;
		$this->authModule = $authModule;
		$this->principalPrefix = $principalPrefix;

		// setup realm
		$defaults = new \OC_Defaults();
		$this->realm = $defaults->getName();
	}

	/**
	 * Checks whether the user has initially authenticated via DAV.
	 *
	 * This is required for WebDAV clients that resent the cookies even when the
	 * account was changed.
	 *
	 * @see https://github.com/owncloud/core/issues/13245
	 *
	 * @param string $username The username.
	 * @return bool True if the user initially authenticated via DAV, false otherwise.
	 */
	private function isDavAuthenticated($username) {
		return $this->session->get(self::DAV_AUTHENTICATED) !== null &&
			$this->session->get(self::DAV_AUTHENTICATED) === $username;
	}

	/**
	 * Validates a Bearer token.
	 *
	 * This method should return the full principal url, or false if the
	 * token was incorrect.
	 *
	 * @param string $bearerToken The Bearer token.
	 * @return string|false The full principal url, if the token is valid, false otherwise.
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 */
	protected function validateBearerToken($bearerToken) {
		if ($this->userSession->isLoggedIn() &&
			$this->isDavAuthenticated($this->userSession->getUser()->getUID())) {

			// verify the bearer token
			$tokenUser = $this->authModule->authToken($bearerToken);
			if ($tokenUser === null) {
				return false;
			}

			// setup the user
			$userId = $this->userSession->getUser()->getUID();
			\OC_Util::setupFS($userId);
			$this->session->close();
			return $this->principalPrefix . $userId;
		}

		\OC_Util::setupFS(); //login hooks may need early access to the filesystem

		try {
			if ($this->userSession->tryAuthModuleLogin($this->request)) {
				$userId = $this->userSession->getUser()->getUID();
				\OC_Util::setupFS($userId);
				$this->session->set(self::DAV_AUTHENTICATED, $userId);
				$this->session->close();
				return $this->principalPrefix . $userId;
			}

			$this->session->close();
			return false;
		} catch (\Exception $ex) {
			$this->session->close();
			return false;
		}
	}
}
