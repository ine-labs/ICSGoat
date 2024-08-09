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
namespace OCA\OpenIdConnect;

use JuliusPC\OpenIDConnectClientException;
use OC\HintException;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\ISession;
use OCP\IUserSession;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SessionVerifier {
	/** @var Logger */
	private $logger;
	/** @var ISession */
	private $session;
	/** @var ICacheFactory */
	private $cacheFactory;
	/** @var EventDispatcherInterface */
	private $eventDispatcher;
	/** @var Client */
	private $client;
	/** @var IUserSession */
	private $userSession;

	public function __construct(
		Logger $logger,
		ISession $session,
		IUserSession $userSession,
		ICacheFactory $cacheFactory,
		EventDispatcherInterface $eventDispatcher,
		Client $client
	) {
		$this->logger = $logger;
		$this->session = $session;
		$this->userSession = $userSession;
		$this->cacheFactory = $cacheFactory;
		$this->eventDispatcher = $eventDispatcher;
		$this->client = $client;
	}

	/**
	 * @throws HintException
	 * @throws OpenIDConnectClientException
	 */
	public function verifySession(): void {
		// verify open id token/session
		$sid = $this->session->get('oca.openid-connect.session-id');
		if ($sid) {
			$sessionValid = $this->cacheFactory
				->create('oca.openid-connect.sessions')
				->get($sid);
			if (!$sessionValid) {
				$this->logger->debug("Session $sid is no longer valid -> logout");
				$this->logout();
				return;
			}
		}

		// verify token as stored in session
		$accessToken = $this->session->get('oca.openid-connect.access-token');
		if ($accessToken === null) {
			return;
		}
		$idToken = $this->session->get('oca.openid-connect.id-token');

		// register logout handler
		$client = $this->client;
		$this->eventDispatcher->addListener('user.afterlogout', function () use ($accessToken, $idToken) {
			$this->afterLogout($accessToken, $idToken);
		});

		// cache access token information
		$exp = $this->getCache()->get($accessToken);
		if ($exp !== null) {
			$this->refreshToken($exp);
			return;
		}

		$client->setAccessToken($accessToken);
		$openIdConfig = $client->getOpenIdConfig();
		if (isset($openIdConfig['use-token-introspection-endpoint']) && $openIdConfig['use-token-introspection-endpoint']) {
			$introspectionClientId = $openIdConfig['token-introspection-endpoint-client-id'] ?? null;
			$introspectionClientSecret = $openIdConfig['token-introspection-endpoint-client-secret'] ?? null;

			$introData = $client->introspectToken($accessToken, '', $introspectionClientId, $introspectionClientSecret);
			$this->logger->debug('Introspection info: ' . \json_encode($introData) . ' for access token:' . $accessToken);
			if (\property_exists($introData, 'error')) {
				$this->logger->error('Token introspection failed: ' . \json_encode($introData));
				$this->logout();
				throw new HintException("Verifying token failed: {$introData->error}");
			}
			if (!$introData->active) {
				$this->logger->error('Token (as per introspection) is inactive: ' . \json_encode($introData));
				$this->logout();
				return;
			}

			$this->getCache()->set($accessToken, $introData->exp);
			$this->refreshToken($introData->exp);
		} else {
			if (!$client->verifyJWTsignature($accessToken)) {
				$this->logger->error('Token cannot be verified: ' . $accessToken);
				$this->logout();
				throw new OpenIDConnectClientException('Token cannot be verified.');
			}
			$payload = $client->getAccessTokenPayload();
			$this->logger->debug('Access token payload: ' . \json_encode($payload) . ' for access token:' . $accessToken);

			$this->getCache()->set($accessToken, $payload->exp);
			/* @phan-suppress-next-line PhanTypeExpectedObjectPropAccess */
			$this->refreshToken($payload->exp);
		}
	}

	public function afterLogout($accessToken, $idToken): void {
		// only call if access token is still valid
		try {
			$this->logger->debug('OIDC Logout: revoking token' . $accessToken);
			$revokeData = $this->client->revokeToken($accessToken);
			$this->logger->debug('Revocation info: ' . \json_encode($revokeData));
		} catch (OpenIDConnectClientException $ex) {
			$this->logger->logException($ex);
		}
		try {
			$this->session->remove('oca.openid-connect.access-token');
			$this->session->remove('oca.openid-connect.refresh-token');
			$this->session->remove('oca.openid-connect.id-token');
			$this->logger->debug('OIDC Logout: ending session ' . $accessToken . ' id: ' . $idToken);
			$openIdConfig = $this->client->getOpenIdConfig();
			$redirectUri = $openIdConfig['post_logout_redirect_uri'] ?? null;
			$this->client->signOut($idToken, $redirectUri);
		} catch (OpenIDConnectClientException $ex) {
			$this->logger->logException($ex);
		}
	}

	private function getCache(): ICache {
		return $this->cacheFactory->create('oca.openid-connect');
	}

	/**
	 * @param int $exp
	 * @throws OpenIDConnectClientException
	 * @throws HintException
	 */
	private function refreshToken($exp): void {
		$expiring = $exp - \time();
		if ($expiring < 5 * 60) {
			$refreshToken = $this->session->get('oca.openid-connect.refresh-token');
			if ($refreshToken) {
				$response = $this->client->refreshToken($refreshToken);
				$this->logger->debug('RefreshToken response: ' . \json_encode($response));
				if (\property_exists($response, 'error')) {
					$this->logger->error('Refresh token failed: ' . \json_encode($response));
					$this->logout();
					throw new HintException("Verifying token failed: {$response->error}");
				}

				$this->session->set('oca.openid-connect.id-token', $this->client->getIdToken());
				$this->session->set('oca.openid-connect.access-token', $this->client->getAccessToken());
				$this->session->set('oca.openid-connect.refresh-token', $this->client->getRefreshToken());
			} else {
				$this->logger->debug('No refresh token available -> nothing to do. We will be kicked out as soon as the access token expires.');
			}
		}
	}

	private function logout(): void {
		$this->logger->error('Calling Application:logout');

		$this->session->remove('oca.openid-connect.access-token');
		$this->session->remove('oca.openid-connect.refresh-token');
		$this->session->remove('oca.openid-connect.id-token');
		$this->userSession->logout();
	}
}
