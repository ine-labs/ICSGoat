<?php
/**
 * ownCloud - Ransomware Protection
 *
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 * @copyright 2017 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Ransomware_Protection\AppInfo;

use OCA\Ransomware_Protection\Blacklist;
use OCA\Ransomware_Protection\Blocker;
use OCA\Ransomware_Protection\Controller\SettingsController;
use OCA\Ransomware_Protection\Db\MovelogMapper;
use OCA\Ransomware_Protection\Hooks\RootHooks;
use OCA\Ransomware_Protection\MovelogManager;
use OCA\Ransomware_Protection\Restorer;
use OCA\Ransomware_Protection\Scanner;
use OCP\AppFramework\App;

/**
 * Class Application
 *
 * @package OCA\Ransomware_Protection\AppInfo
 */
class Application extends App {

	/**
	 * Constructor
	 *
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct('ransomware_protection', $urlParams);

		$container = $this->getContainer();
		$server = $container->getServer();

		/**
		 * Services
		 */
		$container->registerService('Scanner', function ($c) use ($server) {
			return new Scanner(
				$server->getConfig(),
				$server->getDatabaseConnection(),
				$server->getAppManager(),
				$server->getUserManager(),
				$server->getRootFolder(),
				$c->query('L10N')
			);
		});
		$container->registerService('Restorer', function ($c) use ($server) {
			return new Restorer(
				$c->query('Scanner'),
				$c->query('MovelogManager'),
				$server->getConfig(),
				$server->getDatabaseConnection(),
				$server->getAppManager(),
				$server->getUserManager(),
				$server->getRootFolder(),
				$c->query('L10N')
			);
		});

		$container->registerService('Blacklist', function ($c) use ($server) {
			return new Blacklist(
				$server->getLogger()
			);
		});
		$container->registerService('Blocker', function ($c) use ($server) {
			return new Blocker(
				$c->query('Request'),
				$server->getConfig(),
				$c->query('L10N'),
				$server->getUserSession()
			);
		});

		/**
		 * Controller
		 */
		$container->registerService('SettingsController', function ($c) use ($server) {
			return new SettingsController(
				$c->query('Request'),
				$server->getConfig(),
				$c->query('L10N'),
				$server->getUserSession(),
				$c->query('Blocker')
			);
		});

		/**
		 * ORM Mapper
		 */
		$container->registerService('MovelogMapper', function ($c) {
			return new MovelogMapper(
				$c->query('ServerContainer')->getDb()
			);
		});
		$container->registerService('MovelogManager', function ($c) {
			return new MovelogManager(
				$c->query('L10N'),
				$c->query('MovelogMapper')
			);
		});

		/**
		 * Hooks
		 */
		$container->registerService('RootHooks', function ($c) {
			return new RootHooks(
				$c->query('ServerContainer')->getRootFolder(),
				$c->query('AppName'),
				$c->query('MovelogManager')
			);
		});

		/**
		 * Core
		 */
		$container->registerService('L10N', function ($c) {
			return $c->query('ServerContainer')->getL10N($c->query('AppName'));
		});
	}

	/**
	 * Setup the storage wrapper callback
	 */
	public function setupStorage() {
		\OC\Files\Filesystem::addStorageWrapper('ransomware_protection', function ($mountPoint, $storage) {
			if ($storage instanceof \OC\Files\Storage\Storage) {
				$blacklist = $this->getContainer()->query('Blacklist');
				$blocker = $this->getContainer()->query('Blocker');
				return new \OCA\Ransomware_Protection\StorageWrapper(
					[
						'storage' => $storage,
						'mountPoint' => $mountPoint,
						'blacklist' => $blacklist,
						'blocker' => $blocker
					]
				);
			} else {
				return $storage;
			}
		}, 1);
	}
}
