<?php
/**
 * @author Jesus Macias <jesus@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCA\windows_network_drive\lib;

use OC\Files\Filesystem;
use OCP\Files\StorageNotAvailableException;
use OCP\Files\Storage\StorageAdapter;
use OCP\ILogger;
use OCA\windows_network_drive\lib\acl\permissionmanager\PermissionManagerFactory;
use OCA\windows_network_drive\lib\acl\permissionmanager\PermissionManagerException;
use OCA\windows_network_drive\lib\smbwrapper\SmbclientWrapper;
use OCA\windows_network_drive\lib\smbwrapper\SmbclientWrapperException;
use OCA\windows_network_drive\lib\smbwrapper\SMBStatInfo;
use OCA\windows_network_drive\lib\MRUCache;
use OCP\IUser;

/**
 * FS class to connect to windows network drives
 */
class WND extends StorageAdapter {
	protected $logActive;
	protected $isInitialized = false;
	protected $shareName;
	protected $host;
	protected $domain = '';
	protected $user;
	protected $password;
	protected $root;
	protected $permissionManagerName = '';

	/** @var SmbclientWrapper */
	protected $smbclientWrapper;
	/** @var array<string, IUser[]> */
	protected $mountsWithUsers = [];

	protected $wndNotifier;
	protected $permissionManager;
	/** @var ILogger */
	protected $logger;

	/** @var MRUCache */
	protected $statCache;
	protected $cachedAclPermissions = [];
	protected $cachedPathsBuilt = [];

	protected $parseAttrMode;

	private $storageId;

	public function __construct($params) {
		$config = \OC::$server->getConfig();
		if (!isset($this->logActive)) {
			$this->logActive = $config->getSystemValue('wnd.logging.enable', false) === true;
		}
		$this->logger = \OC::$server->getLogger();

		$copiedParams = $params;

		if (isset($params['password'])) {
			// if password is set, change it so it doesn't appear in the logs
			$copiedParams['password'] = '***removed***';
		}
		$this->logEnter('__construct', $copiedParams);

		if (!isset($params['user'], $params['password'], $params['host'], $params['share'])) {
			// these are required parameters -> throw an exception if any is missing (keeps the
			// previous behavior)
			$ex = new \Exception('Invalid configuration: '.\json_encode($copiedParams));
			$this->logLeave(__FUNCTION__, $ex);
			throw $ex;
		}

		$this->domain = $params['domain'] ?? '';
		$this->user = $params['user'];
		$this->password = $params['password'];
		$this->shareName = $params['share'];
		$this->host = $params['host'];

		if (isset($params['root']) && $params['root'] !== '' && $params['root'] !== '/') {
			$this->root = '/' . trim($params['root'], '/') . '/';
		} else {
			$this->root = '/';
		}

		$smbclientWrapperOpts = [
			SMBCLIENT_OPT_TIMEOUT => $config->getSystemValue('wnd.connector.opts.timeout', SmbclientWrapper::OPTS_TIMEOUT),
		];
		$this->smbclientWrapper = new SmbclientWrapper($this->host, $this->shareName, $this->domain, $this->user, $this->password, $smbclientWrapperOpts);
		// register in the singleton notifier
		if ($config->getSystemValue('wnd.in_memory_notifier.enable', true) === true) {
			$this->wndNotifier = WNDNotifier::getSingleton();
			$this->wndNotifier->registerWND($this);
		}

		$factory = new PermissionManagerFactory();
		$factoryParameters = [
			'smbclientWrapper' => $this->smbclientWrapper,
			'host' => $this->host,
			'share' => $this->shareName,
			'domain' => $this->domain,
			'user' => $this->user,
			'password' => $this->password,
		];

		if (isset($params['permissionManager'])) {
			$this->permissionManagerName = $params['permissionManager'];
		}
		$this->permissionManager = $factory->createPermissionManagerByName($this->permissionManagerName, $factoryParameters);

		$this->parseAttrMode = $config->getSystemValue('wnd.fileInfo.parseAttrs.mode', 'stat');
		if (!\in_array($this->parseAttrMode, ['none', 'stat', 'getxattr'], true)) {
			$this->parseAttrMode = 'stat';
		}

		$this->statCache = new MRUCache();
	}

	public function init() {
		if ($this->isInitialized) {
			return;
		}

		$this->validateConfiguration();

		try {
			$this->connectionTestWithReset();
		} catch (SmbclientWrapperException $e) {
			throw new StorageNotAvailableException('Storage not available, perhaps outdated credentials.', 1, $e);
		}
		$this->isInitialized = true;
	}

	/**
	 * Get the username's domain trying to access to the windows drive. It might be different than the
	 * current ownCloud user. Note that the domain won't be extracted from the username (in case a username
	 * such as "mydomain\myusername" is used). The domain must be supplied as an additional parameter.
	 *
	 * @return string the domain
	 */
	public function getDomain() {
		return $this->domain;
	}

	/**
	 * Get the username trying to access to the windows drive. It might be different than the
	 * current ownCloud user
	 *
	 * @return string the username
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * Get the host we're trying to access
	 *
	 * @return string the host
	 */
	public function getHost() {
		return $this->host;
	}

	/**
	 * Get the sharename where we're trying to access
	 *
	 * @return string the share name
	 */
	public function getShareName() {
		return $this->shareName;
	}

	/**
	 * Get the folder that will act as our root
	 *
	 * @return string the root folder
	 */
	public function getRoot() {
		return $this->root;
	}

	public function getPassword() {
		return $this->password;
	}

	/**
	 * Get the name of the permissions manager used by this instance. Note that the
	 * actual object won't be returned, just the name
	 */
	public function getPermissionManagerName() {
		return $this->permissionManagerName;
	}

	protected function buildPath(string $path) {
		$this->logEnter(__FUNCTION__, [$path]);
		if (!isset($this->cachedPathsBuilt[$path])) {
			$result = Filesystem::normalizePath("{$this->root}/{$path}", true, false, true);
			// just cache only one path, for consecutive calls.
			// "normalizePath" has a inconditional json_encode call that could take a bit of time
			$this->cachedPathsBuilt = [$path => $result];
		}
		return $this->logLeave(__FUNCTION__, $this->cachedPathsBuilt[$path]);
	}

	/**
	 * Get the FS id (for ownCloud purposes). Method overloaded to keep retrocompatibility.
	 *
	 * @return string the FS id
	 */
	public function getId(): string {
		if ($this->storageId !== null) {
			$this->log("getId id: {$this->storageId}", \OCP\Util::DEBUG);
			return $this->storageId;
		}

		$username = Utils::conditionalDomainPlusUsername($this->getDomain(), $this->getUser());
		$this->storageId = "wnd::{$username}@{$this->getHost()}/{$this->getShareName()}/{$this->getRoot()}";
		$this->log("getId id: {$this->storageId}", \OCP\Util::DEBUG);
		return $this->storageId;
	}

	public function opendir($path) {
		$this->init();
		$this->logEnter(__FUNCTION__, [$path]);

		$result = $this->opendirWithOpts($path);
		return $this->logLeave(__FUNCTION__, $result);
	}

	public function opendirWithOpts($path, $opts = []) {
		$this->init();
		$this->logEnter(__FUNCTION__, [$path]);

		$pathBuilt = $this->buildPath($path);
		// options for the opendir
		$defaultOpts = [];
		$defaultOpts['logger'] = function ($message, $context) {
			if ($this->logActive) {
				$this->logger->error($message, $context);
			}
		};
		$defaultOpts['readFilter'] = function ($wrapper, $data) use ($path) {
			if ($data['name'] === '.' || $data['name'] === '..') {
				return false;
			}

			if ($this->parseAttrMode === 'none' || ($this->parseAttrMode === 'stat' && $data['type'] === 'directory')) {
				// automatically accept if we aren't parsing file attributes or if it's "stat" mode
				// and a directory. For the rest of the cases, we'll have to check further.
				return true;
			}

			$contentPath = "{$path}/{$data['name']}";
			try {
				$statInfo = $this->rawStat($contentPath);
				if ($statInfo->isHidden()) {
					return false;
				}
				return true;
			} catch (SmbclientWrapperException $e) {
				$this->log("error in opendir: stat call for {$contentPath} failed: [{$e->getCode()}] {$e->getMessage()}", \OCP\Util::ERROR);
				return false;
			}
		};

		$realOpts = $opts + $defaultOpts;
		return $this->logLeave(__FUNCTION__, $this->smbclientWrapper->openDirectory($pathBuilt, $realOpts));
	}

	public function mkdir($path) {
		$this->init();
		$this->logEnter(__FUNCTION__, [$path]);

		$result = false;
		try {
			$this->smbclientWrapper->createFolder($this->buildPath($path));
			$result = true;
		} catch (SmbclientWrapperException $e) {
			if ($this->isConnectivityError($e)) {
				$ex = new StorageNotAvailableException($e->getMessage(), $e->getCode(), $e);
				$this->logLeave(__FUNCTION__, $ex);
				throw $ex;
			}
			$this->logSwallow(__FUNCTION__, $e);
		}
		return $this->logLeave(__FUNCTION__, $result);
	}

	public function rmdir($path) {
		$this->init();
		$this->logEnter(__FUNCTION__, [$path]);

		if ($this->isRootDir($path)) {
			$this->log("refusing to delete \"$path\"", \OCP\Util::DEBUG);
			return $this->logLeave(__FUNCTION__, false);
		}

		$result = false;
		$pathBuilt = $this->buildPath($path);

		// check if the previous rmdir call targeted the same path.
		// see https://github.com/owncloud/core/issues/38923
		// we'll just disable the logger, the request will still go through
		static $previousCallPath;
		$isSameAsBefore = $previousCallPath === $path;
		$previousCallPath = $path;

		// options for the opendir below
		$opts = [];
		if (!$isSameAsBefore) {
			$opts['logger'] = function ($message, $context) {
				if ($this->logActive) {
					$this->logger->error($message, $context);
				}
			};
		}
		$lastEntryRead;  // to be used in the readFilter to grab the entry information
		$opts['readFilter'] = function ($wrapper, $data) use (&$lastEntryRead) {
			if ($data['name'] === '.' || $data['name'] === '..') {
				return false;
			}

			// filter has access to smbclient_readdir entry, which includes 'type'
			// checking if the entry is a directory will be faster this way.
			// Note that the native "readdir" function doesn't expose this info.
			$lastEntryRead = $data;
			return true;
		};

		try {
			$dir = $this->smbclientWrapper->openDirectory($pathBuilt, $opts);  // no skip of entries
			if ($dir !== false) {
				while (($entry = \readdir($dir)) !== false) {
					$entryRelativePath = "{$path}/{$entry}";
					if ($lastEntryRead['type'] === 'directory') {
						$this->rmdir($entryRelativePath);
					} else {
						$this->smbclientWrapper->removeFile($this->buildPath($entryRelativePath));
					}
				}
				\closedir($dir);
			}
			// if we couldn't open the directory, we still try to remove the folder
			$this->smbclientWrapper->removeFolder($pathBuilt);
			$this->removeFromCache($pathBuilt);
			$result = true;
		} catch (SmbclientWrapperException $e) {
			if ($this->isConnectivityError($e)) {
				$ex = new StorageNotAvailableException($e->getMessage(), $e->getCode(), $e);
				$this->logLeave(__FUNCTION__, $ex);
				throw $ex;
			}
			$this->logSwallow(__FUNCTION__, $e);
		}

		return $this->logLeave(__FUNCTION__, $result);
	}

	public function fopen($path, $mode) {
		$this->init();
		$this->logEnter(__FUNCTION__, [$path, $mode]);

		$result = $this->fopenWithOpts($path, $mode);
		return $this->logLeave(__FUNCTION__, $result);
	}

	public function fopenWithOpts($path, $mode, $opts = []) {
		$this->init();
		$this->logEnter(__FUNCTION__, [$path, $mode]);

		$realMode = \str_replace('b', '', $mode);
		$pathBuilt = $this->buildPath($path);

		// default options. Setup a logger for errors, and let the default buffer size
		// The logger can be disabled by using $opts = ['logger' => null];
		// The closeCallbacks will be handled separately
		$defaultOpts = [];
		$defaultOpts['logger'] = function ($message, $context) {
			if ($this->logActive) {
				$this->logger->error($message, $context);
			}
		};
		$realOpts = $opts + $defaultOpts;
		if ($realMode !== 'r') {
			if (!isset($realOpts['closeCallbacks'])) {
				$realOpts['closeCallbacks'] = [];
			}
			// we'll have to remove the entry from the stat cache
			$realOpts['closeCallbacks'][] = function () use ($pathBuilt) {
				unset($this->statCache[$pathBuilt]);
			};
		}

		$result = $this->smbclientWrapper->openFile($pathBuilt, $realMode, $realOpts);
		return $this->logLeave(__FUNCTION__, $result);
	}

	public function unlink($path) {
		$this->init();
		$this->logEnter(__FUNCTION__, [$path]);

		if ($this->isRootDir($path)) {
			$this->log("refusing to unlink \"$path\"", \OCP\Util::DEBUG);
			return $this->logLeave(__FUNCTION__, false);
		}

		$result = false;
		try {
			if ($this->is_dir($path)) {
				$result = $this->rmdir($path);
			} else {
				$pathBuilt = $this->buildPath($path);
				$this->smbclientWrapper->removeFile($pathBuilt);
				unset($this->statCache[$pathBuilt]);
				$result = true;
			}
		} catch (SmbclientWrapperException $e) {
			if ($this->isConnectivityError($e)) {
				$ex = new StorageNotAvailableException($e->getMessage(), $e->getCode(), $e);
				$this->logLeave(__FUNCTION__, $ex);
				throw $ex;
			}
			$this->logSwallow(__FUNCTION__, $e);
		}
		return $this->logLeave(__FUNCTION__, $result);
	}

	public function touch($path, $time=null) {
		$this->init();
		$this->logEnter(__FUNCTION__, [$path, $time]);

		$result = false;
		$pathBuilt = $this->buildPath($path);

		$opts['logger'] = function ($message, $context) {
			if ($this->logActive) {
				$this->logger->error($message, $context);
			}
		};
		// Do not change the mtime for now
		$handler = $this->smbclientWrapper->openFile($pathBuilt, 'c', $opts);
		if ($handler !== false) {
			\fclose($handler);
			$result = true;
		}
		return $this->logLeave(__FUNCTION__, $result);
	}

	public function hasUpdated($path, $time) {
		$this->logEnter(__FUNCTION__, [$path, $time]);
		$actualTime = $this->filemtime($path);
		$result = $actualTime > $time;
		return $this->logLeave(__FUNCTION__, $result);
	}

	protected function rawStat($path) {
		// Do not init here. A public method should have called init already
		$this->logEnter(__FUNCTION__, [$path]);

		$pathBuilt = $this->buildPath($path);
		if (!isset($this->statCache[$pathBuilt])) {
			try {
				if ($this->parseAttrMode === 'none' || $this->parseAttrMode === 'stat') {
					// if 'none' we still need to retrieve size and mtime
					$statInfo = SMBStatInfo::parseStat($this->smbclientWrapper, $pathBuilt);
				} else {
					$statInfo = SMBStatInfo::parseDosAttrs($this->smbclientWrapper, $pathBuilt);
				}
				$this->statCache[$pathBuilt] = $statInfo;
			} catch (SmbclientWrapperException $e) {
				$this->logLeave(__FUNCTION__, $e);
				throw $e;
			}
		}
		return $this->logLeave(__FUNCTION__, $this->statCache[$pathBuilt]);
	}

	public function stat($path) {
		$this->init();
		$this->logEnter(__FUNCTION__, [$path]);

		$result = false;
		try {
			$cachedStatInfo = $this->rawStat($path);
			$result = [
				'size' => $cachedStatInfo->getSize(),
				'mtime' => $cachedStatInfo->getMtime(),
				'type' => $cachedStatInfo->getType()
			];
		} catch (SmbclientWrapperException $e) {
			if ($this->isConnectivityError($e)) {
				$ex = new StorageNotAvailableException($e->getMessage(), $e->getCode(), $e);
				$this->logLeave(__FUNCTION__, $ex);
				throw $ex;
			}
			$this->logSwallow(__FUNCTION__, $e);
		}

		return $this->logLeave(__FUNCTION__, $result);
	}

	public function file_exists($path) {
		$this->init();
		$this->logEnter(__FUNCTION__, [$path]);

		$result = false;
		try {
			$cachedStatInfo = $this->rawStat($path);
			$result = true;
		} catch (SmbclientWrapperException $e) {
			if ($this->isConnectivityError($e)) {
				$ex = new StorageNotAvailableException($e->getMessage(), $e->getCode(), $e);
				$this->logLeave(__FUNCTION__, $ex);
				throw $ex;
			}
			$this->logSwallow(__FUNCTION__, $e);
		}
		return $this->logLeave(__FUNCTION__, $result);
	}

	public function filetype($path) {
		$this->init();
		$this->logEnter(__FUNCTION__, [$path]);

		$result = false;
		try {
			$cachedStatInfo = $this->rawStat($path);
			$result = $cachedStatInfo->getType();
		} catch (SmbclientWrapperException $e) {
			if ($this->isConnectivityError($e)) {
				$ex = new StorageNotAvailableException($e->getMessage(), $e->getCode(), $e);
				$this->logLeave(__FUNCTION__, $ex);
				throw $ex;
			}
			$this->logSwallow(__FUNCTION__, $e);
		}
		return $this->logLeave(__FUNCTION__, $result);
	}

	public function rename($source, $target) {
		$this->init();
		$this->logEnter(__FUNCTION__, [$source, $target]);

		if ($this->isRootDir($source) || $this->isRootDir($target)) {
			$this->log("refusing to rename \"$source\" to \"$target\"", \OCP\Util::DEBUG);
			return $this->logLeave(__FUNCTION__, false);
		}

		$sourcePath = $this->buildPath($source);
		$targetPath = $this->buildPath($target);

		$result = false;
		try {
			$this->smbclientWrapper->renamePath($sourcePath, $targetPath);
			$result = true;
			$this->removeFromCache($sourcePath);
			$this->removeFromCache($targetPath);
		} catch (SmbclientWrapperException $e) {
			if ($this->isConnectivityError($e)) {
				$ex = new StorageNotAvailableException($e->getMessage(), $e->getCode(), $e);
				$this->logLeave(__FUNCTION__, $ex);
				throw $ex;
			}

			switch ($e->getCode()) {
				case 17:  // target file exists, so we have to delete it and retry
				case 22:  // some servers seem to return an error code 22 instead of the expected 17
					$this->logSwallow(__FUNCTION__, $e);
					if ($this->unlink($path)) {
						try {
							$this->smbclientWrapper->renamePath($sourcePath, $targetPath);
							$result = true;
							$this->removeFromCache($sourcePath);
							$this->removeFromCache($targetPath);
						} catch (SmbclientWrapperException $e2) {
							if ($this->isConnectivityError($e2)) {
								$ex = new StorageNotAvailableException($e2->getMessage(), $e2->getCode(), $e2);
								$this->logLeave(__FUNCTION__, $ex);
								throw $ex;
							}
						}
					} else {
						$result = false;
					}
					break;
				default:
					$this->logSwallow(__FUNCTION__, $e);
					$result = false;
					break;
			}
		}
		return $this->logLeave(__FUNCTION__, $result);
	}

	protected function getAclPermissions($path) {
		if ($this->permissionManagerName === '') {
			// if no permission manager has been configured, the default one won't check for
			// permissions, so speed up returning false directly
			return false;
		}

		if (isset($this->cachedAclPermissions[$path])) {
			return $this->cachedAclPermissions[$path];
		}

		// we'll cache only one item, mainly for consecutive "isReadable" + "isUpdateable"...
		$this->cachedAclPermissions = [];

		try {
			$this->cachedAclPermissions[$path] = $this->permissionManager->getACLPermissions(
				Utils::conditionalDomainPlusUsername($this->domain, $this->user),
				$this->buildPath($path)
			);
		} catch (PermissionManagerException $ex) {
			throw new StorageNotAvailableException("ACL couldn't be fetched: ". $ex->getMessage(), 0, $ex);
		}
		return $this->cachedAclPermissions[$path];
	}

	public function isReadable($path) {
		$this->init();
		$this->logEnter(__FUNCTION__, [$path]);

		if ($this->isRootDir($path)) {
			// root path always readable
			return $this->logLeave(__FUNCTION__, true);
		}

		$isReadable = true;
		if ($this->parseAttrMode !== 'none') {
			try {
				$info = $this->rawStat($path);  // StorageNotAvailable might be thrown
				if ($this->parseAttrMode === 'getxattr' || ($this->parseAttrMode === 'stat' && $info->getType() === 'file')) {
					// if it's a directory and the parseMode is "stat", ignore
					$isReadable = !$info->isHidden();
				}
			} catch (SmbclientWrapperException $e) {
				if ($this->isConnectivityError($e)) {
					$ex = new StorageNotAvailableException($e->getMessage(), $e->getCode(), $e);
					$this->logLeave(__FUNCTION__, $ex);
					throw $ex;
				}
				$this->logSwallow(__FUNCTION__, $e);
				return $this->logLeave(__FUNCTION__, false);
			}
		}

		$aclPermissions = $this->getAclPermissions($path);
		if ($aclPermissions !== false) {
			// aclPermissions['read'] might be:
			// * true if the user is allowed to read
			// * false if the user is EXPLICITLY denied to read
			// * null if the user isn't present in the ACL, usually meaning an implicit deny
			$isReadable = $isReadable && ($aclPermissions['read'] === true);
		}
		return $this->logLeave(__FUNCTION__, $isReadable);
	}

	public function isCreatable($path) {
		$this->init();
		$this->logEnter(__FUNCTION__, [$path]);

		$isCreatable = true;
		if ($this->parseAttrMode !== 'none') {
			try {
				$info = $this->rawStat($path);  // StorageNotAvailable might be thrown
				if ($this->parseAttrMode === 'getxattr' || ($this->parseAttrMode === 'stat' && $info->getType() === 'file')) {
					$isCreatable = !$info->isHidden() && (!$info->isReadonly() || $info->getType() === 'dir');
				}
			} catch (SmbclientWrapperException $e) {
				if ($this->isConnectivityError($e)) {
					$ex = new StorageNotAvailableException($e->getMessage(), $e->getCode(), $e);
					$this->logLeave(__FUNCTION__, $ex);
					throw $ex;
				}
				$this->logSwallow(__FUNCTION__, $e);
				return $this->logLeave(__FUNCTION__, false);
			}
		}

		$aclPermissions = $this->getAclPermissions($path);
		if ($aclPermissions !== false) {
			// aclPermissions['write'] might be:
			// * true if the user is allowed to write
			// * false if the user is EXPLICITLY denied to write
			// * null if the user isn't present in the ACL, usually meaning an implicit deny
			$isCreatable = $isCreatable && ($aclPermissions['write'] === true);
		}

		return $this->logLeave(__FUNCTION__, $isCreatable);  // only dirs can be creatable
	}

	public function isUpdatable($path) {
		$this->init();
		$this->logEnter(__FUNCTION__, [$path]);

		if ($this->isRootDir($path)) {
			// root path never updatable -> to prevent renaming
			return $this->logLeave(__FUNCTION__, false);
		}

		$isUpdatable = true;
		if ($this->parseAttrMode !== 'none') {
			try {
				$info = $this->rawStat($path);  // StorageNotAvailable might be thrown
				// following windows behaviour for read-only folders: they can be written into
				// (https://support.microsoft.com/en-us/kb/326549 - "cause" section)
				if ($this->parseAttrMode === 'getxattr' || ($this->parseAttrMode === 'stat' && $info->getType() === 'file')) {
					$isUpdatable = !$info->isHidden() && (!$info->isReadonly() || $info->getType() === 'dir');
				}
			} catch (SmbclientWrapperException $e) {
				if ($this->isConnectivityError($e)) {
					$ex = new StorageNotAvailableException($e->getMessage(), $e->getCode(), $e);
					$this->logLeave(__FUNCTION__, $ex);
					throw $ex;
				}
				$this->logSwallow(__FUNCTION__, $e);
				return $this->logLeave(__FUNCTION__, false);
			}
		}

		$aclPermissions = $this->getAclPermissions($path);
		if ($aclPermissions !== false) {
			// aclPermissions['write'] might be:
			// * true if the user is allowed to write
			// * false if the user is EXPLICITLY denied to write
			// * null if the user isn't present in the ACL, usually meaning an implicit deny
			$isUpdatable = $isUpdatable && ($aclPermissions['write'] === true);
		}
		return $this->logLeave(__FUNCTION__, $isUpdatable);
	}

	public function isDeletable($path) {
		$this->init();
		$this->logEnter(__FUNCTION__, [$path]);

		if ($this->isRootDir($path)) {
			// root path never deletable
			return $this->logLeave(__FUNCTION__, false);
		}

		$isDeletable = true;
		if ($this->parseAttrMode !== 'none') {
			try {
				$info = $this->rawStat($path);  // StorageNotAvailable might be thrown
				if ($this->parseAttrMode === 'getxattr' || ($this->parseAttrMode === 'stat' && $info->getType() === 'file')) {
					$isDeletable = !$info->isHidden() && !$info->isReadonly();
				}
			} catch (SmbclientWrapperException $e) {
				if ($this->isConnectivityError($e)) {
					$ex = new StorageNotAvailableException($e->getMessage(), $e->getCode(), $e);
					$this->logLeave(__FUNCTION__, $ex);
					throw $ex;
				}
				$this->logSwallow(__FUNCTION__, $e);
				return $this->logLeave(__FUNCTION__, false);
			}
		}

		$aclPermissions = $this->getAclPermissions($path);
		if ($aclPermissions !== false) {
			// aclPermissions['delete'] might be:
			// * true if the user is allowed to delete
			// * false if the user is EXPLICITLY denied to delete
			// * null if the user isn't present in the ACL, usually meaning an implicit deny
			$isDeletable = $isDeletable && ($aclPermissions['delete'] === true);
		}
		return $this->logLeave(__FUNCTION__, $isDeletable);
	}

	/**
	 * Need direct access to the share because the default function will provoke infinite recursion
	 * in this particular case
	 */
	public function test($personal = false, $testOnly = false) {
		$this->logEnter(__FUNCTION__, [$personal, $testOnly]);
		$this->validateConfiguration();

		if ($testOnly) {
			return $this->logLeave(__FUNCTION__, $this->connectionTestWithoutReset());
		}
		return $this->logLeave(__FUNCTION__, $this->connectionTestWithReset());
	}

	/**
	 * Test the connection without resetting the password
	 */
	private function connectionTestWithoutReset() {
		$this->logEnter(__FUNCTION__, []);
		try {
			$info = $this->rawStat('');
		} catch (SmbclientWrapperException $e) {
			if ($this->isConnectivityError($e)) {
				$ex = new StorageNotAvailableException($e->getMessage(), $e->getCode(), $e);
				$this->logLeave(__FUNCTION__, $ex);
				throw $ex;
			}
			$this->logSwallow(__FUNCTION__, $e);
			return $this->logLeave(__FUNCTION__, false);
		}

		return $this->logLeave(__FUNCTION__, true);
	}

	/**
	 * Test the connection and reset the password if needed
	 */
	private function connectionTestWithReset() {
		$this->logEnter(__FUNCTION__, []);
		try {
			$info = $this->rawStat('');
			return $this->logLeave(__FUNCTION__, true);
		} catch (SmbclientWrapperException $e) {
			if ($e->getCode() === 13 && $this->password !== '') {
				// FIXME: We need credential information to skip the password reset if login credentials
				// are used or make sure login credentials are reset too
				Utils::resetPassword($this);
				// notify all WND objects to reset the password if needed
				// this object will also be notified
				$this->wndNotifier->notifyChange($this, WNDNotifier::NOTIFY_PASSWORD_REMOVAL);
			}
			$this->logLeave(__FUNCTION__, $e);
			throw $e;
		}
	}

	/**
	 * receive notification from a WND object. This might remove the current password if host and
	 * user are the same
	 */
	public function receiveNotificationFrom(WND $other, $changeType) {
		$this->log('notification from ' . $other->getHost() . ' ' . $other->getUser() .' to ' . $this->getHost() . ' ' . $this->getUser(), \OCP\Util::WARN);
		if ($changeType === WNDNotifier::NOTIFY_PASSWORD_REMOVAL &&
				$this->getHost() === $other->getHost() &&
				$this->getUser() === $other->getUser()) {
			$this->password = '';
			$this->isInitialized = false;
		}
	}

	/**
	 * Add information about the mount point in the ownCloud's FS where this storage
	 * will be used. This is expected to be the same as what is configured in the
	 * mount point configuration (it should be "WindowsNetworkDrive" by default).
	 * Note that the same storage might be accesible from different mount points:
	 * mounts "WND1" and "WND2" might use the same storage if the storage configuration
	 * and / or the storage id is the same for both mounts.
	 * In addition, associated the user with that particular mount point. This means that
	 * if the user access to that mount point, this storage object will be used.
	 *
	 * This is intended to be called only by the storage factory, not in any other place.
	 * @param string $mountPoint the mount point configured.
	 * @param IUser $user the user associated with that mount point
	 */
	public function addMountWithUser(string $mountPoint, IUser $user) {
		if (!isset($this->mountsWithUsers[$mountPoint])) {
			$this->mountsWithUsers[$mountPoint] = [];
		}
		$this->mountsWithUsers[$mountPoint][] = $user;
	}

	/**
	 * Get a map whose keys are the mount point associated to this storages and the values
	 * are a list of users who can access to this storage via the mount point.
	 *
	 * It's common that a mount point has multiple users (the mount point name is the same
	 * for all the users), but it might be possible that some users have a different mount
	 * point
	 *
	 * @return array<string, IUser[]> a mountPoint => userList map
	 */
	public function getMountsWithUsers() {
		return $this->mountsWithUsers;
	}

	/**
	 *  Validate the configuration params, which has been provided
	 *  @throws StorageNotAvailableException if the configuration is invalid
	 *  @return true if configuration is valid
	 */
	public function validateConfiguration() {
		if (str_contains($this->getRoot(), '\\') === true) {
			throw new StorageNotAvailableException('"\" Backslash characters not allowed in root');
		}

		if (str_contains($this->getShareName(), '\\') === true) {
			throw new StorageNotAvailableException('"\" Backslash characters not allowed in share name');
		}

		if ($this->getPassword() === '' && $this->getUser() !== '') {
			throw new StorageNotAvailableException('Password required when username given');
		}

		return true;
	}

	protected function isConnectivityError(SmbclientWrapperException $e) {
		return \in_array($e->getCode(), [110, 111, 113], true);
	}

	protected function isRootDir($path) {
		return $path === '' || $path === '/' || $path === '.';
	}

	protected function removeFromCache($path) {
		// TODO The CappedCache does not really clear by prefix. It just clears all.
		'@phan-var \OC\Cache\CappedMemoryCache $this->statCache';
		$this->statCache->clear("$path/");
		unset($this->statCache[$path]);
	}

	/**
	 * @param string $message
	 */
	protected function log($message, $level, $context = []) {
		if ($this->logActive) {
			$this->logger->log($level, $message, $context + ['app' => 'wnd']);
		}
	}

	protected function logEnter($funcName, $params) {
		if ($this->logActive) {
			$this->logger->debug("enter: $funcName, params = ".\json_encode($params, true), ['app' => 'wnd']);
		}
	}

	protected function logLeave($funcName, $result) {
		if (!$this->logActive) {
			//don't bother building log strings
			return $result;
		} elseif ($result === true) {
			$this->logger->debug("leave: $funcName, return true", ['app' => 'wnd']);
		} elseif ($result === false) {
			$this->logger->debug("leave: $funcName, return false", ['app' => 'wnd']);
		} elseif (\is_string($result)) {
			$this->logger->debug("leave: $funcName, return '$result'", ['app' => 'wnd']);
		} elseif (\is_resource($result)) {
			$this->logger->debug("leave: $funcName, return resource", ['app' => 'wnd']);
		} elseif ($result instanceof \Exception) {
			$message = "leave: $funcName, throw " .
				\get_class($result) .
				" - code: {$result->getCode()} message: {$result->getMessage()} trace: {$result->getTraceAsString()}";
			$this->logger->debug($message, ['app' => 'wnd']);
		} else {
			$this->logger->debug("leave: $funcName, return " . \json_encode($result, true), ['app' => 'wnd']);
		}
		return $result;
	}

	protected function logSwallow($funcName, \Exception $exception) {
		if ($this->logActive) {
			$message = "$funcName swallowing " .
				\get_class($exception) .
				" - code: {$exception->getCode()} message: {$exception->getMessage()} trace: {$exception->getTraceAsString()}";
			$this->logger->debug($message, ['app' => 'wnd']);
		}
	}
}
