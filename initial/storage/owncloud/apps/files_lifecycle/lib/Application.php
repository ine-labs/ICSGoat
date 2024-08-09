<?php
/**
 * ownCloud
 *
 * @author Tom Needham <tom@owncloud.com>
 * @author Michael Barz <mbarz@owncloud.com>
 * @copyright (C) 2018 ownCloud GmbH
 * @license ownCloud Commercial License
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see
 * <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Files_Lifecycle;

use OC\SubAdmin;
use OCA\Files_Lifecycle\Activity\Extension;
use OCA\Files_Lifecycle\Activity\Listener;
use OCA\Files_Lifecycle\Entity\PropertyMapper;
use OCA\Files_Lifecycle\Events\FileArchivedEvent;
use OCA\Files_Lifecycle\Events\FileExpiredEvent;
use OCA\Files_Lifecycle\Events\FileRestoredEvent;
use OCA\Files_Lifecycle\Local\LocalStrategy;
use OCA\Files_Lifecycle\Policy\HardPolicy;
use OCA\Files_Lifecycle\Policy\IPolicy;
use OCA\Files_Lifecycle\Policy\SoftPolicy;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\QueryException;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IUserManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use OCP\Util;

/**
 * Class Application
 *
 * @package OCA\Files_Lifecycle
 */
class Application extends App {
	public const APPID = 'files_lifecycle';

	/**
	 * @var IRootFolder
	 */
	protected $rootFolder;

	/**
	 * @var IRootFolder
	 */
	protected $lazyRoot;

	/**
	 * @var IUserManager
	 */
	protected $userManager;

	/**
	 * @var IDBConnection $db
	 */
	protected $db;

	/**
	 * @var IConfig $config
	 */
	protected $config;

	/**
	 * @var EventDispatcherInterface $eventDispatcher
	 */
	protected $eventDispatcher;

	/**
	 * @var IMimeTypeLoader
	 */
	protected $loader;

	/**
	 * Application constructor.
	 *
	 * @param array $urlParams
	 *
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct(self::APPID, $urlParams);

		$config = $this->getContainer()->getServer()->getConfig();
		$container = $this->getContainer();
		$strategyClass = $config->getAppValue(
			self::APPID,
			'strategyClass',
			LocalStrategy::class
		);
		$this->config = \OC::$server->getConfig();
		// todo try and catch query exceptions

		$this->rootFolder = \OC::$server->getRootFolder();
		$this->lazyRoot = \OC::$server->getLazyRootFolder();
		$this->userManager = \OC::$server->getUserManager();
		$this->db = \OC::$server->getDatabaseConnection();
		$this->eventDispatcher = $container->getServer()->getEventDispatcher();
		$this->loader = \OC::$server->getMimeTypeLoader();

		$policy = $this->config->getAppValue(
			Application::APPID,
			'policy',
			SoftPolicy::POLICY_NAME
		);

		$container->registerService(
			IPolicy::class,
			function (IAppContainer $c) use ($policy) {
				switch ($policy) {
					case HardPolicy::POLICY_NAME:
						return new HardPolicy(
							$c->query(IConfig::class),
							$c->query(IGroupManager::class),
							$c->query(SubAdmin::class),
							$c->query(ILogger::class)
						);
						// break is never reached because of return above.
						//break;
					case SoftPolicy::POLICY_NAME:
						return new SoftPolicy(
							$c->query(IConfig::class),
							$c->query(IGroupManager::class),
							$c->query(ILogger::class)
						);
						// break is never reached because of return above.
						//break;
					default:
						throw new \Exception('Unsupported lifecycle policy configured.');
				}
			}
		);

		$container->registerService(
			UploadPlugin::class,
			function (IAppContainer $c) {
				return new UploadPlugin(
					$this->lazyRoot,
					new PropertyMapper($this->db, $c->query(ILogger::class))
				);
			}
		);

		$container->registerService(
			ArchiveHooks::class,
			function (IAppContainer $c) {
				return new ArchiveHooks($this->lazyRoot);
			}
		);

		$container->registerService(
			UploadInsert::class,
			function (IAppContainer $c) {
				return new UploadInsert(
					$this->db,
					$this->loader
				);
			}
		);

		/**
		 * @var ILifecycleStrategy $strategy
		 */
		$strategy = $this->getContainer()->query($strategyClass);

		$container
			->registerService(
				IArchiver::class,
				function (IAppContainer $c) use ($strategy) {
					return $strategy->getArchiver();
				}
			);

		$container->registerService(
			IRestorer::class,
			function (IAppContainer $c) use ($strategy) {
				return $strategy->getRestorer();
			}
		);

		$container->registerService(
			IExpirer::class,
			function (IAppContainer $c) use ($strategy) {
				return $strategy->getExpirer();
			}
		);

		// Register the JS provider to inform the UI of config
		\OC_Hook::connect(
			'\OCP\Config',
			'js',
			self::class,
			'registerJsConfig'
		);
	}

	/**
	 * Provides config to the UI
	 *
	 * @param array $data The data to provide to the ui
	 *
	 * @throws QueryException
	 *
	 * @return void
	 */
	public static function registerJsConfig($data) {
		if (!\OC::$server->getUserSession()->isLoggedIn()) {
			return;
		}
		/**
		 * @var IPolicy $policy
		 */
		$policy = \OC::$server->query(IPolicy::class);
		$userAllowedToRestore = $policy->userCanRestore(
			\OC::$server->getUserSession()->getUser()
		);
		$impersonatorAllowedToRestore = $policy->impersonatorCanRestore();
		$data['array']['oc_files_lifecycle'] = \json_encode(
			[
				'userAllowedToRestore' => $userAllowedToRestore,
				'impersonatorAllowedToRestore' => $impersonatorAllowedToRestore,
				'impersonated' => self::isBeingImpersonated()
			]
		);
	}

	/**
	 * @return bool
	 */
	protected static function isBeingImpersonated() {
		return \OC::$server->getSession()->get('impersonator') !== null;
	}

	/**
	 * Register event hooks for upload time
	 *
	 * @return void
	 */
	public function registerFileHooks() {
		$container = $this->getContainer();
		$eventDispatcher = $container->getServer()
			->getEventDispatcher();
		$eventDispatcher->addListener(
			'file.aftercreate',
			function ($event) use ($container) {
				$plugin = $container->query(UploadPlugin::class);
				$plugin->setUploadTime($event);
			}
		);
	}

	/**
	 * Register User Hooks for Archive Storage
	 *
	 * @return void
	 */
	public function registerUserHooks() {
		$container = $this->getContainer();
		$eventDispatcher = $container->getServer()
			->getEventDispatcher();
		$eventDispatcher->addListener(
			'user.afterdelete',
			function ($event) use ($container) {
				$plugin = $container->query(ArchiveHooks::class);
				$plugin->deleteUser($event);
			}
		);
	}

	/**
	 * Register Archive Mount Provider
	 *
	 * @return void
	 */
	public function registerMountProviders() {
		/**
		 * @var \OCP\IServerContainer $server
		 */
		$server = $this->getContainer()->query('ServerContainer');
		$config = $server->getConfig();
		$mountProviderCollection = $server->getMountProviderCollection();
		$mountProviderCollection
			->registerProvider(new ArchiveMountProvider($config));
	}

	/**
	 * Registers an extension with the activity manager
	 *
	 * @return void
	 */
	public function registerActivityExtension() {
		$manager = $this->getContainer()->getServer()->getActivityManager();
		$eventDispatcher = $this->getContainer()->getServer()->getEventDispatcher();
		$extension = $this->getContainer()->query(Extension::class);
		$manager->registerExtension(
			function () use ($extension) {
				return $extension;
			}
		);
		$listener = $this->getContainer()->query(Listener::class);
		// File was archived
		$archivedListener = function (FileArchivedEvent $event) use ($listener) {
			$listener->fileArchived($event);
		};
		$eventDispatcher->addListener(
			FileArchivedEvent::EVENT_NAME,
			$archivedListener
		);
		// File was restored
		$restoredListener = function (FileRestoredEvent $event) use ($listener) {
			$listener->fileRestored($event);
		};
		$eventDispatcher->addListener(
			FileRestoredEvent::EVENT_NAME,
			$restoredListener
		);
		// File was expired
		$expiredListener = function (FileExpiredEvent $event) use ($listener) {
			$listener->fileExpired($event);
		};
		$eventDispatcher->addListener(
			FileExpiredEvent::EVENT_NAME,
			$expiredListener
		);
	}

	/**
	 * The config array for the files nav extension
	 *
	 * @return array
	 */
	protected function getFilesPluginExtension() {
		$l = $this->getContainer()->getServer()->getL10N(self::APPID);
		return [
			'id' => 'archive',
			'appname' => self::APPID,
			'script' => 'archivelist.php',
			'order' => 49,
			'name' => $l->t('Archived Files'),
			'icon' => 'archive'
		];
	}

	/**
	 * Register js files with the core template
	 *
	 * @return void
	 */
	protected function registerFilesJsExtensions() {
		Util::addScript(Application::APPID, 'app');
		Util::addScript(Application::APPID, 'filesplugin');
		Util::addScript(Application::APPID, 'filelist');
		Util::addScript(Application::APPID, 'infoview');
		Util::addStyle(Application::APPID, 'style');
	}

	/**
	 * Register plugins with the Files app
	 *
	 * @return void
	 */
	public function registerFilesUIPlugins() {
		if ($this->config->getAppValue(
			Application::APPID,
			'disable_ui',
			false
		)
		) {
			return;
		}

		// Add the navigation extension
		\OCA\Files\App::getNavigationManager()
			->add($this->getFilesPluginExtension());
		$this->getContainer()
			->getServer()
			->getEventDispatcher()
			->addListener(
				'OCA\Files::loadAdditionalScripts',
				function () {
					$this->registerFilesJsExtensions();
				}
			);
	}
}
