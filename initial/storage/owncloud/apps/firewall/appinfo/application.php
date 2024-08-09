<?php
/**
 * ownCloud Firewall
 *
 * @author Clark Tomlinson <clark@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright Copyright (c) 2018, ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Firewall\AppInfo;

require_once __DIR__ . '/../vendor/lib/autoload.php';

use OC\Files\Filesystem;
use OCA\Firewall\Config;
use OCA\Firewall\Context;
use OCA\Firewall\Firewall;
use OCA\Firewall\Logger;
use OCA\Firewall\RuleFactory;
use OCA\Firewall\Ruler;
use OCA\Firewall\StorageWrapper;
use OCP\App as PApp;
use OCP\AppFramework\App;
use OCP\Files\Storage;
use OCP\IContainer;
use OCP\ILogger;
use OCP\IRequest;

class Application extends App {

	/**
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct('firewall', $urlParams);
		$this->registerServices();
	}

	/**
	 * Register any services into the DI Container
	 */
	private function registerServices() {
		$this->getContainer()->registerService('OCA\Firewall\Config', function (IContainer $c) {
			/** @var \OCP\IServerContainer $server */
			$server = $c->query('ServerContainer');

			return new Config(
				$server->getConfig()
			);
		});

		$this->getContainer()->registerService('OCA\Firewall\Context', function (IContainer $c) {
			/** @var \OCP\IServerContainer $server */
			$server = $c->query('ServerContainer');

			return new Context(
				$server->getRequest(),
				$server->getGroupManager(),
				$server->getUserSession(),
				$server->getMimeTypeDetector(),
				$c->query('OCA\Firewall\Config'),
				\OC::$server->getRootFolder()
			);
		});

		$this->getContainer()->registerService('OCA\Firewall\RuleFactory', function (IContainer $c) {
			return new RuleFactory($c);
		});

		$this->getContainer()->registerService('OCA\Firewall\Ruler', function () {
			return new Ruler();
		});

		$this->getContainer()->registerService('OCA\Firewall\Firewall', function (IContainer $c) {
			/** @var \OCP\IServerContainer $server */
			$server = $c->query('ServerContainer');

			return new Firewall(
				$c->query('OCA\Firewall\RuleFactory'),
				$c->query('OCA\Firewall\Context'),
				$c->query('OCA\Firewall\Logger'),
				$server->getL10NFactory()
			);
		});

		$this->getContainer()->registerService('OCA\Firewall\Logger', function (IContainer $c) {
			return new Logger(
				$c->query('ServerContainer')->getLogger(),
				$c->query('OCA\Firewall\Config')->getDebugLevel()
			);
		});
	}

	/**
	 * Boot the firewall application
	 */
	public function boot() {
		$container = $this->getContainer();
		$licenseManager = $container->query('ServerContainer')->getLicenseManager();
		if ($licenseManager->checkLicenseFor('firewall')) {
			$rules = $container->query(Config::class)->getRules(true);

			// Check the request against rules if we have some
			if (!empty($rules)) {
				\OCP\Util::connectHook('OC_Filesystem', 'preSetup', $this, 'wrapStorage');
			}
		}
	}

	/**
	 * Register the storage wrapper
	 */
	public function wrapStorage() {
		Filesystem::addStorageWrapper('firewall', [$this, 'wrapper'], -1); // Before trashbin (1) and encryption (2)
	}

	/**
	 * Do not wrap the storages for:
	 * - Shares, we only look at the owner because of system tags on parents
	 * - CLI, so workflow and other tools can delete files there as needed
	 *
	 * @param mixed $mountPoint
	 * @param Storage $storage
	 * @return StorageWrapper|Storage
	 */
	public function wrapper($mountPoint, Storage $storage) {
		if (!$storage->instanceOfStorage('OC\Files\Storage\Shared') && !\OC::$CLI) {
			$rules = $this->getContainer()->query('OCA\Firewall\Config')->getRules(true);
			/** @var \OCA\Firewall\Firewall $firewall */
			$firewall = $this->getContainer()->query('OCA\Firewall\Firewall');
			$firewall->setRules($rules);

			return new StorageWrapper([
				'storage' => $storage,
				'mountPoint' => $mountPoint,
				'firewall' => $firewall,
			]);
		}

		return $storage;
	}
}
