<?php
/**
 * @author Viktar Dubiniuk <dubinuk@owncloud.com>
 * @author Ilja Neumann <ineumann@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
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

namespace OCA\Market;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\TransferException;
use OCP\App\AppManagerException;
use OCP\Http\Client\IClientService;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IL10N;

/**
 * Class HttpService
 *
 * @package OCA\Market
 */
class HttpService {
	public const CACHE_KEY = 'ocmp';

	public const APPS = 'apps_%s';
	public const BUNDLES = 'bundles';
	public const CATEGORIES = 'categories';
	public const DEMO_KEY = 'demo_license_information';

	private $urlConfig = [
		self::APPS => '/api/v1/platform/%s/apps.json',
		self::BUNDLES => '/api/v1/bundles.json',
		self::CATEGORIES => '/api/v1/categories.json',
		self::DEMO_KEY => '/api/v1/instance/%s/demo-key'
	];

	/** @var IClientService */
	private $httpClientService;
	/** @var VersionHelper */
	private $versionHelper;
	/** @var ICacheFactory */
	private $cacheFactory;
	/** @var IConfig */
	private $config;
	/** @var IL10N */
	private $l10n;

	/**
	 * Service constructor.
	 *
	 * @param IClientService $httpClientService
	 * @param IConfig $config
	 * @param ICacheFactory $cacheFactory
	 * @param IL10N $l10n
	 */
	public function __construct(
		IClientService $httpClientService,
		VersionHelper $versionHelper,
		IConfig $config,
		ICacheFactory $cacheFactory,
		IL10N $l10n
	) {
		$this->httpClientService = $httpClientService;
		$this->versionHelper = $versionHelper;
		$this->config = $config;
		$this->cacheFactory = $cacheFactory;
		$this->l10n = $l10n;
	}

	/**
	 * @return mixed
	 *
	 * @throws AppManagerException
	 */
	public function getApps() {
		return $this->getEntities(self::APPS);
	}

	/**
	 * @return mixed
	 *
	 * @throws AppManagerException
	 */
	public function getBundles() {
		return $this->getEntities(self::BUNDLES);
	}

	/**
	 * @return mixed
	 *
	 * @throws AppManagerException
	 */
	public function getCategories() {
		return $this->getEntities(self::CATEGORIES);
	}

	/**
	 * @return mixed
	 *
	 * @throws AppManagerException
	 */
	public function getDemoKey() {
		return $this->getEntities(self::DEMO_KEY);
	}

	/**
	 * @param string $url
	 * @param string $path
	 *
	 * @throws AppManagerException
	 */
	public function downloadApp($url, $path) {
		$apiKey = $this->getApiKey();
		$this->httpGet($url, ['save_to' => $path], $apiKey);
	}

	/**
	 * @param string $apiKey
	 *
	 * @return \OCP\Http\Client\IResponse
	 *
	 * @throws AppManagerException
	 */
	public function validateKey($apiKey) {
		$url = $this->getAbsoluteUrl('/api/v1/categories.json');
		return $this->httpGet($url, [], $apiKey);
	}

	/**
	 *
	 * Exchange login token for api key
	 *
	 * @param string $loginToken
	 * @param string $codeVerifier
	 * @return string
	 * @throws AppManagerException
	 */
	public function exchangeLoginTokenForApiKey($loginToken, $codeVerifier) {
		$url = $this->getAbsoluteUrl('/api/v1/authorize');
		$result = $this->httpPost($url, [
			'body' => [
				'loginToken' => $loginToken,
				'codeVerifier' => $codeVerifier
			]
		]);

		$body = \json_decode($result->getBody(), true);
		return $body['apiKey'];
	}

	/**
	 * @return void
	 */
	public function invalidateCache() {
		if (!$this->cacheFactory->isAvailable()) {
			return;
		}
		$cache = $this->cacheFactory->create(self::CACHE_KEY);
		$cache->clear();
	}

	/**
	 * @param string $key
	 * @param string $uri
	 *
	 * @return mixed
	 *
	 * @throws AppManagerException
	 */
	public function queryData($key, $uri) {
		// read from cache
		if ($this->cacheFactory->isAvailable()) {
			$cache = $this->cacheFactory->create(self::CACHE_KEY);
			$data = $cache->get($key);
			if ($data !== null) {
				return \json_decode($data, true);
			}
		}

		$this->checkInternetConnection();
		$apiKey = $this->getApiKey();
		$endpointUrl = $this->getAbsoluteUrl($uri);
		$response = $this->httpGet($endpointUrl, [], $apiKey);
		$data = $response->getBody();
		if ($this->cacheFactory->isAvailable()) {
			// cache if for a day - TODO: evaluate the response header
			$cache = $this->cacheFactory->create(self::CACHE_KEY);
			$cache->set($key, $data, 60 * 60 * 24);
		}
		return \json_decode($data, true);
	}

	/**
	 * Checks if this instance can connect to the internet
	 *
	 * @return void
	 *
	 * @throws AppManagerException
	 */
	public function checkInternetConnection() {
		$hasInternetConnection = $this->config->getSystemValue(
			'has_internet_connection',
			true
		);
		if ($hasInternetConnection !== true) {
			throw new AppManagerException(
				$this->l10n->t('The Internet connection is disabled.')
			);
		}
	}

	/**
	 * @return string | null
	 */
	public function getApiKey() {
		$configFileApiKey = $this->config->getSystemValue('marketplace.key', null);
		if ($configFileApiKey) {
			return $configFileApiKey;
		}
		return $this->config->getAppValue('market', 'key', null);
	}
	/**
	 * @param string $path
	 * @param array $options
	 * @param string | null $apiKey
	 *
	 * @return \OCP\Http\Client\IResponse
	 *
	 * @throws AppManagerException
	 */
	private function httpGet($path, $options, $apiKey) {
		if (!empty($apiKey)) {
			$options = \array_merge(
				[
					'headers' => ['Authorization' => "apikey: $apiKey"]
				],
				$options
			);
		}
		$ca = $this->config->getSystemValue('marketplace.ca', null);
		if ($ca !== null) {
			$options = \array_merge(
				[
					'verify' => $ca
				],
				$options
			);
		}
		$client = $this->httpClientService->newClient();
		try {
			$response = $client->get($path, $options);
		} catch (TransferException $e) {
			if ($e instanceof ClientException) {
				if ($e->getCode() === 401) {
					if ($apiKey !== null) {
						throw new AppManagerException(
							$this->l10n->t('Invalid marketplace API key provided')
						);
					}
					throw new AppManagerException(
						$this->l10n->t('Marketplace API key missing')
					);
				}
				if ($e->getCode() === 402) {
					throw new AppManagerException(
						$this->l10n->t('Active subscription on marketplace required')
					);
				}
			}
			throw new AppManagerException(
				$this->l10n->t(
					'No marketplace connection: %s',
					[$e->getMessage()]
				),
				0,
				$e
			);
		}
		return $response;
	}

	/**
	 * @param string $path
	 * @param array $options
	 * @return \OCP\Http\Client\IResponse
	 * @throws AppManagerException
	 */
	private function httpPost($path, $options) {
		$ca = $this->config->getSystemValue('marketplace.ca', null);
		if ($ca !== null) {
			$options = \array_merge(
				[
					'verify' => $ca
				],
				$options
			);
		}
		$client = $this->httpClientService->newClient();

		try {
			$response = $client->post($path, $options);
		} catch (TransferException $e) {
			throw new AppManagerException(
				$this->l10n->t(
					'No marketplace connection: %s',
					[$e->getMessage()]
				),
				0,
				$e
			);
		}

		return $response;
	}

	/**
	 * @param string $code
	 *
	 * @return mixed
	 *
	 * @throws AppManagerException
	 */
	private function getEntities($code) {
		$url = $this->urlConfig[$code];
		if ($code === self::APPS) {
			$platformVersion = $this->versionHelper->getPlatformVersion(3);
			$url = \sprintf($url, $platformVersion);
			$code = \sprintf($code, $platformVersion);
		} elseif ($code == self::DEMO_KEY) {
			$instanceId = $this->config->getSystemValue('instanceid');
			$url = \sprintf($url, $instanceId);
		}
		return $this->queryData($code, $url);
	}

	/**
	 * @param string $relativeUrl
	 * @return string
	 */
	private function getAbsoluteUrl($relativeUrl) {
		$storeUrl = $this->config->getSystemValue('appstoreurl', 'https://marketplace.owncloud.com');
		return \rtrim($storeUrl, '/') . $relativeUrl;
	}
}
