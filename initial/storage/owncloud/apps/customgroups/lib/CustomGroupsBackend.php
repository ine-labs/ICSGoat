<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH
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

namespace OCA\CustomGroups;

/**
 * Group backend for custom groups for integration with core
 */
class CustomGroupsBackend implements \OCP\GroupInterface {
	public const GROUP_ID_PREFIX = 'customgroup_';

	/**
	 * Custom groups handler
	 *
	 * @var CustomGroupsDatabaseHandler
	 */
	private $handler;

	/**
	 * Constructor
	 *
	 * @param CustomGroupsDatabaseHandler $handler custom groups handler
	 */
	public function __construct(
		CustomGroupsDatabaseHandler $handler
	) {
		$this->handler = $handler;
	}

	/**
	 * Checks if backend implements actions.
	 *
	 * @param int $actions bitwise-or'ed actions
	 * @return boolean
	 */
	public function implementsActions($actions) {
		return ($actions & (self::GROUP_DETAILS | self::DELETE_GROUP)) !== 0;
	}

	/**
	 * Checks whether the user is member of a group or not.
	 *
	 * @param string $uid uid of the user
	 * @param string $gid gid of the group
	 * @return boolean true if user is in group, false otherwise
	 */
	public function inGroup($uid, $gid) {
		$uri = $this->extractUri($gid);
		if ($uri === null) {
			return false;
		}

		return $this->handler->inGroupByUri($uid, $uri);
	}

	/**
	 * Get all groups a user belongs to
	 *
	 * @param string $uid Name of the user
	 * @return array an array of group names
	 */
	public function getUserGroups($uid) {
		$memberInfos = $this->handler->getUserMemberships($uid, null);
		return \array_map(function ($memberInfo) {
			return $this->formatGroupId($memberInfo['uri']);
		}, $memberInfos);
	}

	/**
	 * Returns a list with all groups.
	 * The search string will match any part of the display name
	 * field.
	 *
	 * @param string $search search string
	 * @param int $limit limit or -1 to disable
	 * @param int $offset offset
	 * @return array an array of group names
	 */
	public function getGroups($search = '', $limit = -1, $offset = 0) {
		$groups = $this->handler->searchGroups(new Search($search, $offset, $limit));
		return \array_map(function ($groupInfo) {
			return $this->formatGroupId($groupInfo['uri']);
		}, $groups);
	}

	/**
	 * Checks if a group exists
	 *
	 * @param string $gid group id
	 * @return bool true if the group exists, false otherwise
	 */
	public function groupExists($gid) {
		return $this->getGroupDetails($gid) !== null;
	}

	/**
	 * Returns the info for a given group.
	 *
	 * @param string $gid group id
	 * @return array|null group info or null if not found
	 */
	public function getGroupDetails($gid) {
		$uri = $this->extractUri($gid);
		if ($uri === null) {
			return null;
		}

		$group = $this->handler->getGroupByUri($uri);
		if ($group === null) {
			return null;
		}
		return [
			'gid' => $this->formatGroupId($group['uri']),
			'displayName' => $group['display_name'],
		];
	}

	/**
	 * Returns all users in a custom group.
	 *
	 * @param string $gid group id
	 * @param string $search search string
	 * @param int $limit limit
	 * @param int $offset offset
	 * @return array empty array
	 */
	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		$uri = $this->extractUri($gid);
		if ($uri === null) {
			return [];
		}

		$group = $this->handler->getGroupByUri($uri);
		if ($group === null) {
			return null;
		}

		// not exposed to regular user management
		$search = new Search($search, $offset, $limit);
		$memberInfo = $this->handler->getGroupMembers($group['group_id'], $search);
		return \array_map(function ($memberInfo) {
			return $memberInfo['user_id'];
		}, $memberInfo);
	}

	/**
	 * Extracts the uri from the group id string with
	 * the format "customgroup_$uri"
	 *
	 * @param string $gid group id in format "customgroup_$uri"
	 * @return string|null extracted uri or null if the format did not match
	 */
	private function extractUri($gid) {
		$len = \strlen(self::GROUP_ID_PREFIX);
		$prefixPart = \substr($gid, 0, $len);
		if ($prefixPart !== self::GROUP_ID_PREFIX) {
			return null;
		}

		$uri = \substr($gid, $len);

		if ($uri === '') {
			// invalid id
			return null;
		}
		return $uri;
	}

	/**
	 * Formats the given uri to a string
	 *
	 * @param int $uri numeric group id
	 * @return string formatted id in format "customgroup_$uri"
	 */
	private function formatGroupId($uri) {
		return self::GROUP_ID_PREFIX . $uri;
	}

	/**
	 * Returns true only if the scope is "sharing"
	 *
	 * @param string $scope
	 * @return bool true if the scope is "sharing"
	 */
	public function isVisibleForScope($scope) {
		return ($scope === 'sharing');
	}

	/**
	 * Delete group
	 *
	 * @param string $gid group id
	 */
	public function deleteGroup($gid) {
		$uri = $this->extractUri($gid);
		if ($uri !== null) {
			$groupInfo = $this->handler->getGroupByUri($uri);
			if ($groupInfo !== null) {
				$this->handler->deleteGroup($groupInfo['group_id']);
			}
		}
	}
}
