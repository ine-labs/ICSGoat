<?php
/**
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 *
 * @copyright Copyright (c) 2020, ownCloud GmbH
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

namespace OCA\windows_network_drive\lib\acl\permissionmanager;

use OCA\windows_network_drive\lib\acl\permissionmanager\PermissionManager;
use OCA\windows_network_drive\lib\acl\permissionmanager\IPermissionManager;
use OCA\windows_network_drive\lib\acl\permissionmanager\cache\PartitionedCache;

class CachingProxyPermissionManager implements IPermissionManager {
	/** @var PermissionManager */
	private $permissionManager;
	/** @var PartitionedCache */
	private $partitionedCache;
	/** @var int */
	private $instanceId;

	public function __construct(PermissionManager $permissionManager, PartitionedCache $partitionedCache) {
		$this->permissionManager = $permissionManager;
		$this->partitionedCache = $partitionedCache;
	}

	public function getACLPermissions($trustee, $path) {
		// check in cache
		if ($this->instanceId === null) {
			$this->instanceId = \intval(\spl_object_hash($this), 16);
		}

		$cacheKey = "$trustee::$path";
		$value = $this->partitionedCache->get($this->instanceId, $cacheKey);
		if ($value !== null) {
			return $value;
		}

		// if not in cache, ask the permission manager
		$value = $this->permissionManager->getACLPermissions($trustee, $path);
		// try to cache the result. It doesn't matter if it isn't cached in the end
		$this->partitionedCache->set($this->instanceId, $cacheKey, $value);

		return $value;
	}

	public function getInstanceName() {
		return "cacheProxied:" . $this->permissionManager->getInstanceName();
	}

	public function __destruct() {
		if ($this->instanceId !== null) {
			$this->partitionedCache->removePartition($this->instanceId);
		}
	}
}
