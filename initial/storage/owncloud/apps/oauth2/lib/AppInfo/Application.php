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

namespace OCA\OAuth2\AppInfo;

use OCA\OAuth2\AuthModule;
use OCA\OAuth2\Db\ClientMapper;
use OCA\OAuth2\Hooks\UserHooks;
use OCA\OAuth2\Sabre\OAuth2;
use OCP\AppFramework\App;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\SabrePluginEvent;
use Sabre\DAV\Auth\Plugin;

class Application extends App {

	/**
	 * Application constructor.
	 *
	 * @param array $urlParams Variables extracted from the routes.
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct('oauth2', $urlParams);

		$container = $this->getContainer();

		// Logger
		$container->registerService('Logger', function ($c) {
			return $c->query('ServerContainer')->getLogger();
		});

		// User Manager
		$container->registerService('UserManager', function ($c) {
			return $c->query('ServerContainer')->getUserManager();
		});

		// Hooks
		$container->registerService('UserHooks', function ($c) {
			return new UserHooks(
				$c->query('ServerContainer')->getUserManager(),
				$c->query('OCA\OAuth2\Db\AuthorizationCodeMapper'),
				$c->query('OCA\OAuth2\Db\AccessTokenMapper'),
				$c->query('OCA\OAuth2\Db\RefreshTokenMapper'),
				$c->query('Logger'),
				$c->query('AppName')
			);
		});

		// Add event listener
		$dispatcher = $this->getContainer()->getServer()->getEventDispatcher();
		$dispatcher->addListener('OCA\DAV\Connector\Sabre::authInit', function ($event) {
			if ($event instanceof SabrePluginEvent) {
				$authPlugin = $event->getServer()->getPlugin('auth');
				if ($authPlugin instanceof Plugin) {
					$authPlugin->addBackend(
						new OAuth2(
							\OC::$server->getSession(),
							\OC::$server->getUserSession(),
							\OC::$server->getRequest(),
							new AuthModule(),
							'principals/'
						)
					);
				}
			}
		});
	}

	public function boot() {
		$this->getContainer()->query('UserHooks')->register();
		$request = $this->getContainer()->getServer()->getRequest();
		if ($request->getMethod() !== 'GET') {
			return;
		}
		$redirectUrl = $request->getParam('redirect_url');
		if ($redirectUrl === null) {
			return;
		}

		$urlParts = \parse_url(\urldecode($redirectUrl));
		/** @phan-suppress-next-line PhanTypePossiblyInvalidDimOffset */
		if (\strpos($urlParts['path'], 'apps/oauth2/authorize') === false) {
			return;
		}
		$params = [];
		/** @phan-suppress-next-line PhanTypePossiblyInvalidDimOffset */
		\parse_str($urlParts['query'], $params);
		if (!isset($params['client_id'])) {
			return;
		}
		/** @var ClientMapper $mapper */
		$mapper = \OC::$server->query(ClientMapper::class);
		try {
			/** @var \OCA\OAuth2\Db\Client $client */
			$client = $mapper->findByIdentifier($params['client_id']);
			\OCP\Util::addScript('oauth2', 'login');
			\OCP\Util::addStyle('oauth2', 'login');
			$data = ['key' => 'oauth2', 'client' => $client->getName()];
			if (isset($params['user'])) {
				$data['user'] = $params['user'];
			}
			\OCP\Util::addHeader('data', $data);
		} catch (DoesNotExistException $ex) {
			// ignore - the given client id is not known
		}
	}
}
