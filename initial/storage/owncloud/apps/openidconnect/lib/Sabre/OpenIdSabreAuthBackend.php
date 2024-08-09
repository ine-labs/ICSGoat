<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2020, ownCloud GmbH
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
namespace OCA\OpenIdConnect\Sabre;

use OC\User\Session;
use OCA\DAV\Connector\Sabre\Auth;
use OCA\OpenIdConnect\OpenIdConnectAuthModule;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserSession;
use Sabre\DAV\Auth\Backend\AbstractBearer;

class OpenIdSabreAuthBackend extends AbstractBearer {
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

	/** @var OpenIdConnectAuthModule */
	private $authModule;

	/**
	 * OAuth2 constructor.
	 *
	 * @param ISession $session The session.
	 * @param IUserSession $userSession The user session.
	 * @param IRequest $request The request.
	 * @param OpenIdConnectAuthModule $authModule
	 * @param string $principalPrefix The principal prefix.
	 * @throws \Exception
	 */
	public function __construct(
		ISession $session,
		IUserSession $userSession,
		IRequest $request,
		OpenIdConnectAuthModule $authModule,
		$principalPrefix = 'principals/users/'
	) {
		if (!$userSession instanceof Session) {
			throw new \Exception('We rely on internal implementation!');
		}

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
	 * @throws \OC\User\LoginException
	 */
	protected function validateBearerToken($bearerToken) {
		if ($this->userSession->isLoggedIn() &&
			$this->isDavAuthenticated($this->userSession->getUser()->getUID())) {
			try {

				// verify the bearer token
				$tokenUser = $this->authModule->authToken($bearerToken);
				if ($tokenUser === null) {
					return false;
				}

				// setup the user
				$userId = $this->userSession->getUser()->getUID();
				$this->setupFilesystem($userId);
				$this->session->close();
				return $this->principalPrefix . $userId;
			} catch (\Exception $ex) {
				$this->session->close();
				return false;
			}
		}

		$this->setupFilesystem();

		try {
			// we have to go through IUserSession here to login the user properly
			if ($this->userSession->tryAuthModuleLogin($this->request)) {
				$userId = $this->userSession->getUser()->getUID();
				$this->setupFilesystem($userId);
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

	/**
	 * @param string $userId
	 * @codeCoverageIgnore
	 */
	protected function setupFilesystem(string $userId = ''): void {
		\OC_Util::setupFS($userId);
	}
}
