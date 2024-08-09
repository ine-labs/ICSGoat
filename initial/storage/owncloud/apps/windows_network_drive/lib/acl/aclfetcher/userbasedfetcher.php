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

namespace OCA\windows_network_drive\lib\acl\aclfetcher;

use OCA\windows_network_drive\lib\acl\IACLFetcher;
use OCA\windows_network_drive\lib\smbwrapper\SmbclientWrapper;
use OCA\windows_network_drive\lib\smbwrapper\SmbclientWrapperException;
use OCA\windows_network_drive\lib\acl\aclfetcher\exceptions\ACLFetcherException;
use OCA\windows_network_drive\lib\acl\aclfetcher\exceptions\ACLFetcherConnectivityException;

class UserBasedFetcher implements IACLFetcher {
	/** @var SmbclientWrapper */
	private $wrapper;

	/**
	 * @param SmbclientWrapper $wrapper
	 */
	public function __construct(SmbclientWrapper $wrapper) {
		$this->wrapper = $wrapper;
	}

	/**
	 * @inheritDoc
	 */
	public function getACL($path) {
		try {
			return $this->wrapper->getSecurityDescriptor($path);
		} catch (SmbclientWrapperException $ex) {
			if (\in_array($ex->getCode(), [110, 111, 113])) {
				// known codes for connectivity problems
				throw new ACLFetcherConnectivityException("cannot get the ACL: " . $ex->getMessage(), $ex->getCode(), $ex);
			}
			throw new ACLFetcherException("cannot get the ACL: " . $ex->getMessage(), $ex->getCode(), $ex);
		}
	}
}
