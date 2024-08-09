<?php
/**
 * ownCloud Workflow
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Ilja Neumann <ineumann@owncloud.com>
 * @copyright 2018 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Workflow\AppInfo;

use OCA\Workflow\Engine\Wrapper\StorageWrapper;
use OCA\Workflow\PublicAPI\Event\CollectTypesInterface;
use OCA\Workflow\PublicAPI\Event\FileActionInterface;
use OCA\Workflow\PublicAPI\Event\ValidateFlowInterface;
use OCP\Files\Storage\IStorage;

class Application extends \OCP\AppFramework\App {

	/**
	 * Application constructor.
	 */
	public function __construct() {
		parent::__construct('workflow');
	}

	/**
	 * Register the app to several events
	 */
	public function registerListeners() {
		$dispatcher = $this->getContainer()->getServer()->getEventDispatcher();

		$dispatcher->addListener('OCA\Workflow\Engine::' . ValidateFlowInterface::FLOW_VALIDATE, [$this, 'validateFlowListener']);
		$dispatcher->addListener('OCA\Workflow\Engine::' . CollectTypesInterface::TYPES_COLLECT, [$this, 'collectTypesListener']);

		\OCP\Util::connectHook('OC_Filesystem', 'preSetup', $this, 'registerStorageWrapper');
		\OCP\Util::connectHook('OC_Filesystem', 'setup', $this, 'subscribeToStoreTypeSpecificFileEvents');
		\OCP\Util::connectHook('OC_Filesystem', 'post_create', $this, 'fileActionHookListener');
	}

	public function subscribeToStoreTypeSpecificFileEvents(array $params) {
		$container = $this->getContainer();
		$server = $container->getServer();

		$storage = $server->getUserFolder($params['user'])->getStorage();
		$dispatcher = $server->getEventDispatcher();

		// Different event is fired for object store
		if ($storage->instanceOfStorage('\OC\Files\ObjectStore\HomeObjectStoreStorage')) {
			$dispatcher->addListener('OCA\Workflow\Engine::' . FileActionInterface::FILE_CREATE, [$this, 'fileActionListener']);
			return;
		};

		$dispatcher->addListener('OCA\Workflow\Engine::' . FileActionInterface::CACHE_INSERT, [$this, 'fileActionListener']);
	}

	/**
	 * Register the storage wrapper
	 */
	public function registerStorageWrapper() {
		$app = $this;
		\OC\Files\Filesystem::addStorageWrapper('workflow', function ($mountPoint, \OCP\Files\Storage\IStorage $storage) use ($app) { /** @phpstan-ignore-line */
			if ($storage->instanceOfStorage('OC\Files\Storage\Shared')) {
				return $storage;
			}

			return new \OCA\Workflow\Engine\Wrapper\StorageWrapper([
				'storage' => $storage,
				'mountPoint' => $mountPoint,
				'plugin' => $this->getContainer()->query('OCA\Workflow\Engine\Plugin'),
			]);
		}, 0); // After firewall (-1), before trashbin (1) and encryption (2)
	}

	/**
	 * Wrapper for type hinting
	 *
	 * @return \OCA\Workflow\AutoTagging\TaggingPlugin
	 */
	protected function getPlugin() {
		return $this->getContainer()->query('OCA\Workflow\AutoTagging\TaggingPlugin');
	}

	/**
	 * Listen to file action events
	 *
	 * @param FileActionInterface $event
	 */
	public function fileActionListener(FileActionInterface $event) {
		$plugin = $this->getPlugin();
		$plugin->fileAction($event);
	}

	public function fileActionHookListener($data) {
		$view = \OC\Files\Filesystem::getView();
		$absolutePath = $data['path'];

		/** @var StorageWrapper $storage */
		list($storage, $internalPath) = $view->resolvePath($absolutePath);
		if ($storage->instanceOfStorage(StorageWrapper::class)) {
			$ids = $storage->getParentIds($internalPath);
			if ($ids['fileId'] !== null) {
				$plugin = $this->getContainer()->query(\OCA\Workflow\Engine\Plugin::class);
				$plugin->trigger(FileActionInterface::FILE_CREATE, $absolutePath, $ids['fileId'], $ids['parentIds']);
			}
		}
	}

	/**
	 * Listen to flow validation events
	 *
	 * @param ValidateFlowInterface $event
	 */
	public function validateFlowListener(ValidateFlowInterface $event) {
		$plugin = $this->getPlugin();
		$plugin->validateFlow($event);
	}

	/**
	 * Register the type to the workflow engine
	 *
	 * @param CollectTypesInterface $event
	 */
	public function collectTypesListener(CollectTypesInterface $event) {
		$plugin = $this->getPlugin();
		$plugin->collectTypes($event);
	}
}
