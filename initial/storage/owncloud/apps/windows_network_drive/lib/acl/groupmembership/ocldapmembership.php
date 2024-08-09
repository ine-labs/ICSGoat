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

namespace OCA\windows_network_drive\lib\acl\groupmembership;

use OCA\windows_network_drive\lib\acl\IGroupMembership;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IConfig;

/**
 * Assume that the group membership can be retrieved via ownCloud's group manager
 * This requires that ownCloud has LDAP connection to the same AD / LDAP that the
 * windows / samba server is using.
 * Note that the domain can't be retrieved from ownCloud's side, so it will remain unknown
 *
 * It might be possible to create local groups and copy the same membership information
 * but this isn't intended and it's also error-prone. It will cause problems on the long run.
 */
class OCLDAPMembership implements IGroupMembership {
	/** @var IGroupManager */
	private $groupManager;
	/** @var IUserManager */
	private $userManager;
	/** @var IConfig */
	private $config;
	/** @var array */
	private $groupCache = [];

	/**
	 * @param IGroupManager $groupManager
	 */
	public function __construct(IUserManager $userManager, IGroupManager $groupManager, IConfig $config) {
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->config = $config;
	}

	/**
	 * @inheritdoc
	 */
	public function getGroupMembers($group) {
		//strip the domain from the group if any
		$group = $this->stripDomain($group);

		if (isset($this->groupCache[$group])) {
			$groupData = $this->groupCache[$group];
			if (!$groupData['isGroup']) {
				throw new MissingGroupException("$group is not a group");
			}
			if ($groupData['isComplete']) {
				return \array_keys($groupData['list']);
			}
		}

		$this->groupCache[$group] = [
			'isComplete' => false,
			'isGroup' => false,
			'list' => [],
		];

		if ($this->config->getSystemValue('wnd.groupmembership.checkUserFirst', false)) {
			if ($this->isUser($group)) {
				// checking if the $group is in fact a user is expected to be faster
				throw new MissingGroupException("$group is a user");
			}
		}

		$groupObj = $this->groupManager->get($group);
		if ($groupObj) {
			$users = $groupObj->getUsers();
			$this->groupCache[$group] = [
				'isComplete' => true,
				'isGroup' => true,
				'list' => [],
			];
			foreach ($users as $user) {
				$this->groupCache[$group]['list'][$user->getUID()] = true;
			}
			return \array_keys($this->groupCache[$group]['list']);
		} else {
			throw new MissingGroupException("$group group not found");
		}
	}

	/**
	 * @inheritdoc
	 */
	public function isInGroup($user, $group) {
		$user = $this->stripDomain($user);
		$group = $this->stripDomain($group);

		// check the cache first
		if (isset($this->groupCache[$group])) {
			$groupData = $this->groupCache[$group];
			if (!$groupData['isGroup']) {
				throw new MissingGroupException("$group is not a group");
			}
			if (isset($groupData['list'][$user])) {
				return $groupData['list'][$user];
			}
		}

		$this->groupCache[$group] = [
			'isComplete' => false,
			'isGroup' => false,
			'list' => [],
		];
		if ($this->config->getSystemValue('wnd.groupmembership.checkUserFirst', false)) {
			if ($this->isUser($group)) {
				// checking if the $group is in fact a user is expected to be faster
				throw new MissingGroupException("$group is a user");
			}
		}

		$groupObj = $this->groupManager->get($group);
		if ($groupObj) {
			$userObj = $this->userManager->get($user);
			if ($userObj) {
				$isUserInGroup = $groupObj->inGroup($userObj);
				$this->groupCache[$group]['isGroup'] = true;
				$this->groupCache[$group]['list'][$user] = $isUserInGroup;
				return $isUserInGroup;
			} else {
				$this->groupCache[$group]['isGroup'] = true;
				$this->groupCache[$group]['list'][$user] = false;
				throw new MissingUserException("$user user not found");
			}
		} else {
			throw new MissingGroupException("$group group not found");
		}
	}

	/**
	 * @inheritDoc
	 */
	public function fixUser($user) {
		return $this->stripDomain($user);
	}

	private function stripDomain($item) {
		if (\strpos($item, '\\') !== false) {
			$parts = \explode('\\', $item, 2);
			$item = $parts[1];
		}
		return $item;
	}

	private function isUser($name) {
		$userObj = $this->userManager->get($name);
		if ($userObj === null) {
			// user not found => not a user
			return false;
		}

		// if the user isn't an LDAP user, do not consider it as user so we can
		// check for a group with the same name later.
		$backendName = $userObj->getBackendClassName();
		if ($backendName === 'OCA\User_LDAP\User_Proxy' || $backendName === 'LDAP') {
			return true;
		}
		return false;
	}
}
