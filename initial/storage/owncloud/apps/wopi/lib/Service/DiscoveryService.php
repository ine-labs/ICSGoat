<?php
/**
 * ownCloud Wopi
 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @copyright 2018 ownCloud GmbH.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\WOPI\Service;

use OC\HintException;
use OCP\Files\IMimeTypeDetector;
use OCP\Http\Client\IClientService;
use OCP\ICacheFactory;
use OCP\IConfig;

class DiscoveryService {

	/** @var IConfig */
	private $config;
	/** @var ICacheFactory */
	private $cacheFactory;
	/** @var IClientService */
	private $clientService;
	/** @var IMimeTypeDetector */
	private $mimeTypeDetector;

	/**
	 * WopiController constructor.
	 *
	 * @param IConfig $config
	 * @param ICacheFactory $cacheFactory
	 * @param IClientService $clientService
	 * @param IMimeTypeDetector $mimeTypeDetector
	 */
	public function __construct(
		IConfig $config,
		ICacheFactory $cacheFactory,
		IClientService $clientService,
		IMimeTypeDetector $mimeTypeDetector
	) {
		$this->config = $config;
		$this->cacheFactory = $cacheFactory;
		$this->clientService = $clientService;
		$this->mimeTypeDetector = $mimeTypeDetector;
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	public function getDiscoveryData(): array {
		// read from cache
		if (!$this->isDebug() && $this->cacheFactory->isAvailable()) {
			$cache = $this->cacheFactory->create('wopi');
			$data = $cache->get('discovery');
			if ($data !== null) {
				return \json_decode($data, true);
			}
		}

		$officeOnlineUrl = $this->getOfficeOnlineUrl();

		// load xml
		$wopiDiscovery = "$officeOnlineUrl/hosting/discovery";
		$wopiClient = $this->clientService->newClient();
		$discovery = $wopiClient->get($wopiDiscovery)->getBody();

		// parse xml
		$loadEntities = \libxml_disable_entity_loader(true);
		$parsedDiscovery = \simplexml_load_string($discovery);
		\libxml_disable_entity_loader($loadEntities);

		$config = [
			'view' => [],
			'edit' => [],
			'editnew' => [],
			'favicons' => []
		];
		$supportedActions = \array_keys($config);
		$supportedApps = ['Word', 'Excel', 'PowerPoint'];
		if ($this->isDebug()) {
			$supportedApps[] = 'WopiTest';
		}
		foreach ($supportedApps as $app) {
			$result = $parsedDiscovery->xpath("/wopi-discovery/net-zone/app[@name='$app']/action");
			$favicon = (string)$parsedDiscovery->xpath("/wopi-discovery/net-zone/app[@name='$app']/@favIconUrl")[0];
			foreach ($result as $action) {
				$name = (string)$action['name'];
				if (\in_array($name, $supportedActions, true)) {
					$ext = (string)$action['ext'];
					$mime = $this->mimeTypeDetector->detectPath("test.$ext");
					if ($mime !== 'application/octet-stream' || $app === 'WopiTest') {
						$config['favicons'][$mime] = $favicon;
						$config[$name][$mime] = $config[$name][$mime] ?? [];
						$config[$name][$mime][$ext] = (string)$action['urlsrc'];
						$config[$name][$mime]['App'] = $app;
					}
				}
			}
		}

		if ($this->cacheFactory->isAvailable()) {
			// cache if for a day
			$cache = $this->cacheFactory->create('wopi');
			$cache->set($discovery, \json_encode($config), 60*60*24);
		}

		return $config;
	}

	/**
	 * @return bool
	 */
	private function isDebug(): bool {
		return $this->config->getSystemValue('debug', false) === true;
	}

	/**
	 * @return string
	 * @throws HintException
	 */
	public function getOfficeOnlineUrl() {
		$officeOnlineUrl = $this->config->getSystemValue('wopi.office-online.server', null);
		if ($officeOnlineUrl === null) {
			throw new HintException('System configuration <wopi.office-online.server> is missing');
		}
		return $officeOnlineUrl;
	}
}
