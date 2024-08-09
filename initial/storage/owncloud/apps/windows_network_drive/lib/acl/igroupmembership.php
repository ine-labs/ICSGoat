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

/**
 * Interface to check if a user is a member of a group and also to check the members of the group.
 * This group is a windows / samba group, which might not be related to ownCloud
 */
interface IGroupMembership {
	/**
	 * Get the user ids of the members of the group
	 * @param string $group the windows / samba group id
	 * @return string[] a list with the members of the windows / samba group
	 * @throws OCA\windows_network_drive\lib\acl\groupmembership\MissingGroupException if the group doesn't exists
	 */
	public function getGroupMembers($group);

	/**
	 * Check if that user is a member of that group
	 * @param string $user the windows / samba user id with the domain, something like "mydomain\myuser"
	 * @param string $group the windows / samba group id with the domain, something like "mydomain\mygroup"
	 * @return bool true if the user is in the group, false otherwise
	 * @throws OCA\windows_network_drive\lib\acl\groupmembership\MissingUserException if the user doesn't exists
	 * @throws OCA\windows_network_drive\lib\acl\groupmembership\MissingGroupException if the group doesn't exists
	 */
	public function isInGroup($user, $group);

	/**
	 * Members returned by the "getGroupMembers" might not have domain, or might have always a fixed domain
	 * This function will take a user (the same way the "getGroupMembers" accepts the group), and it will
	 * return a user with the same format as it were a member.
	 * The intention is that this function is called if the "getGroupMembers" fails, then the "failed" group
	 * can be considered a user, so using this function will homogenize the results.
	 * @param string $user a (suspected) user of the windows / samba server
	 * @return string the same user as if it were returned by the "getGroupMembers"
	 */
	public function fixUser($user);
}
