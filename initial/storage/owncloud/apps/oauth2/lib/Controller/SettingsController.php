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

use OCA\OAuth2\Db\AccessTokenMapper;
use OCA\OAuth2\Db\AuthorizationCodeMapper;
use OCA\OAuth2\Db\Client;
use OCA\OAuth2\Db\ClientMapper;
use OCA\OAuth2\Db\RefreshTokenMapper;
use OCA\OAuth2\Utilities;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Template;
use Rowbot\URL\URL;

class SettingsController extends Controller {

	/** @var ClientMapper */
	private $clientMapper;

	/** @var AuthorizationCodeMapper */
	private $authorizationCodeMapper;

	/** @var AccessTokenMapper */
	private $accessTokenMapper;

	/** @var RefreshTokenMapper */
	private $refreshTokenMapper;

	/** @var string */
	private $userId;

	/** @var IL10N  */
	private $l10n;

	/** @var ILogger */
	private $logger;

	/** @var IURLGenerator */
	private $urlGenerator;

	/**
	 * SettingsController constructor.
	 *
	 * @param string $AppName The app's name.
	 * @param IRequest $request The request.
	 * @param ClientMapper $clientMapper The client mapper.
	 * @param AuthorizationCodeMapper $authorizationCodeMapper The authorization code mapper.
	 * @param AccessTokenMapper $accessTokenMapper The access token mapper.
	 * @param RefreshTokenMapper $refreshTokenMapper The refresh token mapper.
	 * @param string $UserId The user ID.
	 * @param ILogger $logger The logger.
	 * @param IURLGenerator $urlGenerator Use for url generation
	 */
	public function __construct(
		$AppName,
		IRequest $request,
		ClientMapper $clientMapper,
		AuthorizationCodeMapper $authorizationCodeMapper,
		AccessTokenMapper $accessTokenMapper,
		RefreshTokenMapper $refreshTokenMapper,
		$UserId,
		IL10N $l10n,
		ILogger $logger,
		IURLGenerator $urlGenerator
	) {
		parent::__construct($AppName, $request);

		$this->clientMapper = $clientMapper;
		$this->authorizationCodeMapper = $authorizationCodeMapper;
		$this->accessTokenMapper = $accessTokenMapper;
		$this->refreshTokenMapper = $refreshTokenMapper;
		$this->userId = $UserId;
		$this->l10n = $l10n;
		$this->logger = $logger;
		$this->urlGenerator = $urlGenerator;
	}

	public function addClient(): JSONResponse {
		$redirectUri = \trim($this->request->getParam('redirect_uri', ''));
		$name = \trim($this->request->getParam('name', ''));
		if ($name === '') {
			return $this->sendErrorResponse($this->l10n->t('Name must not be empty'));
		}
		if ($redirectUri === '') {
			return $this->sendErrorResponse($this->l10n->t('Redirect URI must not be empty'));
		}
		if (!Utilities::isValidUrl($redirectUri)) {
			return $this->sendErrorResponse($this->l10n->t('Redirect URI must be a valid URL'));
		}

		try {
			// The name should be unique
			$this->clientMapper->findByName($name);
			return $this->sendErrorResponse($this->l10n->t('Name %s already exists', [$name]));
		} catch (DoesNotExistException $e) {
			// expected when the client name is not a duplicate
		}

		$client = new Client();
		$client->setIdentifier(Utilities::generateRandom());
		$client->setSecret(Utilities::generateRandom());
		$client->setRedirectUri($redirectUri);
		$client->setName($name);

		$allowSubdomains = $this->request->getParam('allow_subdomains', null) !== null;
		$client->setAllowSubdomains($allowSubdomains);
		$trusted = $this->request->getParam('trusted', null) !== null;

		$rURI = new URL(Utilities::removeWildcardPort($redirectUri));
		if (($rURI->hostname === 'localhost' || $rURI->hostname === '127.0.0.1') && $trusted) {
			return $this->sendErrorResponse($this->l10n->t('Cannot set localhost as trusted.'));
		}
		$client->setTrusted($trusted);

		$this->clientMapper->insert($client);
		$this->logger->info('The client "' . $client->getName() . '" has been added.', ['app' => $this->appName]);

		$template = new Template('oauth2', 'client.part', '');
		/** @phan-suppress-next-line PhanTypeMismatchArgument */
		$template->assign('client', $this->clientMapper->findByIdentifier($client->getIdentifier()));
		return new JSONResponse(
			[
				'status' => 'success',
				'rowHtml' =>  $template->fetchPage(),
				'data' => [] // OC.msg needs this
			]
		);
	}

	/**
	 * Deletes a client.
	 *
	 * @param int $id The client identifier.
	 *
	 * @return JSONResponse
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 */
	public function deleteClient($id): JSONResponse {
		if (!\is_int($id)) {
			return $this->sendErrorResponse($this->l10n->t('Client id must be a number'));
		}
		/** @var Client $client */
		$client = $this->clientMapper->find($id);
		$clientName = $client->getName();
		$this->clientMapper->delete($client);

		$this->authorizationCodeMapper->deleteByClient($id);
		$this->accessTokenMapper->deleteByClient($id);
		$this->refreshTokenMapper->deleteByClient($id);

		$this->logger->info('The client "' . $clientName . '" has been deleted.', ['app' => $this->appName]);
		return new JSONResponse(
			[
				'status' => 'success',
				'clientIdentifier' =>  $client->getIdentifier(),
				'data' => [] // OC.msg needs this
			]
		);
	}

	/**
	 * Revokes the authorization for a client.
	 *
	 * @param int $id The client identifier.
	 *
	 * @return RedirectResponse Redirection to the settings page.
	 *
	 * @NoAdminRequired
	 */
	public function revokeAuthorization($id) {
		if (!\is_int($id)) {
			return new RedirectResponse(
				$this->urlGenerator->linkToRouteAbsolute(
					'settings.SettingsPage.getPersonal',
					['sectionid' => 'security']
				) . '#oauth2'
			);
		}

		$this->authorizationCodeMapper->deleteByClientUser($id, $this->userId);
		$this->accessTokenMapper->deleteByClientUser($id, $this->userId);
		$this->refreshTokenMapper->deleteByClientUser($id, $this->userId);

		return new RedirectResponse(
			$this->urlGenerator->linkToRouteAbsolute(
				'settings.SettingsPage.getPersonal',
				['sectionid' => 'security']
			) . '#oauth2'
		);
	}

	private function sendErrorResponse(string $message): JSONResponse {
		return new JSONResponse(
			[
				'status' => 'error',
				'errorMessage' => $message,
				'data' => [] // OC.msg needs this
			]
		);
	}
}
