<?php
/**
 * ownCloud
 *
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Files_LDAP_Home;

use OC\Files\Filesystem;
use OCP\IConfig;
use OCP\Util;

class Storage extends \OC\Files\Storage\Local {
	const HOME_FOLDER_NAME = 'Home';

	//holds the connection to the LDAP user backend
	protected static $ldap = null;

	//holds the ID of the storage
	private $id = null;

	/**
	 * Constructor
	 *
	 * @param array $arguments holding 'user' key with the user ID, 'home'
	 * key with user path (see self::setup method), 'config' containing an
	 * instance of IConfig
	 * @throws \Exception when the user is not an LDAP user
	 */
	public function __construct($arguments) {
		if (!isset($arguments['config']) || !($arguments['config'] instanceof IConfig)) {
			throw new \ErrorException('No IConfig instance provided');
		}
		$this->initiateLDAPAccess($arguments['config']);
		if (!$this->isLdapUser($arguments['config'], $arguments['user'])) {
			throw new \ErrorException('No LDAP User, no Home – not an Error ');
		}
		$home = self::$ldap->getUserSystemHome($arguments['user']);
		if (!$home) {
			throw new \ErrorException(
				'Cannot determine Home from LDAP for user '.$arguments['user']);
		}
		parent::__construct([
			'datadir' => $home
		]);
		$this->id = 'ldaphome::'.$arguments['user'];
	}

	/**
	 * Checks whether the mounted storage is read and writable. If not, a
	 * warning is logged.
	 */
	public function runDiagnostics() {
		$logger = \OC::$server->getLogger();

		$isReadable = $this->isReadable('/');
		if (!$isReadable) {
			$logger->warning('Home directory {datadir} is not readable', ['datadir' => $this->datadir, 'app' => 'files_ldap_home']);
			return;
		}
		$isWritable = $this->isUpdatable('/');
		if (!$isWritable) {
			$logger->warning('Home directory {datadir} is not writable', ['datadir' => $this->datadir, 'app' => 'files_ldap_home']);
		}
	}

	/**
	 * Get the identifier for the storage,
	 * the returned id should be the same for every storage object that is
	 * created with the same parameters and two storage objects with the same id
	 * should refer to two storages that display the same files.
	 *
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * initiates the connection to a LDAP user backend
	 *
	 * @param IConfig $ocConfig
	 * @throws \ErrorException
	 */
	private static function initiateLDAPAccess(IConfig $ocConfig) {
		if (self::$ldap !== null) {
			return;
		}
		$helper = new \OCA\User_LDAP\Helper();
		$configPrefixes = $helper->getServerConfigurationPrefixes(true);
		if (\count($configPrefixes) >= 1) {
			self::$ldap  = new LDAPReader($configPrefixes, $ocConfig);
		} else {
			throw new \ErrorException('No LDAP Server configured');
		}
	}

	/**
	 * Checks whether the given user is an LDAP user
	 *
	 * @param IConfig $config
	 * @param string $user optional, the internal username
	 * @return bool
	 * @throws \ErrorException
	 */
	public static function isLdapUser(IConfig $config, $user = null) {
		if ($user === null) {
			$user = \OCP\User::getUser();
		}

		if (self::$ldap === null) {
			self::initiateLDAPAccess($config);
		}

		if (!self::$ldap->userExists($user)) {
			return false;
		}

		return true;
	}

	/**
	 * Mounts the Home if applicable
	 * This method is triggered by OC_Filesystem::post_initMountPoints hook
	 *
	 * @param array $options offering username and user dir path.
	 */
	public static function setup($options) {
		try {
			//Check whether app requirements are fulfilled and setup shall be
			//done for an LDAP user and if so whether a homedir is set in LDAP
			Helper::checkOperability();
			//The class overwriting is required for unit tests
			$class = isset($options['class']) ? $options['class'] : __CLASS__;
			$parameters = [
				'user'   => $options['user'],
				'config' => isset($options['config']) ?
					$options['config'] : \OC::$server->getConfig(),
			];

			/** @var Storage $storage */
			$storage = new $class($parameters);
		} catch (\ErrorException $e) {
			return;
		}

		Util::writeLog(
			'files_ldap_home', 'Mount Home for '.$options['user'],
			Util::DEBUG);
		//Convinced! Mount the Home for this user

		$settings = new Settings($parameters['config']);
		$mountName = $settings->getMountName();

		//Note: we can use $user/files/ here, because the real home is already
		//mounted thereto after initMountPoints hook
		Filesystem::mount(
			$storage,
			[
				'user' => $options['user'],
			],
			$options['user'].'/files/'.$mountName.'/'
		);
		$storage->runDiagnostics();
	}
}
