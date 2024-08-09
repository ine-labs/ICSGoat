<?php
/**
 * ownCloud
 *
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 * @author Jesus Macias Portela <jesus@owncloud.com>
 * @author Ilja Neumann <ineumann@owncloud.com>
 *
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\windows_network_drive\lib;
use OCA\windows_network_drive\Lib\Auth\LoginCredentials;
use OCA\windows_network_drive\lib\notification_queue\StorageFactory;
use OCP\Files\External\IStorageConfig;
use OCP\Files\External\Service\IStoragesService;
use OCP\Files\Cache\ICache;
use OCP\IConfig;
use OCP\IUser;

/**
 * A general utilities class for the windows network drive app
 */
class Utils {
	/**
	 * Encrypt the string
	 *
	 * @param string $password text to be encryted
	 * @return string the encrypted text
	 */
	public static function encryptPassword($password) {
		return \OC::$server->getCrypto()->encrypt($password);
	}

	/**
	 * Decrypt the string
	 *
	 * @param string $storedPassword text to be decryted
	 * @return string|false the decrypted text or false if the storedPassword is wrong
	 */
	public static function decryptPassword($storedPassword) {
		try {
			return \OC::$server->getCrypto()->decrypt($storedPassword);
		} catch (\Exception $e) {
			// Preserve compatibility with Rijndael as it was used as cipher before
			$cipher = new \phpseclib3\Crypt\Rijndael('cbc');
			$encryptedPassword = \base64_decode($storedPassword);
			$iv = \substr($encryptedPassword, 0, 16);
			$binaryPassword = \substr($encryptedPassword, 16);
			if ($binaryPassword === false) {
				return false;
			}
			$cipher->setIV($iv);
			$cipher->setKey(Utils::getCipherKey());
			try {
				return $cipher->decrypt($binaryPassword);
			} catch (\Exception $e) {
				return false;
			}
		}
	}

	/**
	 * phpseclib3 only supports specific key lengths for then Rijndael cipher.
	 * Thus me might need to pad null bytes here.
	 * @return string
	 */
	private static function getCipherKey() {
		$passwordSalt = \OC::$server->getConfig()->getSystemValue('passwordsalt');
		$maxKeyLength = 32;
		$allowedKeyLengths = [16, 20, 24, 28, 32];

		if (\in_array(\strlen($passwordSalt), $allowedKeyLengths)) {
			return $passwordSalt;
		}

		if (\strlen($passwordSalt) > $maxKeyLength) {
			// Cut password salt
			return \substr($passwordSalt, 0, $maxKeyLength);
		}

		// Expand password salt with null bytes
		return \str_pad($passwordSalt, $maxKeyLength, "\0");
	}

	/**
	 * Check for invalid char in the string and perform an action if needed
	 *
	 * @param string $stringToCheck the string to be checked
	 * @param string $charList a string with the invalid chars
	 * @param callable $action the action to be taken if there are any invalid chars
	 * To the action an array with the single-qouted chars will be passed as parameter
	 */
	public static function checkInvalidChars($stringToCheck, $charList, $action) {
		if (\strpbrk($stringToCheck, $charList) !== false) {
			$invalidCharsList = \array_map(function ($e) {
				return '\'' . $e . '\'';
			}, \array_unique(\str_split($charList, 1)));
			\call_user_func($action, $invalidCharsList);
		}
	}

	/**
	 * Check if a file (or folder) is inside a folder. This just does path manipulation without
	 * connection to the FS
	 *
	 * @param $file the file (or folder) to be checked
	 * @param $folder the folder
	 */
	public static function isInsideFolder($file, $folder) {
		$file = \rtrim($file, '/');
		$folder = \rtrim($folder, '/');
		return ($file === $folder || \substr($file, 0, \strlen($folder) + 1) === $folder . '/');
	}

	/**
	 * Get the relative path of $file based on $folder. No path calculation or normalization will
	 * be done in this method, so getRelativePath('/path/to/file', /path/././to/be/../) will fail.
	 * Normalize the paths if needed before using this function.
	 *
	 * Some considerations:
	 * getRelativePath('/foo', '/foo') will return an empty string
	 * getRelativePath('/foo', 'foo') will fail (return null)
	 * getRelativePath('/foo/file', '/foo') and getRelativePath('/foo/file', '/foo/') will return
	 * 'file' both
	 *
	 * @param $file the file whose relative path we want to obtain
	 * @param $folder the base path where we calculate the relative path from.
	 * @return string|null the relative path of the file or null if the file is outside the folder
	 */
	public static function getRelativePath($file, $folder) {
		$file = \rtrim($file, '/');
		$folder = \rtrim($folder, '/');

		if ($file === $folder) {
			// quick fix for problems with php 5.6 and substr returning false
			return "";
		}

		$baseFolder = \substr($file, 0, \strlen($folder));
		if ($baseFolder === $folder) {
			// $file must be inside $folder, so $baseFolder must match $folder
			$relativePath = \substr($file, \strlen($folder));
			if ($relativePath === '' || $relativePath[0] === '/') {
				// at this point $relativePath must be an empty string if the $baseFolder is the
				// same as $folder, or it must start with '/', otherwise the file is outside
				// check getRelativePath('/foo/file', '/foo/fi')
				return \trim(\substr($file, \strlen($folder)), '/');
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	/**
	 * Check if a file (or folder) is inside a folder. This method is aware of '$user' placeholders
	 * in the folder's path, so
	 * isInsideFolderUserAware('/path/to/usernumber123/file/text.txt', '/path/to/$user') will match
	 * This just does path manipulation without connection to the FS
	 *
	 * @param $file the file (or folder) to be checked
	 * @param $folder the folder
	 */
	public static function isInsideFolderUserAware($file, $folder) {
		$file = \rtrim($file, '/');
		$folder = \rtrim($folder, '/');
		$quotedFolder = \preg_quote($folder);
		$preparedFolder = \preg_replace('/\\\\\$user/', '[^/]+', $quotedFolder);
		$toBeMatchedFolder = '#^' . $preparedFolder . '/#';
		return \preg_match($toBeMatchedFolder, $file . '/') === 1;
	}

	public static function getRelativePathUserAware($file, $folder) {
		$file = \rtrim($file, '/');
		$folder = \rtrim($folder, '/');
		$quotedFolder = \preg_quote($folder);
		$preparedFolder = \preg_replace('/\\\\\$user/', '[^/]+', $quotedFolder);
		$toBeMatchedFolder = '#^' . $preparedFolder . '/#';
		if (\preg_match($toBeMatchedFolder, $file . '/') === 1) {
			return \trim(\preg_replace($toBeMatchedFolder, '', $file . '/'), '/');
		} else {
			return false;
		}
	}

	/**
	 * Get the value for the key in the array or the default value if the key is not set.
	 * This function will check with the isset() function to check if the key is in the array,
	 * so notice that there might be special cases where the function will get the default
	 * value instead of the real one
	 *
	 * @param array $arr the array where the key will be looked for
	 * @param mixed $key the key for the array
	 * @param mixed $default the value that will be returned if the check fails
	 * @return mixed the value contained in the array or the default passed
	 */
	public static function getDefault($arr, $key, $default = null) {
		return (isset($arr[$key])) ? $arr[$key] : $default;
	}

	/**
	 * reset user-custom per-storage credentials if log in failed
	 * in order to avoid user lock-out in AD
	 *
	 * @param WND $wnd
	 */
	public static function resetPassword(WND $wnd) {
		$users = [];
		$mountsWithUsers = $wnd->getMountsWithUsers();
		foreach ($mountsWithUsers as $mount => $userList) {
			foreach ($userList as $userObj) {
				$users[$userObj->getUID()] = $userObj;
			}
		}
		$users = \array_values($users);
		if (empty($users)) {
			// assume the user list wasn't set during the storage creation
			// so consider the session user as the one using it (there might be more users,
			// but we won't reset those without being sure)
			$sessionUser = \OC::$server->getUserSession()->getUser();
			if (!$sessionUser) {
				// no session so we assume that this is running via cli/cronjob
				// thus we reset the storage credentials for all users using a particular
				// share
				$storageFactory = new StorageFactory(
					\OC::$server->getUserGlobalStoragesService(),
					\OC::$server->getUserManager(),
					\OC::$server->getGroupManager(),
					\OC::$server->getLogger()
				);

				/** @var WND[] $storages */
				$storages = $storageFactory->fetchStoragesForServer($wnd->getHost(), $wnd->getShareName());
				if (isset($storages[$wnd->getId()])) {
					$mounts = $storages[$wnd->getId()]->getMountsWithUsers();
					foreach ($mounts as $mount => $mountUsers) {
						$users = \array_merge($users, $mountUsers);
					}
				}
			} else {
				$users = [$sessionUser];
			}
		}

		$globalStorageService = \OC::$server->getGlobalStoragesService();
		// check global storage first
		foreach ($users as $user) {
			$storageConfigs = $globalStorageService->getAllStorages();
			self::resetPasswordForUser($user, $globalStorageService, $wnd);
		}

		$userStorageService = \OC::$server->getUserStoragesService();
		foreach ($users as $user) {
			$userStorageService->setUser($user);  // FIXME private API
			$storageConfigs = $userStorageService->getAllStorages();
			self::resetPasswordForUser($user, $userStorageService, $wnd);
			$userStorageService->resetUser();  // FIXME private API
		}
	}

	/**
	 * @param IUser $user the user mounting the storage
	 * @param IStorageService $service the storage service where we fetch the storage configurations from
	 * @param WND $wnd the WND storage that triggered the password reset
	 */
	private static function resetPasswordForUser(IUser $user, IStoragesService $service, WND $wnd) {
		$uid = $user->getUID();
		$storageConfigs = $service->getAllStorages();
		foreach ($storageConfigs as $storageConfig) {
			$storageConfigIdentifier = $storageConfig->getBackend()->getIdentifier();
			if ($storageConfigIdentifier === 'windows_network_drive' || $storageConfigIdentifier === 'windows_network_drive2') {
				$domainOption = $storageConfig->getBackendOption('domain');
				$hostOption = $storageConfig->getBackendOption('host');

				if ($storageConfigIdentifier === 'windows_network_drive2') {
					// for WND2 we need to check the service account in addition to the auth mechanisms
					$serviceUser = $storageConfig->getBackendOption('service-account');
					$usernameWithDomain = self::conditionalDomainPlusUsername($domainOption, $serviceUser);
					self::conditionalResetPassword(
						$hostOption,
						$usernameWithDomain,
						$wnd,
						function () use ($storageConfig, $service) {
							// reset the password by changing the storage configuration
							$storageConfig->setBackendOption('service-account-password', '');
							$service->updateStorage($storageConfig);
						}
					);
				}

				$authMechanism = $storageConfig->getAuthMechanism();
				switch ($authMechanism->getIdentifier()) {
					case 'password::global':
						if ($storageConfig->getType() === IStorageConfig::MOUNT_TYPE_ADMIN) {
							$uid = '';
						}

						$credentials = $authMechanism->getAuth($uid, $storageConfig->getId());
						$usernameWithDomain = self::conditionalDomainPlusUsername($domainOption, $credentials['user']);
						self::conditionalResetPassword(
							$hostOption,
							$usernameWithDomain,
							$wnd,
							function () use ($authMechanism, $uid) {
								$authMechanism->resetPassword($uid);
							}
						);
						break;
					case 'password::userprovided':
						$credentials = $authMechanism->getAuth($uid, $storageConfig->getId());
						$usernameWithDomain = self::conditionalDomainPlusUsername($domainOption, $credentials['user']);
						$storageId = $storageConfig->getId();
						self::conditionalResetPassword(
							$hostOption,
							$usernameWithDomain,
							$wnd,
							function () use ($authMechanism, $user, $storageId) {
								// user comes from the very top of the function
								$authMechanism->resetPassword($user, $storageId);
							}
						);
						break;
					case 'password::password':
						$credUsername = $storageConfig->getBackendOption('user');
						$usernameWithDomain = self::conditionalDomainPlusUsername($domainOption, $credUsername);
						self::conditionalResetPassword(
							$hostOption,
							$usernameWithDomain,
							$wnd,
							function () use ($storageConfig, $service) {
								// reset the password by changing the storage configuration
								$storageConfig->setBackendOption('password', '');
								$service->updateStorage($storageConfig);
							}
						);
						break;
					case 'password::logincredentials':
						if ($authMechanism instanceof LoginCredentials) {
							$creds = $authMechanism->getCredentials($user);
							$credUsername = $creds['user'];
							$usernameWithDomain = self::conditionalDomainPlusUsername($domainOption, $credUsername);
							self::conditionalResetPassword(
								$hostOption,
								$usernameWithDomain,
								$wnd,
								function () use ($authMechanism, $user) {
									// user comes from the very top of the function
									$authMechanism->resetPassword($user->getUID());
								}
							);
						}
						break;
				}
			}
		}
	}

	/**
	 * Helper function for the password reset one
	 */
	private static function conditionalResetPassword($host, $username, WND $wnd, callable $func) {
		$wndDomainUser = self::conditionalDomainPlusUsername($wnd->getDomain(), $wnd->getUser());
		if ($host === $wnd->getHost() && $username === $wndDomainUser) {
			return \call_user_func($func);
		}
	}

	/**
	 * Add the domain name to the username conditionally
	 * If there is a domain the function will return something like <domain>\<username>
	 * If there is no domain (domain is not set or is empty) it will return just the username
	 * (without the backslash)
	 */
	public static function conditionalDomainPlusUsername($domain, $username) {
		if (isset($domain) && $domain !== ""
			&& \strpos($username, "\\") === false && \strpos($username, "/") === false
		) {
			$usernameWithDomain = $domain . "\\" . $username;
		} else {
			$usernameWithDomain = $username;
		}
		return $usernameWithDomain;
	}

	/**
	 * Get the closest parent path that is in the cache. If $path is in the cache
	 * it will be returned, otherwise traverse each parent upwards to the root
	 * checking if it's in the cache, and return the first parent found that is in the cache.
	 */
	public static function innermostPathInCache(ICache $cache, $path) {
		while (!$cache->inCache($path)) {
			$path = \dirname($path);
			if ($path === '' || $path === '/' || $path === '.') {
				$path = '';
				break;
			}
		}
		return $path;
	}

	/**
	 * Get the value from a nested array. You'll need to provide a list of sorted keys
	 * access to the nested elements.
	 * For example, if you have `$a['key1']['key2']['key3'] = 'myvalue'` you can access
	 * to that value using getValueFromNestedArray($a, ['key1', 'key2', 'key3'])
	 *
	 * The function will throw an InvalidArgumentException if the composedKey is wrong:
	 * either one element is missing or any element except the last one isn't an array
	 * (and we can't access to inner elements)
	 *
	 * @param array $nestedArray the array we want to check
	 * @param array $keyList the list of sorted key we'll use to go deeper in the array
	 * @return mixed the value of the element
	 * @throws \InvalidArgumentException in case of error.
	 */
	public static function getValueFromNestedArray(array $nestedArray, array $keyList) {
		if (\count($keyList) < 1) {
			throw new \InvalidArgumentException('keyList must contain at least one element');
		}
		$targetArray = $nestedArray;
		$nestedKeyList = \array_slice($keyList, 0, -1);  // exclude the last item
		$lastKey = \array_slice($keyList, -1, 1);
		$lastKey = $lastKey[0];  // get the element

		foreach ($nestedKeyList as $keyItem) {
			if (!\array_key_exists($keyItem, $targetArray)) {
				throw new \InvalidArgumentException("Missing key: $keyItem");
			}
			if (!\is_array($targetArray[$keyItem])) {
				throw new \InvalidArgumentException("$keyItem doesn't reference an array");
			}
			$targetArray = $targetArray[$keyItem];
		}

		if (!\array_key_exists($lastKey, $targetArray)) {
			throw new \InvalidArgumentException("Missing key: $lastKey");
		}
		return $targetArray[$lastKey];
	}

	/**
	 * Get the value from a nested array in the system configuration. The function won't look
	 * into app or user preferences.
	 * You need to provide a sorted list of keys such as ['key1', 'key2', 'key3'] in order to
	 * access to the value of 'key3' which is inside 'key2', which is inside 'key1'
	 * @param IConfig $config the IConfig object in order to get the system values
	 * @param array $keyList the sorted list of keys to get the value from the deepest key
	 * @return mixed the value
	 * @throws \InvalidArgumentException if the keyList doesn't contain at least one key, or the
	 * initial key is missing from the configuration, or if we can't get the value (wrong key list)
	 */
	public static function getValueFromNestedArrayFromConfig(IConfig $config, array $keyList) {
		$keyListCount = \count($keyList);
		if ($keyListCount < 1) {
			throw new \InvalidArgumentException('keyList must contain at least one element');
		}

		$dummyObject = new \stdClass();
		$systemValue = $config->getSystemValue($keyList[0], $dummyObject);
		if ($keyListCount === 1) {
			if ($systemValue === $dummyObject) {
				// same reference
				throw new \InvalidArgumentException("{$keyList[0]} is missing in the config");
			} else {
				return $systemValue;
			}
		} else {
			if (!\is_array($systemValue)) {
				throw new \InvalidArgumentException("{$keyList[1]} doesn't reference an array");
			} else {
				return self::getValueFromNestedArray($systemValue, \array_slice($keyList, 1));
			}
		}
	}
}
