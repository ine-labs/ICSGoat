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

namespace OCA\OAuth2\Controller;

use OCA\OAuth2\Db\AccessToken;
use OCA\OAuth2\Db\AccessTokenMapper;
use OCA\OAuth2\Db\AuthorizationCodeMapper;
use OCA\OAuth2\Db\ClientMapper;
use OCA\OAuth2\Db\RefreshToken;
use OCA\OAuth2\Db\RefreshTokenMapper;
use OCA\OAuth2\Exceptions\UnsupportedPkceTransformException;
use OCA\OAuth2\Utilities;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;

class OAuthApiController extends ApiController {

	/** @var ClientMapper */
	private $clientMapper;

	/** @var AuthorizationCodeMapper */
	private $authorizationCodeMapper;

	/** @var AccessTokenMapper */
	private $accessTokenMapper;

	/** @var RefreshTokenMapper */
	private $refreshTokenMapper;

	/** @var IUserManager */
	private $userManager;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var ILogger */
	private $logger;

	/**
	 * OAuthApiController constructor.
	 *
	 * @param string $AppName The app's name.
	 * @param IRequest $request The request.
	 * @param ClientMapper $clientMapper The client mapper.
	 * @param AuthorizationCodeMapper $authorizationCodeMapper The authorization code mapper.
	 * @param AccessTokenMapper $accessTokenMapper The access token mapper.
	 * @param RefreshTokenMapper $refreshTokenMapper The refresh token mapper.
	 * @param IURLGenerator $urlGenerator The URL generator.
	 * @param ILogger $logger The logger.
	 */
	public function __construct(
		$AppName,
		IRequest $request,
		ClientMapper $clientMapper,
		AuthorizationCodeMapper $authorizationCodeMapper,
		AccessTokenMapper $accessTokenMapper,
		RefreshTokenMapper $refreshTokenMapper,
		IUserManager $userManager,
		IURLGenerator $urlGenerator,
		ILogger $logger
	) {
		parent::__construct($AppName, $request);

		$this->clientMapper = $clientMapper;
		$this->authorizationCodeMapper = $authorizationCodeMapper;
		$this->accessTokenMapper = $accessTokenMapper;
		$this->refreshTokenMapper = $refreshTokenMapper;
		$this->userManager = $userManager;
		$this->urlGenerator = $urlGenerator;
		$this->logger = $logger;
	}

	/**
	 * Implements the OAuth 2.0 Access Token Response.
	 *
	 * @param string $grant_type The authorization grant type.
	 * @param string $code The authorization code.
	 * @param string $redirect_uri The redirect URI.
	 * @param string $refresh_token The refresh token.
	 * @param string $code_verifier The PKCE code verifier.
	 * @return JSONResponse The Access Token or an empty JSON Object.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @CORS
	 */
	public function generateToken(
		$grant_type,
		$code = null,
		$redirect_uri = null,
		$refresh_token = null,
		$code_verifier = null
	) {
		if (!\is_string($grant_type)) {
			return new JSONResponse(['error' => 'invalid_request'], Http::STATUS_BAD_REQUEST);
		}

		if ($_SERVER['PHP_AUTH_USER'] === null || $_SERVER['PHP_AUTH_PW'] === null) {
			return new JSONResponse(['error' => 'invalid_request'], Http::STATUS_BAD_REQUEST);
		}

		try {
			/** @var \OCA\OAuth2\Db\Client $client */
			$client = $this->clientMapper->findByIdentifier($_SERVER['PHP_AUTH_USER']);
		} catch (DoesNotExistException $exception) {
			return new JSONResponse(['error' => 'invalid_client'], Http::STATUS_BAD_REQUEST);
		}

		if (\strcmp($client->getSecret(), $_SERVER['PHP_AUTH_PW']) !== 0) {
			return new JSONResponse(['error' => 'invalid_client'], Http::STATUS_BAD_REQUEST);
		}

		switch ($grant_type) {
			case 'authorization_code':
				if (!\is_string($code) || !\is_string($redirect_uri)) {
					return new JSONResponse(['error' => 'invalid_request'], Http::STATUS_BAD_REQUEST);
				}

				try {
					/** @var \OCA\OAuth2\Db\AuthorizationCode $authorizationCode */
					$authorizationCode = $this->authorizationCodeMapper->findByCode($code);
				} catch (DoesNotExistException $exception) {
					// could be that authorization code has been already cleaned up or client sends wrong authorization code
					$this->logger->debug("authorization code does not exist: {$exception}", ['app'=>__CLASS__]);
					return new JSONResponse(['error' => 'invalid_grant', 'error_description' => 'authorization code does not exist'], Http::STATUS_BAD_REQUEST);
				}

				if (\strcmp((string)$authorizationCode->getClientId(), (string)$client->getId()) !== 0) {
					$this->logger->debug("auth grant client ids mismatch: {$authorizationCode->getClientId()} != {$client->getId()}", ['app'=>__CLASS__]);
					return new JSONResponse(['error' => 'invalid_grant', 'error_description' => 'auth grant client ids mismatch'], Http::STATUS_BAD_REQUEST);
				}

				if ($authorizationCode->hasExpired()) {
					$this->logger->debug("auth grant expired: {$authorizationCode->getExpires()}", ['app'=>__CLASS__]);
					return new JSONResponse(['error' => 'invalid_grant', 'error_description' => 'auth grant expired'], Http::STATUS_BAD_REQUEST);
				}

				if (!Utilities::validateRedirectUri($client->getRedirectUri(), \urldecode($redirect_uri), $client->getAllowSubdomains())) {
					$this->logger->debug("auth grant redirect uri invalid: {$redirect_uri}", ['app'=>__CLASS__]);
					return new JSONResponse(['error' => 'invalid_grant', 'error_description' => 'auth grant redirect uri invalid'], Http::STATUS_BAD_REQUEST);
				}

				try {
					if (!$authorizationCode->isCodeVerifierValid($code_verifier)) {
						$this->logger->debug("code verifier invalid: {$code_verifier}", ['app' => __CLASS__]);
						return new JSONResponse(['error' => 'invalid_grant', 'error_description' => 'code verifier invalid'], Http::STATUS_BAD_REQUEST);
					}
				} catch (UnsupportedPkceTransformException $e) {
					$this->logger->debug("code challenge method invalid: {$e}", ['app' => __CLASS__]);
					return new JSONResponse(['error' => 'invalid_request', 'error_description' => $e->getMessage()], HTTP::STATUS_BAD_REQUEST);
				}

				$this->logger->info('An authorization code has been used by the client "' . $client->getName() . '" to request an access token.', ['app' => $this->appName]);

				$userId = $authorizationCode->getUserId();
				if (\strstr($userId, ':')) {
					list($userName, $userId) = \explode(':', $userId, 2);
				} else {
					$userName = $userId;
				}
				$this->authorizationCodeMapper->delete($authorizationCode);

				$userObj = $this->userManager->get($userId);
				if ($userObj === null || !$userObj->isEnabled()) {
					$this->logger->debug("the matching user is missing or disabled", ['app'=>__CLASS__]);
					return new JSONResponse(['error' => 'unauthorized_client', 'error_description' => 'user not enabled'], Http::STATUS_BAD_REQUEST);
				}

				break;
			case 'refresh_token':
				$statusCode = Http::STATUS_BAD_REQUEST;
				// This fixes the infinite loop issue with desktop client 2.4.2
				if (\preg_match('/\bmirall\b.+2\.4\.2/i', $this->request->getHeader('User-Agent'))) {
					$statusCode = Http::STATUS_OK;
				}

				if (!\is_string($refresh_token)) {
					return new JSONResponse(['error' => 'invalid_request'], $statusCode);
				}

				try {
					/** @var RefreshToken $refreshToken */
					$refreshToken = $this->refreshTokenMapper->findByToken($refresh_token);
				} catch (DoesNotExistException $exception) {
					// could be that token has been already cleaned up or client sends wrong token
					$this->logger->debug("refresh token does not exist: {$exception}", ['app'=>__CLASS__]);
					return new JSONResponse(['error' => 'invalid_grant', 'error_description' => 'refresh token does not exist'], $statusCode);
				}

				if (\strcmp((string)$refreshToken->getClientId(), (string)$client->getId()) !== 0) {
					$this->logger->debug("refresh grant client ids mismatch: {$refreshToken->getClientId()} != {$client->getId()}", ['app'=>__CLASS__]);
					return new JSONResponse(['error' => 'invalid_grant', 'error_description' => 'refresh grant client ids mismatch'], $statusCode);
				}

				$this->logger->info('A refresh token has been used by the client "' . $client->getName() . '" to request an access token.', ['app' => $this->appName]);

				$userId = $refreshToken->getUserId();
				if (\strstr($userId, ':')) {
					list($userName, $userId) = \explode(':', $userId, 2);
				} else {
					$userName = $userId;
				}

				$userObj = $this->userManager->get($userId);
				if ($userObj === null || !$userObj->isEnabled()) {
					$this->logger->debug("the matching user is missing or disabled", ['app'=>__CLASS__]);
					return new JSONResponse(['error' => 'unauthorized_client', 'error_description' => 'user not enabled'], Http::STATUS_BAD_REQUEST);
				}

				$relatedAccessToken = new AccessToken();
				$relatedAccessToken->setId($refreshToken->getAccessTokenId());
				$this->accessTokenMapper->delete($relatedAccessToken);
				$this->refreshTokenMapper->delete($refreshToken);

				break;
			default:
				\OC::$server->getLogger()->debug("unhandled grant type: {$grant_type}", ['app'=>__CLASS__]);
				return new JSONResponse(['error' => 'invalid_grant', 'error_description' => 'unhandled grant type'], Http::STATUS_BAD_REQUEST);
		}

		$token = Utilities::generateRandom();
		$accessToken = new AccessToken();
		$accessToken->setToken($token);
		$accessToken->setClientId($client->getId());
		$accessToken->setUserId($userId);
		$accessToken->resetExpires();
		$this->accessTokenMapper->insert($accessToken);

		$token = Utilities::generateRandom();
		$refreshToken = new RefreshToken();
		$refreshToken->setToken($token);
		$refreshToken->setClientId($client->getId());
		$refreshToken->setUserId($userId);
		$refreshToken->setAccessTokenId($accessToken->getId());
		$this->refreshTokenMapper->insert($refreshToken);

		return new JSONResponse(
			[
				'access_token' => $accessToken->getToken(),
				'token_type' => 'Bearer',
				'expires_in' => AccessToken::EXPIRATION_TIME,
				'refresh_token' => $refreshToken->getToken(),
				'user_id' => $userName,
				'message_url' => $this->urlGenerator->linkToRouteAbsolute($this->appName . '.page.authorizationSuccessful')
			]
		);
	}
}
