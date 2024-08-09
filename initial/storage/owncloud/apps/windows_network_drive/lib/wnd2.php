<?php
/**
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 *
 * @copyright Copyright (c) 2021, ownCloud GmbH
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

use OCA\windows_network_drive\lib\storage\WNDCacheWrapper;
use OCA\windows_network_drive\lib\Utils;

/**
 * The file ids will be handled by the service account, this means that the
 * files will be shared by the users using this storage (shared comments, tags,
 * and other features relying on the file id)
 * For write access nd visibility, the specific user's account will be used
 *
 * Differences with WND:
 * - read operations will use a service account while write operations will use
 * a user's specific account
 * - for permissions, it will use the service account
 * - a custom cache will be used to restrict visibility per user, depending on
 * whether the user has access to the file or folder.
 *
 * Known missing operations:
 * - copy -> relying on common.php, it will call mkdir / fopen(..., 'w')
 * - copyFromStorage -> same as above
 * - moveFromStorage -> relying on common.php, will call to rename or copyFromStorage
 */
class WND2 extends WND {
	/** @var WND */
	private $userSpecificSmb;

	public function __construct($params) {
		$this->userSpecificSmb = new WND($params);

		$params2 = $params;
		$params2['user'] = $params['service-account'];
		$params2['password'] = $params['service-account-password'];

		// assume the service password is encrypted
		// if decryption fails, consider them unencrypted
		$decryptedPassword = Utils::decryptPassword($params['service-account-password']);

		if ($decryptedPassword !== false) {
			$params2['password'] = $decryptedPassword;
		}

		parent::__construct($params2);
	}

	public function getId(): string {
		$username = Utils::conditionalDomainPlusUsername($this->getDomain(), $this->getUser());
		$id = 'wnd2::' . $username . '@' . $this->getHost() . '/' . $this->getShareName() . '/' . $this->getRoot();
		return $id;
	}

	public function getCache($path = '', $storage = null) {
		$parentCache = parent::getCache($path, $storage);
		$memcacheFactory = \OC::$server->getMemCacheFactory();
		$memcache = $memcacheFactory->create('wnd2files');  // create is the only method available in the public interface
		$config = \OC::$server->getConfig();
		return new WNDCacheWrapper($parentCache, $this->userSpecificSmb, $memcache, $config);
	}

	public function unlink($path) {
		$result = $this->userSpecificSmb->unlink($path);
		if ($result) {
			unset($this->statCache[$this->buildPath($path)]);
		}
		return $result;
	}

	public function fopen($path, $mode) {
		$fullPath = $this->buildPath($path);
		$opts = [];
		if ($mode !== 'r' && $mode !== 'rb') {
			$opts['closeCallbacks'] = [];
			$opts['closeCallbacks'][] = function () use ($fullPath) {
				unset($this->statCache[$fullPath]);
			};
		}

		$result = $this->userSpecificSmb->fopenWithOpts($path, $mode, $opts);
		return $result;
	}

	public function rmdir($path) {
		$result = $this->userSpecificSmb->rmdir($path);
		if ($result) {
			$buildPath = $this->buildPath($path);
			$this->statCache->clear("{$buildPath}/");
			unset($this->statCache[$buildPath]);
		}
		return $result;
	}

	public function mkdir($path) {
		return $this->userSpecificSmb->mkdir($path);
		// no cache manipulation here
	}

	public function touch($path, $time = null) {
		return $this->userSpecificSmb->touch($path, $time);
		// no cache manipulation here
	}

	public function rename($source, $target) {
		$result = $this->userSpecificSmb->rename($source, $target);
		if ($result) {
			$buildSource = $this->buildPath($source);
			$buildTarget = $this->buildPath($target);
			$this->statCache->clear("{$buildSource}/");
			$this->statCache->clear("{$buildTarget}/");
			unset($this->statCache[$buildSource], $this->statCache[$buildTarget]);
		}
		return $result;
	}
}
