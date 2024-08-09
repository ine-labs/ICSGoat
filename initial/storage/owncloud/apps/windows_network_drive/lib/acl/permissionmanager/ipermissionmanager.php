<?php
/**
 * ownCloud
 *
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 * @copyright Copyright (c) 2020, ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\windows_network_drive\lib\acl\permissionmanager;

interface IPermissionManager {
	/**
	 * Get the ACL permissions for a path and a trustee. The trustee must include the domain in order
	 * to match it in the ACL
	 * @param string $trustee the trustee for the ACL, something like "mydomain\myuser"
	 * @param string $path the full SMB path to file or folder
	 * @return array|false false if the permission manager refuses to work, or an array with "read", "write"
	 * and "delete" keys, each one with true or false depending on the trustee to have "read", "write"
	 * or "delete" permissions on $path
	 * @throws PermissionManagerException if the permission manager can't access to the info
	 */
	public function getACLPermissions($trustee, $path);

	/**
	 * Get the name of this instance. See the constructor method for details.
	 * This is intended to be used just for testing. Do not rely on this method unless
	 * you make sure all the instances are created via PermissionManagerFactory.
	 * @return string the name of this instance
	 */
	public function getInstanceName();
}
