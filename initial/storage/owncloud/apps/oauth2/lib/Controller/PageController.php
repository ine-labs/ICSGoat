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

use OC_Util;
use OCA\OAuth2\Db\AccessToken;
use OCA\OAuth2\Db\AccessTokenMapper;
use OCA\OAuth2\Db\AuthorizationCode;
use OCA\OAuth2\Db\AuthorizationCodeMapper;
use OCA\OAuth2\Db\ClientMapper;
use OCA\OAuth2\Utilities;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Util;

class PageController extends Controller {

	/** @var ClientMapper */
	private $clientMapper;
	/** @var AuthorizationCodeMapper */
	private $authorizationCodeMapper;
	/** @var AccessTokenMapper */
	private $accessTokenMapper;
	/** @var ILogger */
	private $logger;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IUserSession */
	private $userSession;
	/** @var IUserManager */
	private $userManager;

	/**
	 * PageController constructor.
	 *
	 * @param string $appName The app's name.
	 * @param IRequest $request The request.
	 * @param ClientMapper $clientMapper The client mapper.
	 * @param AuthorizationCodeMapper $authorizationCodeMapper The authorization code mapper.
	 * @param AccessTokenMapper $accessTokenMapper The access token mapper.
	 * @param ILogger $logger The logger.
	 * @param IURLGenerator $urlGenerator
	 * @param IUserSession $userSession
	 * @param IUserManager $userManager
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		ClientMapper $clientMapper,
		AuthorizationCodeMapper $authorizationCodeMapper,
		AccessTokenMapper $accessTokenMapper,
		ILogger $logger,
		IURLGenerator $urlGenerator,
		IUserSession $userSession,
		IUserManager $userManager
	) {
		parent::__construct($appName, $request);

		$this->clientMapper = $clientMapper;
		$this->authorizationCodeMapper = $authorizationCodeMapper;
		$this->accessTokenMapper = $accessTokenMapper;
		$this->logger = $logger;
		$this->urlGenerator = $urlGenerator;
		$this->userSession = $userSession;
		$this->userManager = $userManager;
	}

	/**
	 * Shows a view for the user to authorize a client.
	 *
	 * @param string $response_type The expected response type.
	 * @param string $client_id The client identifier.
	 * @param string $redirect_uri The redirection URI.
	 * @param string | null $state The state.
	 * @param string | null $user
	 *
	 * @return TemplateResponse | RedirectResponse The authorize view or the
	 * authorize-error view with a redirection to the
	 * default page URL.
	 *
	 * @throws MultipleObjectsReturnedException
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function authorize(
		$response_type,
		$client_id,
		$redirect_uri,
		$state = null,
		$user = null,
		$code_challenge = null,
		$code_challenge_method = null
	) {
		if (!\is_string($response_type) || !\is_string($client_id)
			|| !\is_string($redirect_uri) || ($state !== null && !\is_string($state))
		) {
			$this->logger->error('Invalid OAuth request - one of the mandatory query parameters is missing');
			return new TemplateResponse(
				$this->appName,
				'authorize-error',
				['client_name' => null],
				'guest'
			);
		}

		if ($user !== null && $user !== $this->userSession->getUser()->getUserName()) {
			$logoutUrl = $this->urlGenerator->linkToRouteAbsolute(
				'oauth2.page.logout',
				[
					'user' => $user,
					'requesttoken' => Util::callRegister(),
					'response_type' => $response_type,
					'client_id' => $client_id,
					'redirect_uri' => \urlencode($redirect_uri),
					'state' => $state
				]
			);
			$currentUser = $this->userSession->getUser();
			$currentUser = $this->buildDisplayForUser($currentUser);
			$requestedUser = $this->buildDisplayForUser($user);
			return new TemplateResponse(
				$this->appName,
				'switch-user',
				['current_user' => $currentUser, 'requested_user' => $requestedUser,
					'logout_url' => $logoutUrl],
				'guest'
			);
		}
		try {
			/** @var \OCA\OAuth2\Db\Client $client */
			$client = $this->clientMapper->findByIdentifier($client_id);
		} catch (DoesNotExistException $exception) {
			$this->logger->error("Invalid OAuth request with client-id $client_id");
			return new TemplateResponse(
				$this->appName,
				'authorize-error',
				['client_name' => null],
				'guest'
			);
		}

		if (!Utilities::validateRedirectUri($client->getRedirectUri(), \urldecode($redirect_uri), $client->getAllowSubdomains())) {
			$this->logger->error("Invalid OAuth request with invalid redirect_uri: $redirect_uri !== {$client->getRedirectUri()}");
			return new TemplateResponse(
				$this->appName,
				'authorize-error',
				['client_name' => $client->getName()],
				'guest'
			);
		}

		if (!\in_array($response_type, ['code', 'token'])) {
			$this->logger->error("Invalid OAuth request with wrong response_type: $response_type");

			$errorRedirectUri = \urldecode($redirect_uri);
			$errorRedirectUri .= '?error=unsupported_response_type';
			if ($state !== null) {
				$errorRedirectUri .= '&state=' . \urlencode($state);
			}

			return new RedirectResponse($errorRedirectUri);
		}

		// trusted clients get their auth code back directly
		if ($client->getTrusted()) {
			return $this->generateAuthorizationCode($response_type, $client_id, $redirect_uri, $state, $code_challenge, $code_challenge_method);
		}

		$logoutUrl = $this->urlGenerator->linkToRouteAbsolute(
			'oauth2.page.logout',
			[
				'user' => $user,
				'requesttoken' => Util::callRegister(),
				'response_type' => $response_type,
				'client_id' => $client_id,
				'redirect_uri' => \urlencode($redirect_uri),
				'state' => $state
			]
		);
		$currentUser = $this->userSession->getUser();
		$currentUser = $this->buildDisplayForUser($currentUser);

		return new TemplateResponse($this->appName, 'authorize', [
			'client_name' => $client->getName(),
			'current_user' => $currentUser,
			'logout_url' => $logoutUrl
		], 'guest');
	}

	/**
	 * Implements the OAuth 2.0 Authorization Response.
	 *
	 * @param string $response_type The expected response type.
	 * @param string $client_id The client identifier.
	 * @param string $redirect_uri The redirection URI.
	 * @param string $state The state.
	 * @param string $code_challenge The PKCE code challenge.
	 * @param string $code_challenge_method The PKCE code challenge method.
	 *
	 * @return RedirectResponse Redirection to the given redirect_uri or to the
	 * default page URL.
	 *
	 * @throws MultipleObjectsReturnedException
	 * @NoAdminRequired
	 */
	public function generateAuthorizationCode($response_type, $client_id, $redirect_uri, $state = null, $code_challenge = null, $code_challenge_method = null) {
		if (!\is_string($response_type) || !\is_string($client_id)
			|| !\is_string($redirect_uri) || ($state !== null && !\is_string($state))
		) {
			return new RedirectResponse(OC_Util::getDefaultPageUrl());
		}

		$userName = $this->userSession->getUser()->getUserName();
		$userUID  = $this->userSession->getUser()->getUID();

		if ($userName !== null && $userName !== $userUID) {
			$userNameAndUid = \implode(':', [ $userName, $userUID ]);
		} else {
			$userNameAndUid = $userUID;
		}

		switch ($response_type) {
			case 'code':
				try {
					/** @var \OCA\OAuth2\Db\Client $client */
					$client = $this->clientMapper->findByIdentifier($client_id);
				} catch (DoesNotExistException $exception) {
					return new RedirectResponse(OC_Util::getDefaultPageUrl());
				}

				if (!Utilities::validateRedirectUri($client->getRedirectUri(), \urldecode($redirect_uri), $client->getAllowSubdomains())) {
					return new RedirectResponse(OC_Util::getDefaultPageUrl());
				}

				$code = Utilities::generateRandom();
				$authorizationCode = new AuthorizationCode();
				$authorizationCode->setCode($code);
				$authorizationCode->setClientId($client->getId());
				$authorizationCode->setUserId($userNameAndUid);
				$authorizationCode->resetExpires();
				$authorizationCode->setCodeChallenge($code_challenge);
				$authorizationCode->setCodeChallengeMethod($code_challenge_method);
				$this->authorizationCodeMapper->insert($authorizationCode);

				$result = \urldecode($redirect_uri);
				$result .= '?code=' . \urlencode($code);
				if ($state !== null) {
					$result .= '&state=' . \urlencode($state);
				}

				$this->logger->info('An authorization code has been issued for the client "' . $client->getName() . '".', ['app' => $this->appName]);

				return new RedirectResponse($result);
			case 'token':
				try {
					/** @var \OCA\OAuth2\Db\Client $client */
					$client = $this->clientMapper->findByIdentifier($client_id);
				} catch (DoesNotExistException $exception) {
					return new RedirectResponse(OC_Util::getDefaultPageUrl());
				}

				if (!Utilities::validateRedirectUri($client->getRedirectUri(), \urldecode($redirect_uri), $client->getAllowSubdomains())) {
					return new RedirectResponse(OC_Util::getDefaultPageUrl());
				}

				$token = Utilities::generateRandom();
				$accessToken = new AccessToken();
				$accessToken->setToken($token);
				$accessToken->setClientId($client->getId());
				$accessToken->setUserId($userNameAndUid);
				$accessToken->resetExpires();
				$this->accessTokenMapper->insert($accessToken);

				$result = \urldecode($redirect_uri);
				$result .= '?access_token=' . \urlencode($accessToken->getToken());
				$result .= '&expires_in=' . \urlencode((string)AccessToken::EXPIRATION_TIME);
				if ($state !== null) {
					$result .= '&state=' . \urlencode($state);
				}

				$this->logger->info('An authorization code has been issued for the client "' . $client->getName() . '".', ['app' => $this->appName]);

				return new RedirectResponse($result);
			default:
				return new RedirectResponse(OC_Util::getDefaultPageUrl());
		}
	}

	/**
	 * Shows a message for successful authorization.
	 *
	 * @return TemplateResponse The authorization-successful view.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function authorizationSuccessful() {
		return new TemplateResponse($this->appName, 'authorization-successful', [], 'guest');
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $user
	 * @param string $response_type
	 * @param string $client_id
	 * @param string $redirect_uri
	 * @param string | null $state
	 * @return RedirectResponse | TemplateResponse
	 */
	public function logout($user, $response_type, $client_id, $redirect_uri, $state = null) {
		if (!\is_string($response_type) || !\is_string($client_id)
			|| !\is_string($redirect_uri) || ($state !== null && !\is_string($state))
		) {
			return new TemplateResponse(
				$this->appName,
				'authorize-error',
				['client_name' => null, 'back_url' => OC_Util::getDefaultPageUrl()]
			);
		}
		// logout the current user
		$this->userSession->logout();

		$redirectUrl = $this->urlGenerator->linkToRoute('oauth2.page.authorize', [
			'response_type' => $response_type,
			'client_id' => $client_id,
			'redirect_uri' => $redirect_uri,
			'state' => $state,
			'user' => $user
		]);

		// redirect the browser to the login page and set the redirect_url to the authorize page of oauth2
		return new RedirectResponse($this->urlGenerator->linkToRouteAbsolute(
			'core.login.showLoginForm',
			[
				'user' => $user,
				'redirect_url' => $redirectUrl
			]
		));
	}

	/**
	 * @param IUser|string $userIdOrUser
	 * @return string
	 */
	private function buildDisplayForUser($userIdOrUser) {
		$currentUser = $userIdOrUser;
		if (!$userIdOrUser instanceof IUser) {
			$currentUser = $this->userManager->get($userIdOrUser);
			if ($currentUser === null) {
				$escapedUserId = Util::sanitizeHTML($userIdOrUser);
				return "<strong>$escapedUserId</strong>";
			}
		}
		$displayName = $currentUser->getDisplayName();
		$userId = $currentUser->getUID();
		if (empty($displayName) || $displayName === $userId) {
			$escapedUserId = Util::sanitizeHTML($userId);
			return "<strong>$escapedUserId</strong>";
		}
		$userId = Util::sanitizeHTML($userId);
		$escapedDisplayName = Util::sanitizeHTML($displayName);
		return "<span class='hasTooltip' data-original-title='$userId'><strong>$escapedDisplayName</strong></span>";
	}
}
