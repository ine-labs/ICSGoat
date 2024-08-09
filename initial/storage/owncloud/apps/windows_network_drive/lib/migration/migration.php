<?php
/**
 * @author Jesus Macias Portela <jmacias@solidgear.es>
 *
 * @copyright (C) 2017 ownCloud, Inc.
 * @license OCL
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

namespace OCA\windows_network_drive\lib\migration;

use OCA\windows_network_drive\lib\DBAccess;
use OCA\windows_network_drive\lib\Utils;
use OCA\windows_network_drive\lib\Hooks;
use OCP\Files\External\Backend\Backend;
use OCP\IAppConfig;
use OCP\Files\External\IStorageConfig;
use OCP\Files\External\IStoragesBackendService;
use OCP\IUserManager;
use OC\Files\External\Service\DBConfigService;
use OC\Files\External\Service\UserStoragesService;
use OC\Files\External\StorageConfig;

class Migration {

	/**
	 * @var IUserManager
	 */
	private $userManager;

	/** @var IStoragesBackendService */
	private $backendService;

	/** @var DBConfigService */
	private $dbConfig;

	/** @var IAppConfig */
	private $appConfig;

	/**
	 * Migrate WND config to Files_external + WND backend
	 */
	public function __construct() {
		// Enable file externals because is a dependency
		\OC_App::loadApp('files_external');
		$this->backendService = \OC::$server->getStoragesBackendService();
		$this->userManager = \OC::$server->getUserManager();
		$this->connection = \OC::$server->getDatabaseConnection();
		$this->appConfig = \OC::$server->getAppConfig();
		$this->dbConfig = \OC::$server->query('StoragesDBConfigService');
	}

	/**
	 * Migrate admin configured storages
	 */
	public function start() {
		$this->log('Starting Windows network drive migration', \OCP\Util::DEBUG);

		//Register WND Backend
		Hooks::loadWNDBackend();

		//1º Read global mount points
		//2º Create global mount points on files_external
		$mountsCount = $this->migrateGlobalMounts();

		//3º Foreach user read personal mountpoint
		//4º Foreach user migrate their global credentials
		//5º Foreach personal mount point create personal on files_external
		$mountsCount += $this->migratePersonalMounts();

		//6º Update flag to allow/disallow personal WND mounts in files_external
		$this->updatePersonalMountFlag();

		//7º Delete old DB tables -- TODO
		$this->cleanOldSetup();

		if ($mountsCount > 0) {
			// enable core's external storage subsystem to make the mounts visible
			$this->appConfig->setValue('core', 'enable_external_storage', 'yes');
		}
	}

	/*

	Mount point created by the admin
	================================
		WND 1 - User credentials. In this case the admin create the mount point. When the user access to OC will see a red row on the mount point.
		The user should click on it and will see a dialog to enter their credentials. If credentials are right, the row will be enabled and the mount point
		will be accessible.

		Array
		(
			[mount_id] => 3
			[mount_point] => WND1
			[mount_url] => 10.10.10.106
			[share] => shared
			[root] => \/
			[use_custom_credentials] => 1
			[global_username] =>
			[global_password] =>
			[applicable_id] => 4
			[applicable_mount_id_fk] => 3
			[mount_type] => global
			[mount_type_name] =>
			[applicables] => Array
				(
					[0] => global
				)
		)

		WND 2 - Login Credentials + Domain. In this case the admin creates the mount point, and also can fill a domain name, because in some cases is necessary
		use it plus the username captured on the login. User credentials are stored in the session so occ comand doesn't work.

		Array
		(
			[mount_id] => 4
			[mount_point] => WND2
			[mount_url] => 10.10.10.106
			[share] => shared
			[root] => \/
			[use_custom_credentials] => 3
			[global_username] =>
			[global_password] =>
			[applicable_id] => 5
			[applicable_mount_id_fk] => 4
			[mount_type] => global
			[mount_type_name] =>
			[applicables] => Array
				(
					[0] => global
				)
		)

		WND 3 - Custom credentials. In this case the admin uses custom credentials to mount the external storage. All users will see the same mount point
		and permissions will be the same for all.

		Array
		(
			[mount_id] => 5
			[mount_point] => WND3
			[mount_url] => 10.10.10.106
			[share] => shared
			[root] => \/
			[use_custom_credentials] => 0
			[global_username] => administrador
			[global_password] => NGR3Y3hvNHR2bTFxYXF0YkFfpnIappCgwRPqHUl7us4=
			[applicable_id] => 6
			[applicable_mount_id_fk] => 5
			[mount_type] => global
			[mount_type_name] =>
			[applicables] => Array
				(
					[0] => global
				)
		)

		WND 4 - Global admin credentials. The same as custom credentials but with global admin credentials. So you can have several mount points with global credentials
		and you can update the credentials from a single point. This example has applicables !!

		Array
		(
			[mount_id] => 6
			[mount_point] => WND4
			[mount_url] => 10.10.10.106
			[share] => shared
			[root] => \/
			[use_custom_credentials] => 2
			[global_username] =>
			[global_password] =>
			[applicable_id] => 7
			[applicable_mount_id_fk] => 6
			[mount_type] => global
			[mount_type_name] =>


	 */

	/**
	 * Migrate global mount points
	 *
	 * @return int number of mounts found
	 */
	private function migrateGlobalMounts() {
		$credentials = DBAccess::getAdminGlobalCredentialsCached();
		$user = $credentials['global_username'];
		$pass = Utils::decryptPassword($credentials['global_password']);
		if (!empty($user) && $pass === false) {
			$this->log("Could not decrypt global password for:" . $user, \OCP\Util::ERROR);
		}

		$oldmounts = $this->getGlobalMounts();
		foreach ($oldmounts as $mount) {
			$this->saveGlobalMount($mount, $user, $pass);
		}

		return \count($oldmounts);
	}

	/**
	 * get WND global mount points
	 *
	 * @return array
	 */
	private function getGlobalMounts() {
		return DBAccess::getAllMountsWithApplicablesParsed();
	}

	/**
	 * save global mount point on files_external
	 *
	 * @param array $mount WND mount point
	 * @param string $user Admin global credential user
	 * @param string $password Admin global credential password
	 */
	private function saveGlobalMount($mount, $user, $password) {
		$g = \OC::$server->getGlobalStoragesService();
		$config = $this->translateConfig($mount, $user, $password, "global");

		$isSharingEnabled = $this->appConfig->getValue('windows_network_drive', 'global_sharing', '0');
		$storageConfig = $this->parseData($config);
		$storageConfig->setMountOption('enable_sharing', $isSharingEnabled ? 'true' : 'false');
		$storage = $g->addStorage($storageConfig);
		// Check if mount has Id
		if ($storage->getId() !== null) {
			$this->log("GlobalMount ".$mount['mount_point']." was migrated", \OCP\Util::INFO);
		} else {
			$this->log("GlobalMount ".$mount['mount_point']." was NOT migrated", \OCP\Util::WARN);
		}
	}

	/**
	 * Translate WND config to files_external config
	 *
	 * @param array $mount WND mount point
	 * @param string $user Admin global credential user
	 * @param string $password Admin global credential password
	 * @return array
	 */
	private function translateConfig($mount, $user, $password, $type) {
		$config = [];
		$config['mount_point'] = '/'.$mount['mount_point'];
		$config['storage'] = '\OCA\windows_network_drive\lib\WND';
		$config['authentication_type'] = $this->mapAuthType($mount, $type);
		$config['configuration'] = [];
		$config['configuration']['host'] = $mount['mount_url'];
		$config['configuration']['root'] = $mount['root'];
		$config['configuration']['share'] = $mount['share'];

		if (isset($mount['global_username'])) {
			$config['configuration']['user'] = $mount['global_username'];
		} elseif (isset($mount['creds_username'])) {
			$config['configuration']['user'] = $mount['creds_username'];
		}

		if (isset($mount['global_password'])) {
			$config['configuration']['password'] = Utils::decryptPassword($mount['global_password']);
		} elseif (isset($mount['creds_password'])) {
			$config['configuration']['password'] = Utils::decryptPassword($mount['creds_password']);
		}

		// Workarround to migrate mount points with global credentials to custom credentials password:password
		if ($config['authentication_type'] === 'password::globalcredentials') {
			$config['configuration']['user'] = $user;
			$config['configuration']['password'] = $password;
			$config['authentication_type'] =  'password::password';
		}

		// In WND domain option was only available with login credentials, and was stored in DB colunm "global_username" (weird)
		if ($config['authentication_type'] === 'password::logincredentials') {
			if (isset($mount['global_username'])) {
				$config['configuration']['domain'] = $mount['global_username'];
			}
		}

		if (isset($mount['domain'])) {
			$config['configuration']['domain'] = "";
		}

		$config['options'] = [];

		if (isset($mount['applicables'])) {
			$applicables = $this->getApplicables($mount['applicables']);

			if (!empty($applicables['users'])) {
				$config['applicable_users'] = $applicables['users'];
			}
			if (!empty($applicables['groups'])) {
				$config['applicable_groups'] = $applicables['groups'];
			}
		}

		return $config;
	}

	/**
	 * Translate WND authentication type files_external authentication type
	 *
	 * @param array $mount WND mount point
	 * @return string
	 */
	private function mapAuthType($mount, $type) {
		if ($type === "global") {
			switch ((int)$mount['use_custom_credentials']) {
				case 0:
					//Custom credentials
					return "password::password";
				case 1:
					// User credentials
					return "password::userprovided";
				case 2:
					// Global admin credentials
					return "password::globalcredentials";
				case 3:
					// Login credentials + domain
					return "password::sessioncredentials";
			}
		} elseif ($type === "personal") {
			switch ((int)$mount['use_global_credentials']) {
				case 0:
					//Custom credentials
					return "password::password";
				case 2:
					// User Global credentials
					// TODO: Review
					return "password::globalcredentials";
			}
		}
	}

	/**
	 * translate WND mount point applicables to files_external applicables
	 *
	 * @param array $applicables
	 * @return array
	 */
	private function getApplicables($applicables) {
		$users = [];
		$groups = [];

		foreach ($applicables as $index => $applicable) {
			if (\strpos($applicable, '(user)') !== false) {
				$users[] = \str_replace('(user)', '', $applicable);
			} elseif (\strpos($applicable, '(group)') !== false) {
				$groups[] = \str_replace('(group)', '', $applicable);
			}
		}

		return ["users" => $users, "groups" => $groups];
	}

	/*

	Mount point created by an user
	================================

	PWND1 - Use personal global credentials. In this case the user creates a mount point using their own global credentials.

	Array
	(
		[personal_mount_id] => 1
		[mount_point] => PWND1
		[mount_url] => 10.10.10.106
		[share] => shared
		[root] => \/
		[target_user] => administrador
		[use_global_credentials] => 2
		[creds_username] =>
		[creds_password] =>
	)

	PWND2 - Use custom credentials. In this case the user creates a mount point using custom credentials.

	Array
	(
		[personal_mount_id] => 2
		[mount_point] => PWND2
		[mount_url] => 10.10.10.106
		[share] => shared
		[root] => \/
		[target_user] => administrador
		[use_global_credentials] => 0
		[creds_username] => administrador
		[creds_password] => dHJiZGNvejh5MmdoZWU5MgZLSK2zPGRSBl5iTJ9ITjI=
	)

	 */

	/**
	 * Migrate personal mount points
	 *
	 * @return int number of found personal mounts
	 */
	private function migratePersonalMounts() {
		// use chunks to avoid caching too many users in memory
		$count = 0;
		$limit = 100;
		$offset = 0;
		do {
			$users = $this->userManager->search('', $limit, $offset);
			foreach ($users as $user) {
				//Get user global credentials
				$credentials = DBAccess::getPersonalGlobalCredentialsCached($user->getUID());
				$userName = $credentials['global_username'];
				$pass = Utils::decryptPassword($credentials['global_password']);
				$this->log("User Credentials: " . $user->getDisplayName(), \OCP\Util::DEBUG);
				if (!empty($userName) && $pass === false) {
					$this->log("Could not decrypt global password for:" . $user->getDisplayName(), \OCP\Util::WARN);
				}
				$personalMounts = $this->getPersonalMounts($user->getUID());
				foreach ($personalMounts as $mount) {
					$dummySession = new DummyUserSession();
					$dummySession->setUser($user);
					$this->savePersonalMount($mount, $userName, $pass, $dummySession);
				}
			}
			$offset += $limit;
			$count += \count($users);
		} while (\count($users) >= $limit);

		return $count;
	}

	/**
	 * get personal mount points
	 *
	 * @param string $user
	 * @return array
	 */
	private function getPersonalMounts($user) {
		$mounts = [];
		$cursor = DBAccess::getPersonalMountsPerUser($user);
		if ($cursor !== false) {
			while (($row = $cursor->fetchRow()) !== false) {
				$mounts[] = $row;
			}
		}
		return $mounts;
	}

	/**
	 * save global mount point on files_external
	 *
	 * @param array $mount WND mount point
	 * @param string $user User global credential user
	 * @param string $password User global credential password
	 * @param IUserSession $session User Dummy session
	 */
	private function savePersonalMount($mount, $user, $password, $session) {
		$u = new UserStoragesService($this->backendService, $this->dbConfig, $session, \OC::$server->query('UserMountCache'));
		$config = $this->translateConfig($mount, $user, $password, "personal");
		$storageConfig = $this->parseData($config);
		$storage = $u->addStorage($storageConfig);
		// Check if mount has Id
		if ($storage->getId() !== null) {
			$this->log("Personal mount ".$mount['mount_point']." for user ".$session->getUser()->getUID()." was migrated", \OCP\Util::INFO);
		} else {
			$this->log("Personal mount ".$mount['mount_point']." for user ".$session->getUser()->getUID()." was NOT migrated", \OCP\Util::WARN);
		}
	}

	/**
	 * create config in files_external format
	 *
	 * @param array $data
	 * @return IStorageConfig
	 */
	private function parseData(array $data) {
		$mount = new StorageConfig();
		$mount->setMountPoint($data['mount_point']);
		$mount->setBackend($this->getBackendByClass($data['storage']));
		$authBackend = $this->backendService->getAuthMechanism($data['authentication_type']);
		$mount->setAuthMechanism($authBackend);
		$mount->setBackendOptions($data['configuration']);
		$mount->setMountOptions($data['options']);
		$mount->setApplicableUsers(isset($data['applicable_users']) ? $data['applicable_users'] : []);
		$mount->setApplicableGroups(isset($data['applicable_groups']) ? $data['applicable_groups'] : []);
		return $mount;
	}

	/**
	 * get files_external backend
	 *
	 * @param string $className
	 * @return Backend
	 */
	private function getBackendByClass($className) {
		$backends = $this->backendService->getBackends();
		foreach ($backends as $backend) {
			if (\strpos($backend->getStorageClass(), "WND") !== false) {
				return $backend;
			}
		}
	}

	private function updatePersonalMountFlag() {
		$isPersonalMountAllowed = $this->appConfig->getValue('windows_network_drive', 'personal_mounts', '0');
		if ($isPersonalMountAllowed) {
			$allowedMounts = $this->appConfig->getValue('files_external', 'user_mounting_backends', '');
			if (!empty($allowedMounts)) {
				$allowedMounts .= ',';
			}
			$allowedMounts .= 'windows_network_drive';

			$this->appConfig->setValue('files_external', 'allow_user_mounting', 'yes');
			$this->appConfig->setValue('files_external', 'user_mounting_backends', $allowedMounts);
		}
	}

	// TODO: Delete old WND DB tables?
	private function cleanOldSetup() {
		$this->log("Clean finished", \OCP\Util::DEBUG);
	}

	/**
	 * Log helper
	 */
	private function log($message, $level, $from='wnd-migration') {
		\OCP\Util::writeLog($from, $message, $level);
	}
}
