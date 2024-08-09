<?php
/**
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
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

use OCA\windows_network_drive\lib\acl\aclfetcher\NullFetcher;
use OCA\windows_network_drive\lib\acl\aclfetcher\UserBasedFetcher;
use OCA\windows_network_drive\lib\acl\groupmembership\OCLDAPMembership;
use OCA\windows_network_drive\lib\acl\permissionmanager\cache\PartitionedCache;
use OCA\windows_network_drive\lib\acl\ACLOperator;
use OCA\windows_network_drive\lib\smbwrapper\SmbclientWrapper;

class PermissionManagerFactory {
	/** @var PartitionedCache */
	private $cache;
	/**
	 * Create a new permission manager instance by name. The required parameter might vary
	 * depending on the type of the manager.
	 * Available names are:
	 *  - "nullPermissionManager"
	 *  - "ocLdapPermissionManager"
	 * "nullPermissionManager" will be used by default.
	 *
	 * For the parameters:
	 *  - "nullPermissionManager" doesn't require anything. The parameters will be ignored
	 *  - "ocLdapPermissionManager" requires parameters to connect to the SMB server
	 *    [
	 *      'host' => 'myhost',
	 *      'share' => 'sharename',
	 *      'domain' => 'workgroup',
	 *      'user' => 'username',
	 *      'password' => 'password',
	 *    ]
	 * @param string $name the name of the permission manager.
	 * @param array $params the parameters required to create the instance
	 * @return OCA\windows_network_drive\lib\acl\IPermissionManager|false the created instance
	 * properly configured based on the name or false if some require parameters are missing
	 * (depending on the name)
	 */
	public function createPermissionManagerByName($name, array $params = []) {
		switch ($name) {
			case 'nullPermissionManager':
				return $this->getNullPermissionManager();
				break;
			case 'ocLdapPermissionManager':
				return $this->getOcLdapPermissionManager($params);
				break;
			default:
				return $this->getNullPermissionManager();
				break;
		}
	}

	private function getNullPermissionManager() {
		return new PermissionManager(
			"nullPermissionManager",
			new NullFetcher(),
			new OCLDAPMembership(
				\OC::$server->getUserManager(),
				\OC::$server->getGroupManager(),
				\OC::$server->getConfig()
			),
			new ACLOperator(),
			\OC::$server->getLogger(),
			\OC::$server->getEventLogger()
		);
	}

	private function getOcLdapPermissionManager(array $params) {
		$smbclientWrapper = null;
		$config = \OC::$server->getConfig();

		if (isset($params['smbclientWrapper'])) {
			$smbclientWrapper = $params['smbclientWrapper'];
		} elseif (isset($params['host'], $params['share'], $params['domain'], $params['user'], $params['password'])) {
			$smbclientWrapperOpts = [
				SMBCLIENT_OPT_TIMEOUT => $config->getSystemValue('wnd.connector.opts.timeout', SmbclientWrapper::OPTS_TIMEOUT),
			];
			$smbclientWrapper = new SmbclientWrapper(
				$params['host'],
				$params['share'],
				$params['domain'],
				$params['user'],
				$params['password'],
				$smbclientWrapperOpts
			);
		} else {
			return false;
		}

		if ($this->cache === null) {
			$cacheSize = $config->getSystemValue('wnd.permissionmanager.cache.size', 512);
			if (!\is_int($cacheSize) || $cacheSize < 0) {
				// ensure it's a number
				$cacheSize = 512;
			}
			$this->cache = new PartitionedCache($cacheSize);
		}

		$permissionManager = new PermissionManager(
			"ocLdapPermissionManager",
			new UserBasedFetcher(
				$smbclientWrapper
			),
			new OCLDAPMembership(
				\OC::$server->getUserManager(),
				\OC::$server->getGroupManager(),
				\OC::$server->getConfig()
			),
			new ACLOperator(),
			\OC::$server->getLogger(),
			\OC::$server->getEventLogger()
		);
		return new CachingProxyPermissionManager($permissionManager, $this->cache);
	}
}
