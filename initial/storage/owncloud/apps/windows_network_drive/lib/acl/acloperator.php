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

use OCA\windows_network_drive\lib\acl\groupmembership\MissingGroupException;
use OCA\windows_network_drive\lib\acl\groupmembership\MissingUserException;

class ACLOperator {
	/**
	 * Get the changes between 2 ACL.
	 * An ACL is a list of ACE, each ACE contains the following information:
	 * ['trustee' => 'domain\smbUser', 'mode' => 'allowed', 'flags' => '', 'mask' => 'RWD'],
	 *
	 * An example of ACL could be:
	 * [
	 *  ['trustee' => 'domain\smbUser', 'mode' => 'allowed', 'flags' => '', 'mask' => 'RWD'],
	 *  ['trustee' => 'domain\smbUser2', 'mode' => 'allowed', 'flags' => 'CI|OI', 'mask' => 'RWD'],
	 * ]
	 *
	 * The function will return an array with a list of the added entries and another one with the removed ones
	 * [
	 *  'added' => [$ace1, $ace2],
	 *  'removed' => [$ace3],
	 * ]
	 *
	 * NOTE: The indexes of the returned elements will be kept. This means that the list of both
	 * 'added' and 'removed' keys won't be reindexed, and the indexes shouldn't be trusted. You might
	 * want to reindex both lists outside if needed.
	 *
	 * @param array $oldACL the previous ACL
	 * @param array $newACL the new ACL
	 * @return array ['added' => ACElist, 'removed' => ACElist]. The 'added' and 'removed' keys will always
	 * be present, containing a list that might be empty
	 */
	public function getACEchanges($oldACL, $newACL) {
		$arrayComparatorFunction = function ($array1, $array2) {
			// FIXME: need to consider 'flagsAsInt' and 'maskAsInt' (optionals) as well
			foreach (['trustee', 'mode', 'flags', 'mask'] as $arrayKey) {
				if ($array1[$arrayKey] < $array2[$arrayKey]) {
					return -1;
				} elseif ($array1[$arrayKey] > $array2[$arrayKey]) {
					return 1;
				}
			}
			return 0;
		};

		$added = \array_udiff($newACL, $oldACL, $arrayComparatorFunction);
		$removed = \array_udiff($oldACL, $newACL, $arrayComparatorFunction);

		return [
			'added' => $added,
			'removed' => $removed,
		];
	}

	/**
	 * Gets a double-map trustee -> user and user -> trustee based on the ACL.
	 * Note that the "user -> trustee" depends heavily on the information in the ACL, and it
	 * will likely be incomplete.
	 *
	 * An example of ACL could be:
	 * [
	 *  ['trustee' => 'domain\smbUser', 'mode' => 'allowed', 'flags' => '', 'mask' => 'RWD'],
	 *  ['trustee' => 'domain\smbUser2', 'mode' => 'allowed', 'flags' => 'CI|OI', 'mask' => 'RWD'],
	 * ]
	 *
	 * Note that the "user" might (or might not) contain the domain or any other information
	 * depending on the specific groupMembership implementation.
	 *
	 * @param array $acl a list of ACEs. The list can be an ACL extracted from a security descriptor, a merge
	 * of several ACLs, a difference between several ACLs... it doesn't need to reflect an existing ACL
	 * @param IGroupMembership $groupMembership a group membership instance to know the members of the SMB group
	 * and to adjust the users' name
	 * @return array
	 *  [
	 *    'trusteeToUser' => [
	 *      'mydomain\user1' => ['user1'],
	 *      'mydomain\group1' => ['user1', 'user2'],
	 *    ],
	 *    'userToTrustee' => [
	 *      'user1' => ['mydomain\user1', 'mydomain\group1'],
	 *      'user2' => ['mydomain\group1'],
	 *    ],
	 *  ]
	 */
	public function getAccountMap(array $acl, IGroupMembership $groupMembership) {
		$userList = [
			'trusteeToUser' => [],
			'userToTrustee' => [],
		];

		foreach ($acl as $aceItem) {
			$trustee = $aceItem['trustee'];
			if (isset($userList['trusteeToUser'][$trustee])) {
				// trustee already processed
				continue;
			}

			try {
				// assume the trustee is a group
				$members = $groupMembership->getGroupMembers($trustee);
			} catch (MissingGroupException $e) {
				// if the group doesn't exists assume it's a user
				// fake the user to be a member of a group for easier handling
				$members = [$groupMembership->fixUser($trustee)];
			}

			foreach ($members as $member) {
				if (!isset($userList['trusteeToUser'][$trustee])) {
					$userList['trusteeToUser'][$trustee] = [];
				}
				if (!isset($userList['userToTrustee'][$member])) {
					$userList['userToTrustee'][$member] = [];
				}
				$userList['trusteeToUser'][$trustee][] = $member;
				$userList['userToTrustee'][$member][] = $trustee;
			}
		}
		return $userList;
	}

	/**
	 * Check if the "domain" user can read accordingly to the ACL. The ACL should be extracted from a
	 * security descriptor for accuracy.
	 * It's expected an account map to be provided via the "getAccountMap" function. If no account map
	 * is provided, that method will be automatically called using the ACL used as parameter, as well
	 * as the group membership.
	 * If you use the "getAccountMap" function to get an account map, you must use the same IGroupMembership
	 * instance, otherwise the results won't be the right ones.
	 * The user MUST be present in the accountMap, otherwise we won't be able to know which ACEs
	 * we'll need to check (it might be a member of a group we don't know, or it might be mapped differently),
	 * so we'll return null in this case
	 *
	 * IMPORTANT NOTE: this method isn't considering inherited ACEs and might rely heavily on the
	 * specific ACL order. Consider http://www.ntfs.com/ntfs-permissions-precedence.htm to check how it's
	 * supposed to work
	 *
	 * @param array $acl the ACL from a security descriptor
	 * @param string $user the trustee
	 * @param IGroupMembership $groupMembership
	 * @param array $accountMap the account mapping extracted using the "getAccountMap". The ACL used for the
	 * "getAccountMap" function must contain, at least, the same ACL information as this $acl parameter
	 * (it might contain additional entries)
	 *
	 * @return bool|null true if the user can read, false otherwise. null if the user isn't present
	 * in the accountMap
	 */
	public function userCanRead($acl, $user, IGroupMembership $groupMembership, $accountMap = null) {
		return $this->userCan('R', $acl, $user, $groupMembership, $accountMap);
	}

	/**
	 * Check if the "domain" user can write accordingly to the ACL. The ACL should be extracted from a
	 * security descriptor for accuracy.
	 * @see userCanRead
	 * @param array $acl the ACL from a security descriptor
	 * @param string $user the trustee
	 * @param IGroupMembership $groupMembership
	 * @param array $accountMap the account mapping extracted using the "getAccountMap". The ACL used for the
	 * "getAccountMap" function must contain, at least, the same ACL information as this $acl parameter
	 * (it might contain additional entries)
	 *
	 * @return bool|null true if the user can read, false otherwise. null if the user isn't present
	 * in the accountMap
	 */
	public function userCanWrite($acl, $user, IGroupMembership $groupMembership, $accountMap = null) {
		return $this->userCan('W', $acl, $user, $groupMembership, $accountMap);
	}

	/**
	 * Check if the "domain" user can delete accordingly to the ACL. The ACL should be extracted from a
	 * security descriptor for accuracy.
	 * @see userCanRead
	 * @param array $acl the ACL from a security descriptor
	 * @param string $user the trustee
	 * @param IGroupMembership $groupMembership
	 * @param array $accountMap the account mapping extracted using the "getAccountMap". The ACL used for the
	 * "getAccountMap" function must contain, at least, the same ACL information as this $acl parameter
	 * (it might contain additional entries)
	 *
	 * @return bool|null true if the user can read, false otherwise. null if the user isn't present
	 * in the accountMap
	 */
	public function userCanDelete($acl, $user, IGroupMembership $groupMembership, $accountMap = null) {
		return $this->userCan('D', $acl, $user, $groupMembership, $accountMap);
	}

	private function userCan($maskLetter, $acl, $user, IGroupMembership $groupMembership, $accountMap = null) {
		if ($accountMap === null) {
			$accountMap = $this->getAccountMap($acl, $groupMembership);
		}

		// "fix" the user via group membership so we can search it in the account map
		$user = $groupMembership->fixUser($user);
		if (!isset($accountMap['userToTrustee'][$user])) {
			$trusteesToCheck = [];
			$result = null;  // default result if the trustee can't be found in the account map
		} else {
			$trusteesToCheck = $accountMap['userToTrustee'][$user];
			$result = false;  // default result if the trustee is found
		}

		foreach ($acl as $newACE) {
			$trustee = $newACE['trustee'];
			if (\in_array($trustee, $trusteesToCheck, true) || $trustee === 'EVERYONE') {
				if ($newACE['mode'] === 'denied' && \strpos($newACE['mask'], $maskLetter) !== false) {
					return false;
				}
				if ($newACE['mode'] === 'allowed' && \strpos($newACE['mask'], $maskLetter) !== false) {
					$result = true;
				}
			}
		}
		return $result;
	}

	/**
	 * Evaluate the permissions the trustee should get for the target ACL (or a list of ACEs).
	 * The function will return a map with "R", "W" and "D" keys (read, write and delete) with
	 * the following possible values:
	 * - empty string, if there is no information for the user in the ACL. This usually means an
	 * implicit deny of the permission
	 * - "denied", if the permission has been explicitly denied
	 * - "allowed", if the permission has been expicitly allowed
	 * Note that this function relies on the IGroupMembership to check if a group (if the trustee
	 * in the ACL is a group) has the target user as a member, and so, the permissions for that group
	 * should also be taken into account.
	 * @param string $trusteeUser the trustee (domain + user) to be checked
	 * @param array $acl the ACL information, or the list of ACEs.
	 * @param IGroupMembership $groupMembership
	 * @return array an array containing the permissions for the user accordingly to the ACL, as
	 * explained above
	 */
	public function evaluatePermissionsForTrustee($trusteeUser, array $acl, IGroupMembership $groupMembership) {
		$permissions = ['R' => '', 'W' => '', 'D' => ''];
		foreach ($acl as $aceItem) {
			if (\strpos($aceItem['flags'], 'IO') !== false) {
				// if the entry has the inherit-only flags -> ignore
				continue;
			}

			$trustee = $aceItem['trustee'];

			$shouldCheckTrustee = false;
			try {
				$shouldCheckTrustee = $trustee === 'EVERYONE' ||
						$trusteeUser === $trustee ||
						$groupMembership->isInGroup($trusteeUser, $trustee);
			} catch (MissingGroupException $e) {
				$shouldCheckTrustee = false;
			} catch (MissingUserException $e) {
				// if the trusteeUser is unknown for the groupMembership, ignore and
				// check the next. We can still evaluate exact matches
				$shouldCheckTrustee = false;
			}

			if ($shouldCheckTrustee) {
				foreach ($permissions as $permission => $status) {
					if ($status === 'denied') {
						// if the permission has been denied -> jump over it
						// denied permission always takes precendence
						continue;
					}

					// check the ACE
					if ($aceItem['mode'] === 'denied' && \strpos($aceItem['mask'], $permission) !== false) {
						$permissions[$permission] = 'denied';
					}
					if ($aceItem['mode'] === 'allowed' && \strpos($aceItem['mask'], $permission) !== false) {
						$permissions[$permission] = 'allowed';
					}
				}
			}
		}
		return $permissions;
	}
}
