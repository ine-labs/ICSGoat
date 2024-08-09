<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 * @author Michael Barz <mbarz@owncloud.com>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Guests;

use OCP\GroupInterface;
use OCP\IConfig;

/**
 * Provides a virtual (not existing in the database) group for guest users.
 * Members of this group are determined by the user value "isGuest" in oc_preferences.
 *
 * @package OCA\Guests
 */
class GroupBackend implements GroupInterface {
	public const DEFAULT_NAME = 'guest_app';

	/**
	 * @var array
	 */
	private $guestMembers = [];

	/**
	 * @var array
	 */
	protected $possibleActions = [
		self::COUNT_USERS => 'countUsersInGroup',
	];

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var string
	 */
	private $groupName;

	/**
	 * GroupBackend constructor.
	 *
	 * @param IConfig $config
	 * @param string $groupName
	 */
	public function __construct(IConfig $config, $groupName = self::DEFAULT_NAME) {
		$this->config = $config;
		$this->groupName = $groupName;
	}

	/**
	 * Get Guest User ID's from oc_preferences
	 *
	 * @return array
	 */
	private function getMembers() {
		if (empty($this->guestMembers)) {
			$this->guestMembers = $this->config->getUsersForUserValue(
				'owncloud',
				'isGuest',
				'1'
			);
		}

		return $this->guestMembers;
	}

	/**
	 * Get all supported actions
	 *
	 * @return int bitwise-or'ed actions
	 *
	 * Returns the supported actions as int to be
	 * compared with \OC\Group\Backend::CREATE_GROUP etc.
	 */
	public function getSupportedActions() {
		$actions = 0;
		foreach ($this->possibleActions as $action => $methodName) {
			if (\method_exists($this, $methodName)) {
				$actions |= $action;
			}
		}

		return $actions;
	}

	/**
	 * Check if backend implements actions
	 *
	 * @param int $actions bitwise-or'ed actions
	 *
	 * @return bool
	 *
	 * Returns the supported actions as int to be
	 * compared with \OC\Group\Backend::CREATE_GROUP etc.
	 */
	public function implementsActions($actions) {
		return (bool)($this->getSupportedActions() & $actions);
	}

	/**
	 * is user in group?
	 *
	 * @param string $uid uid of the user
	 * @param string $gid gid of the group
	 *
	 * @return bool
	 * @since 4.5.0
	 *
	 * Checks whether the user is member of a group or not.
	 */
	public function inGroup($uid, $gid) {
		if ($gid !== $this->groupName) {
			return false;
		}
		$isGuest = $this->config->getUserValue(
			$uid,
			'owncloud',
			'isGuest',
			'0'
		);
		return $isGuest === '1' ;
	}

	/**
	 * Get all groups a user belongs to
	 *
	 * @param string $uid Name of the user
	 *
	 * @return array an array of group names
	 * @since 4.5.0
	 *
	 * This function fetches all groups a user belongs to. It does not check
	 * if the user exists at all.
	 */
	public function getUserGroups($uid) {
		$isGuest = $this->config->getUserValue(
			$uid,
			'owncloud',
			'isGuest',
			'0'
		);
		if ($isGuest === '1') {
			return [$this->groupName];
		}

		return [];
	}

	/**
	 * get a list of all groups
	 *
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return array an array of group names
	 * @since 4.5.0
	 *
	 * Returns a list with all groups
	 */
	public function getGroups($search = '', $limit = -1, $offset = 0) {
		return [$this->groupName];
	}

	/**
	 * check if a group exists
	 *
	 * @param string $gid
	 *
	 * @return bool
	 * @since 4.5.0
	 */
	public function groupExists($gid) {
		return $gid === $this->groupName;
	}

	/**
	 * get a list of all users in a group
	 *
	 * @param string $gid
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return array an array of user ids
	 * @since 4.5.0
	 */
	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		if ($gid === $this->groupName && $limit !== 0) {
			if (!empty($search)) {
				$users = \array_filter(
					$this->getMembers(),
					function ($var) use ($search) {
						return \strpos($var, $search) !== false;
					}
				);
			} else {
				$users = $this->getMembers();
			}

			if ($limit > 0) {
				return \array_slice($users, $offset, $limit);
			}
			return $users;
		}

		return [];
	}

	/**
	 * @return int
	 */
	public function countUsersInGroup() {
		return \count($this->getMembers());
	}

	/**
	 * Returns whether the groups are visible for a given scope.
	 *
	 * @param string|null $scope scope string
	 *
	 * @return bool true if searchable, false otherwise
	 *
	 * @since 10.0.0
	 */
	public function isVisibleForScope($scope) {
		return $scope !== 'sharing';
	}
}
