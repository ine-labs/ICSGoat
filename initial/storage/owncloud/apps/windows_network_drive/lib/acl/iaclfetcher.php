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

namespace OCA\windows_network_drive\lib\acl;

interface IACLFetcher {
	/**
	 * Fetch the ACL for that specific path. The implementation will return a security descriptor
	 *
	 * A RefusedException might be thrown when the implementation refuses to fetch the ACL. If
	 * the ACL is in an external service (DB, SMB server, etc), the implementation shouldn't
	 * contact that server, and throw the exception as soon as possible. The function consumer
	 * shouldn't ask again for the same path, and it must consider the ACL as unknown
	 *
	 * An ACLFetcherException might be thrown when the implementation has problems to fetch the ACL
	 * For connectivity problems an ACLFetcherConnectivityException (subclass of the ACLFetcherException)
	 * must be thrown. This means that the implementation has tried to fetch the ACL but it couldn't, and
	 * the ACL remains unknown; this might be a temporary failure, so we can't make assumptions on the
	 * permissions.
	 *
	 * @param string $path
	 * @return \OCA\windows_network_drive\lib\acl\models\SecurityDescriptor
	 * @throws \OCA\windows_network_drive\lib\acl\aclfetcher\ACLFetcherException if it can't fetch
	 * the ACL for that path
	 * @throws \OCA\windows_network_drive\lib\acl\aclfetcher\ACLFetcherConnectivityException if it
	 * can't fetch the ACL for that path due to connectivity problems
	 * @throws \OCA\windows_network_drive\lib\acl\aclfetcher\RefusedException if the implementation
	 * refuses to work and an ACL won't be retrieved
	 */
	public function getACL($path);
}
