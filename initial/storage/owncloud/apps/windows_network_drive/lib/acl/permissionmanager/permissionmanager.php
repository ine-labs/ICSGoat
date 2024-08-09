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

use OCA\windows_network_drive\lib\acl\aclfetcher\exceptions\RefusedException;
use OCA\windows_network_drive\lib\acl\aclfetcher\exceptions\ACLFetcherException;
use OCA\windows_network_drive\lib\acl\aclfetcher\exceptions\ACLFetcherConnectivityException;
use OCA\windows_network_drive\lib\acl\permissionmanager\PermissionManagerException;
use OCA\windows_network_drive\lib\acl\permissionmanager\IPermissionManager;
use OCA\windows_network_drive\lib\acl\IACLFetcher;
use OCA\windows_network_drive\lib\acl\IGroupMembership;
use OCA\windows_network_drive\lib\acl\ACLOperator;
use OCP\ILogger;
use OCP\Diagnostics\IEventLogger;

class PermissionManager implements IPermissionManager {
	/** @var IACLFetcher */
	private $aclFetcher;
	/** @var IGroupMembership */
	private $groupMembership;
	/** @var ACLOperator */
	private $aclOperator;
	/** @var ILogger */
	private $logger;
	/** @var IEventLogger */
	private $eventLogger;

	/** @var string */
	private $name;

	/**
	 * @param string $name the instance name that will be used to identify this instance. This name might
	 * be used to identify instances with the same configuration. Note that there is no control over the
	 * name, and it can be duplicated or empty.
	 * @param IACLFetcher $aclFetcher the aclFetcher implementation to fetch the ACL
	 * @param IGroupMembership $groupMembership the groupMembership implementation to know the members
	 * of the SMB groups
	 * @param ACLOperator $aclOperator the operator for the ACLs
	 */
	public function __construct(
		$name,
		IACLFetcher $aclFetcher,
		IGroupMembership $groupMembership,
		ACLOperator $aclOperator,
		ILogger $logger,
		IEventLogger $eventLogger
	) {
		$this->name = $name;
		$this->aclFetcher = $aclFetcher;
		$this->groupMembership = $groupMembership;
		$this->aclOperator = $aclOperator;
		$this->logger = $logger;
		$this->eventLogger = $eventLogger;
	}

	/**
	 * Get the ACL permissions for a path and a trustee. The trustee must include the domain in order
	 * to match it in the ACL
	 * @param string $trustee the trustee for the ACL, something like "mydomain\myuser"
	 * @param string $path the full SMB path to file or folder
	 * @return array|false false if the aclFetcher refuses to work, or an array with "read", "write"
	 * and "delete" keys, each one with true or false depending on the trustee to have "read", "write"
	 * or "delete" permissions on $path
	 * @throws PermissionManagerException if cannot fetch the ACL
	 */
	public function getACLPermissions($trustee, $path) {
		try {
			$eventId1 = \uniqid('wnd.aclFetcher.getACL');
			$this->eventLogger->start($eventId1, "wnd fetching ACLs on $path");

			$acl = $this->aclFetcher->getACL($path);
		} catch (RefusedException $ex) {
			// we don't know the ACL, so return false
			$this->logger->debug(\get_class($this->aclFetcher) . " refused to work on $path", ['app' => 'wnd']);
			return false;
		} catch (ACLFetcherConnectivityException $ex) {
			// rethrow
			throw new PermissionManagerException("Cannot access to the backend", 0, $ex);
		} catch (ACLFetcherException $ex) {
			// there was an error retrieving the ACL, assume the user doesn't have
			// enough permissions to even read
			$message = $ex->getMessage();
			$code = $ex->getCode();
			$this->logger->debug(\get_class($this->aclFetcher) . " failed to access to $path: $message ($code)", ['app' => 'wnd']);
			return [
				'read' => false,
				'write' => false,
				'delete' => false,
			];
		} finally {
			$this->eventLogger->end($eventId1);
		}

		$aclAsArray = $acl->toConvertedArray();
		$aclAsArray = $aclAsArray['acl'];

		$eventId2 = \uniqid('wnd.aclOperator.getAccountMap');
		$this->eventLogger->start($eventId2, "wnd evaluate permissions for $trustee on $path");
		$permissionsData = $this->aclOperator->evaluatePermissionsForTrustee($trustee, $aclAsArray, $this->groupMembership);
		$this->eventLogger->end($eventId2);

		$this->logger->debug("evaluated permissions for $path: " . \json_encode($permissionsData), ['app' => 'wnd']);

		$permissions = [
			'read' => ($permissionsData['R'] === 'allowed') ? true : false,
			'write' => ($permissionsData['W'] === 'allowed') ? true : false,
			'delete' => ($permissionsData['D'] === 'allowed') ? true : false,
		];
		return $permissions;
	}

	/**
	 * Get the name of this instance. See the constructor method for details.
	 * This is intended to be used just for testing. Do not rely on this method unless
	 * you make sure all the instances are created via PermissionManagerFactory.
	 * @return string the name of this instance
	 */
	public function getInstanceName() {
		return $this->name;
	}
}
