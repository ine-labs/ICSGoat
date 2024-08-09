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

namespace OCA\CustomGroups\Dav;

use OCA\CustomGroups\Exception\ValidationException;
use OCP\IConfig;
use Sabre\DAV\IExtendedCollection;
use Sabre\DAV\MkCol;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\Forbidden;

use OCA\CustomGroups\CustomGroupsDatabaseHandler;
use OCA\CustomGroups\Search;
use OCA\CustomGroups\Service\MembershipHelper;
use Symfony\Component\EventDispatcher\GenericEvent;
use Sabre\DAV\Exception\Conflict;
use OCP\IGroupManager;

/**
 * Collection of custom groups
 */
class GroupsCollection implements IExtendedCollection {

	/**
	 * Custom groups handler
	 *
	 * @var CustomGroupsDatabaseHandler
	 */
	private $groupsHandler;

	/**
	 * Group manager from core
	 * @var IGroupManager
	 */
	private $groupManager;

	/**
	 * Membership helper
	 *
	 * @var MembershipHelper
	 */
	private $helper;

	/** @var IConfig */
	private $config;

	/**
	 * User id for which to use memberships or null for all groups
	 *
	 * @var string
	 */
	private $userId;

	/**
	 * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
	*/
	private $dispatcher;

	/**
	 * GroupsCollection constructor.
	 *
	 * @param IGroupManager $groupManager
	 * @param CustomGroupsDatabaseHandler $groupsHandler custom groups handler
	 * @param MembershipHelper $helper
	 * @param IConfig $config
	 * @param string|null $userId
	 */
	public function __construct(
		IGroupManager $groupManager,
		CustomGroupsDatabaseHandler $groupsHandler,
		MembershipHelper $helper,
		IConfig $config,
		$userId = null
	) {
		$this->groupManager = $groupManager;
		$this->groupsHandler = $groupsHandler;
		$this->helper = $helper;
		$this->config = $config;
		$this->userId = $userId;

		$this->dispatcher = \OC::$server->getEventDispatcher();
	}

	/**
	 * Not supported
	 *
	 * @param string $name name
	 * @param resource|string $data unused
	 * @throws MethodNotAllowed not supported
	 */
	public function createFile($name, $data = null) {
		throw new MethodNotAllowed('Cannot create regular nodes');
	}

	public function createDirectory($name) {
		if (!$this->helper->canCreateGroups()) {
			throw new Forbidden('No permission to create groups');
		}

		$this->createGroup($name, $name);
	}

	/**
	 * Creates a new custom group
	 *
	 * @param string $name group URI
	 * @throws MethodNotAllowed if the group already exists
	 */
	public function createExtendedCollection($name, Mkcol $mkCol) {
		if (!$this->helper->canCreateGroups()) {
			throw new Forbidden('No permission to create groups');
		}

		/** Group name cannot be empty */
		if (($name === '') || ($name === null)) {
			throw new ValidationException('Can not create empty group');
		}

		/** Group name must be at least 2 character long */
		if (\mb_strlen($name, 'UTF-8') < 2) {
			throw new ValidationException('The group name should be at least 2 characters long.');
		}

		/** Group name must be max 64 characters long */
		if (\mb_strlen($name, 'UTF-8') > 64) {
			throw new ValidationException('The group name should be maximum 64 characters long.');
		}

		/**
		 * A special case where index is appended with the group name
		 */
		if ((\mb_strlen($name, 'UTF-8') === 2) && \is_numeric($name[1])) {
			throw new ValidationException('The group name should be at least 2 characters long.');
		}

		/** Group name should not start with space */
		if ($name[0] === ' ') {
			throw new ValidationException('The group name can not start with space');
		}

		$displayName = $name;

		// can't use handle() here as it's called too late
		$mutations = $mkCol->getMutations();
		if (isset($mutations[GroupMembershipCollection::PROPERTY_DISPLAY_NAME])) {
			$displayName = $mutations[GroupMembershipCollection::PROPERTY_DISPLAY_NAME];
			$mkCol->setResultCode(GroupMembershipCollection::PROPERTY_DISPLAY_NAME, 202); // accepted
		}

		$this->createGroup($name, $displayName);
	}

	/**
	 * Creates a group node with the given name and display name
	 *
	 * @param string $name group uri
	 * @param string $displayName group display name
	 */
	private function createGroup($name, $displayName) {
		if (!$this->helper->isGroupDisplayNameAvailable($displayName)) {
			throw new Conflict("Group with display name \"$displayName\" already exists");
		}

		$groupId = $this->groupsHandler->createGroup($name, $displayName);
		if ($groupId === null) {
			throw new MethodNotAllowed("Group with uri \"$name\" already exists");
		}

		// add current user as admin
		$this->groupsHandler->addToGroup($this->helper->getUserId(), $groupId, true);

		$event = new GenericEvent(null, [
			'groupName' => $name,
			'groupId' => $groupId,
			'user' => $this->helper->getUserId()]);
		$this->dispatcher->dispatch('\OCA\CustomGroups::addGroupAndUser', $event);
	}

	/**
	 * Returns the custom group node for the given URI.
	 *
	 * @param string $name group URI
	 * @return GroupMembershipCollection node
	 * @throws NotFound if the requested group does not exist
	 */
	public function getChild($name) {
		$group = $this->groupsHandler->getGroupByUri($name);
		if ($group === null) {
			throw new NotFound("Group with uri \"$name\" not found");
		}
		return $this->createMembershipsCollection($group);
	}

	/**
	 * Returns nodes for all existing custom groups.
	 *
	 * @return GroupMembershipCollection[] custom group nodes
	 */
	public function getChildren() {
		return $this->search();
	}

	/**
	 * Search nodes
	 *
	 * @param Search $search search
	 */
	public function search(Search $search = null) {
		if ($this->userId !== null) {
			$allGroups = $this->groupsHandler->getUserMemberships($this->userId, $search);
		} else {
			$disallowAdminAccessAll = $this->config->getSystemValue('customgroups.disallow-admin-access-all', false);
			if ($disallowAdminAccessAll) {
				$allGroups = $this->groupsHandler->getUserMemberships($this->helper->getUserId(), $search);
			} else {
				$allGroups = $this->groupsHandler->getGroups($search);
			}
		}
		return \array_map(function ($groupInfo) {
			return $this->createMembershipsCollection($groupInfo);
		}, $allGroups);
	}

	/**
	 * Returns whether a custom group exists.
	 *
	 * @param string $name group URI
	 * @return boolean true if the group exists, false otherwise
	 */
	public function childExists($name) {
		return $this->groupsHandler->getGroupByUri($name) !== null;
	}

	/**
	 * Not supported
	 *
	 * @throws MethodNotAllowed not supported
	 */
	public function delete() {
		throw new MethodNotAllowed('Cannot delete this collection');
	}

	/**
	 * Returns the name of the node.
	 *
	 * This is used to generate the url.
	 *
	 * @return string node name
	 */
	public function getName() {
		if ($this->userId !== null) {
			return $this->userId;
		}
		return 'groups';
	}

	/**
	 * Not supported
	 *
	 * @param string $name name
	 * @throws MethodNotAllowed not supported
	 */
	public function setName($name) {
		throw new MethodNotAllowed('Cannot rename this collection');
	}

	/**
	 * Returns null
	 *
	 * @return int null
	 */
	public function getLastModified() {
		return null;
	}

	/**
	 * Creates a custom group node for the given group info.
	 *
	 * @param array $groupInfo group info
	 * @return GroupMembershipCollection node
	 */
	private function createMembershipsCollection(array $groupInfo) {
		return new GroupMembershipCollection(
			$groupInfo,
			$this->groupManager,
			$this->groupsHandler,
			$this->helper
		);
	}
}
