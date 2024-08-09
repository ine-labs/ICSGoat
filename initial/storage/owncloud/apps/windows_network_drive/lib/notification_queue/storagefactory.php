<?php
/**
 * ownCloud
 *
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 * @copyright Copyright (c) 2016, ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\windows_network_drive\lib\notification_queue;

use OCA\windows_network_drive\lib\fs_backend\WND;
use OCA\windows_network_drive\lib\fs_backend\WND2;
use OCA\windows_network_drive\lib\Utils;
use OCP\Files\External\IStorageConfig;
use OCP\Files\External\Service\IGlobalStoragesService;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\ILogger;

/**
 * Create and return storage instances based on certain conditions. For now only WND storages that
 * target an specific host and share will be returned (notice that storages for several users might
 * be returned)
 */
class StorageFactory {
	private $globalStorageService;
	private $userManager;
	private $groupManager;
	private $logger;

	/**
	 * @param IGlobalStoragesService $globalStorageService
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param ILogger $logger
	 */
	public function __construct(IGlobalStoragesService $globalStorageService, IUserManager $userManager, IGroupManager $groupManager, ILogger $logger) {
		$this->globalStorageService = $globalStorageService;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->logger = $logger;
	}

	/**
	 * Fetch WND storages targeting the specified host and share. Consider the function to return a
	 * list of storages (IStorage[]). The keys of the arrays are the storage ids in order to provide
	 * a list of unique storages, so don't rely on this fact and don't consider the list sorted.
	 *
	 * Note that this function won't filter by user. Storages used by any user will be returned, it
	 * doesn't matter if the storage configurations are system-wide or personal.
	 *
	 * @param string $host the specific host
	 * @param string $share the specific share
	 * @return array the array keys will be the storage ids, and the values will be the specific
	 * storage implementations ready to be used.
	 */
	public function fetchStoragesForServer($host, $share) {
		$mounts = $this->globalStorageService->getStorageForAllUsers();
		$mapping = $this->generateMapping($mounts, $host, $share);
		$storageList = [];
		foreach ($mapping as $user_id => $data) {
			// traverse the mapping. A $user_id !== '' will imply that we need to generate a storage for
			// that specific user (the user object should be ready in $data['user_obj']).
			// If $user_id === '' implies that all users can mount that storage configuration, so we need
			// to generate the storage for all the users (we limit the users to the ones that have
			// logged in at least once)
			if ($user_id !== '') {
				foreach ($data['mounts'] as $storage_conf) {
					$storage = $this->generateStorageForUser($data['user_obj'], $storage_conf);
					if ($storage) {
						$storageId = $storage->getId();
						if (!isset($storageList[$storageId])) {
							$storageList[$storageId] = $storage;
						}
						$storageList[$storageId]->addMountWithUser($storage_conf->getMountPoint(), $data['user_obj']);
					}
				}
			} else {
				$this->userManager->callForSeenUsers(function ($userObj) use ($data, &$storageList) {
					foreach ($data['mounts'] as $storage_conf) {
						$storage = $this->generateStorageForUser($userObj, $storage_conf);
						if ($storage) {
							$storageId = $storage->getId();
							if (!isset($storageList[$storageId])) {
								$storageList[$storageId] = $storage;
							}
							$storageList[$storageId]->addMountWithUser($storage_conf->getMountPoint(), $userObj);
						}
					}
				});
			}
		}
		return $storageList;
	}

	/**
	 * Fetch WND storages targeting the specified host and share and matching any of the SMB accounts in the list.
	 * Consider the function to return a
	 * list of storages (IStorage[]). The keys of the arrays are the storage ids in order to provide
	 * a list of unique storages, so don't rely on this fact and don't consider the list sorted.
	 *
	 * The accounts in the account list must have the domain if the storage also uses the domain.
	 *
	 * @param string $host the specific host
	 * @param string $share the specific share
	 * @param string[] $accountList the list of user ids to filter
	 * @return array the array keys will be the storage ids, and the values will be the specific
	 * storage implementations ready to be used.
	 */
	public function fetchStoragesForServerAndAccounts($host, $share, $accountList) {
		$mounts = $this->globalStorageService->getStorageForAllUsers();
		$mapping = $this->generateMapping($mounts, $host, $share);
		$storageList = [];
		foreach ($mapping as $user_id => $data) {
			if ($user_id !== '') {
				foreach ($data['mounts'] as $storage_conf) {
					$storage = $this->generateStorageForUser($data['user_obj'], $storage_conf);
					if ($storage && \in_array(Utils::conditionalDomainPlusUsername($storage->getDomain(), $storage->getUser()), $accountList, true)) {
						$storageId = $storage->getId();
						$storageList[$storageId] = $storage;
						$storageList[$storageId]->addMountWithUser($storage_conf->getMountPoint(), $data['user_obj']);
					}
				}
			} else {
				$this->userManager->callForSeenUsers(function ($userObj) use ($data, &$storageList, $accountList) {
					foreach ($data['mounts'] as $storage_conf) {
						$storage = $this->generateStorageForUser($userObj, $storage_conf);
						if ($storage && \in_array(Utils::conditionalDomainPlusUsername($storage->getDomain(), $storage->getUser()), $accountList, true)) {
							$storageId = $storage->getId();
							$storageList[$storageId] = $storage;
							$storageList[$storageId]->addMountWithUser($storage_conf->getMountPoint(), $userObj);
						}
					}
				});
			}
		}
		return $storageList;
	}

	/**
	 * Fetch WND storages targeting the specified host and share, but only for the ownCloud users in the
	 * list. Note that only storages with "login credentials saved in DB" and "credentials hardcoded in config file"
	 * will be fetched and the rest will be ignored. Consider the function to return a
	 * list of storages (IStorage[]). The keys of the arrays are the storage ids in order to provide
	 * a list of unique storages, so don't rely on this fact and don't consider the list sorted.
	 *
	 * The accounts in the account list must have the domain if the storage also uses the domain.
	 *
	 * @param string $host the specific host
	 * @param string $share the specific share
	 * @param string[] $accountList the list of user ids to filter
	 * @return array the array keys will be the storage ids, and the values will be the specific
	 * storage implementations ready to be used.
	 */
	public function fetchStoragesForServerAndForUsers($host, $share, $userList) {
		$mounts = $this->globalStorageService->getStorageForAllUsers();
		$mapping = $this->generateMapping($mounts, $host, $share, ['password::logincredentials', 'password::hardcodedconfigcredentials']);
		$storageList = [];
		foreach ($mapping as $user_id => $data) {
			if ($user_id !== '') {
				if (\in_array($user_id, $userList, true)) {
					// ensure the user_id is inside the list, otherwise we're not interested
					foreach ($data['mounts'] as $storage_conf) {
						$storage = $this->generateStorageForUser($data['user_obj'], $storage_conf);
						if ($storage) {
							$storageId = $storage->getId();
							$storageList[$storageId] = $storage;
							$storageList[$storageId]->addMountWithUser($storage_conf->getMountPoint(), $data['user_obj']);
						}
					}
				}
			} else {
				foreach ($userList as $user) {
					$userObj = $this->userManager->get($user);
					if ($userObj) {
						foreach ($data['mounts'] as $storage_conf) {
							$storage = $this->generateStorageForUser($userObj, $storage_conf);
							if ($storage) {
								$storageId = $storage->getId();
								$storageList[$storageId] = $storage;
								$storageList[$storageId]->addMountWithUser($storage_conf->getMountPoint(), $userObj);
							}
						}
					}
				}
			}
		}
		return $storageList;
	}

	/**
	 * the function will return something like:
	 * [user_id => ['mounts' => [storage_conf1, storage_conf2, ...],
	 *              'user_obj' => user_object],
	 *  user_id2 => ['mounts' => [storage_conf1],
	 *               'user_obj' => user_object2]]
	 * traverse the map based on the user_id and generate + store the storages, this will
	 * allow us to fetch the user list only once.
	 * for the special case of "all users", the user_id will be '' and the user_object will be null
	 */
	private function generateMapping($mountList, $host, $share, $onlyForAuths = null) {
		$userStorageMap = [];
		foreach ($mountList as $mount) {
			$mountBackend = $mount->getBackend();
			if ($mountBackend instanceof WND || $mountBackend instanceof WND2) {
				$mountHost = $mount->getBackendOption('host');
				$mountShare = $mount->getBackendOption('share');
				$authId = $mount->getAuthMechanism()->getIdentifier();
				if ($mountHost === $host && $mountShare === $share && ($onlyForAuths === null || \in_array($authId, $onlyForAuths, true))) {
					$userList = $this->getUserList($mount);
					if (empty($userList)) {
						if (!isset($userStorageMap[''])) {
							$userStorageMap[''] = ['mounts' => [$mount], 'user_obj' => null];
						} else {
							$userStorageMap['']['mounts'][] = $mount;
						}
					} else {
						foreach ($userList as $user_id => $user_object) {
							if (!isset($userStorageMap[$user_id])) {
								$userStorageMap[$user_id] = ['mounts' => [$mount], 'user_obj' => $user_object];
							} else {
								$userStorageMap[$user_id]['mounts'][] = $mount;
							}
						}
					}
				}
			} else {
				$backendClass = \get_class($mount->getBackend());
				$this->logger->debug("ignoring mount of class $backendClass. No mappings will be generated", ['app' => 'wnd']);
			}
		}
		return $userStorageMap;
	}

	/**
	 * @return array[IUser] a list of users will be returned based on the applicable
	 * list (using users and groups)
	 */
	private function getUserList(IStorageConfig $storageConfig) {
		$applicableUsers = $storageConfig->getApplicableUsers();
		$applicableGroups = $storageConfig->getApplicableGroups();
		if (empty($applicableUsers) && empty($applicableGroups)) {
			// we'll need to fetch all the users at some point
			return [];
		}
		$userList = [];  // we'll rely on the user's UID being unique
		foreach ($applicableUsers as $applicableUser) {
			$userObj = $this->userManager->get($applicableUser);
			if ($userObj) {
				$userList[$userObj->getUID()] = $userObj;
			}
		}
		// foreach group we need to retrieve its users
		foreach ($applicableGroups as $applicableGroup) {
			$group = $this->groupManager->get($applicableGroup);
			if ($group) {
				$groupedUsers = $group->getUsers();
				foreach ($groupedUsers as $groupedUser) {
					$userList[$groupedUser->getUID()] = $groupedUser;
				}
			}
		}
		return $userList;  // this will return a list with non-duplicated users
	}

	/**
	 * Generate the storage for the user based on the configuration.
	 *
	 * @param IUser $user the user for the storage
	 * @param IStorageConfig $storageConfig the storage configuration
	 * @return IStorage|false a storage implementation or false if something went wrong
	 */
	private function generateStorageForUser(IUser $user, IStorageConfig $storageConfig) {
		// clone the object so we can manipulate the configuration freely
		$mount = clone $storageConfig;
		// following prepareStorageConfig from files_external lib/Config/ConfigAdapter
		foreach ($mount->getBackendOptions() as $option => $value) {
			$mount->setBackendOption($option, \OC\Files\External\LegacyUtil::setUserVars($user->getUserName(), $value));
		}
		$auth = $mount->getAuthMechanism();
		$backend = $mount->getBackend();
		if ($auth->getIdentifier() === 'password::global' && $mount->getType() === IStorageConfig::MOUNT_TYPE_ADMIN) {
			$auth->manipulateStorageConfig($mount, null);
		} else {
			try {
				$auth->manipulateStorageConfig($mount, $user);
			} catch (\OCP\Files\External\InsufficientDataForMeaningfulAnswerException $ex) {
				// user's global credentials not entered or user didn't entered credentials
				// for user entered auth => ignore and go to the next
				$this->logger->debug('ignoring  ' . $storageConfig->getMountPoint() . ' for ' . $user->getUID() . ' : ' . $ex->getMessage(), ['app' => 'wnd']);
				return false;
			}
		}
		$backend->manipulateStorageConfig($mount, $user);
		// construct the storage
		$class = $backend->getStorageClass();
		$storage = new $class($mount->getBackendOptions());
		// auth mechanism should fire first
		$storage = $backend->wrapStorage($storage);
		$storage = $auth->wrapStorage($storage);
		return $storage;
	}

	/**
	 * Find and return storage configurations that match all the conditions.
	 * The conditions are a map of backendOption => value, such as
	 * ['host' => 'myhost', 'share' => 'myshare']
	 * Note that all conditions must match, so using a wrong backend option ('missingKey' for
	 * example) won't return anything.
	 * This method works only with whatever can be extracted from the backend options. In
	 * particular, auth options such as "classic" username and password won't be available
	 *
	 */
	public function findMatchingStorageConfig(array $classes, array $opts) {
		$storageConfigs = [];
		$mounts = $this->globalStorageService->getStorageForAllUsers();
		foreach ($mounts as $mount) {
			$backend = $mount->getBackend();
			// empty backendClasses implies that any backend class is accepted, so no filter
			// by backend class
			if (!empty($classes) && !\in_array(\get_class($backend), $classes, true)) {
				continue;
			}

			$backendOptions = $mount->getBackendOptions();

			foreach ($opts as $key => $value) {
				if (!isset($backendOptions[$key]) || $backendOptions[$key] !== $value) {
					continue 2;  // check the next mount
				}
			}
			$storageConfigs[] = $mount;
		}
		return $storageConfigs;
	}
}
