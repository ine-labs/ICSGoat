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

use OCA\CustomGroups\CustomGroupsDatabaseHandler;
use OCA\CustomGroups\Exception\ValidationException;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\PreconditionFailed;
use OCA\CustomGroups\Dav\Roles;
use OCA\CustomGroups\Search;
use OCA\CustomGroups\Service\MembershipHelper;
use Symfony\Component\EventDispatcher\GenericEvent;
use OCP\IGroupManager;

/**
 * Group memberships collection for a given group
 */
class GroupMembershipCollection implements \Sabre\DAV\ICollection, \Sabre\DAV\IProperties {
	public const NS_OWNCLOUD = 'http://owncloud.org/ns';

	public const PROPERTY_GROUP_ID = '{http://owncloud.org/ns}group-id';
	public const PROPERTY_DISPLAY_NAME = '{http://owncloud.org/ns}display-name';
	public const PROPERTY_ROLE = '{http://owncloud.org/ns}role';

	/**
	 * Custom groups handler
	 *
	 * @var CustomGroupsDatabaseHandler
	 */
	private $groupsHandler;

	/**
	 * Membership helper
	 *
	 * @var MembershipHelper
	 */
	private $helper;

	/**
	 * Group manager from core
	 *
	 * @var IGroupManager
	 */
	private $groupManager;

	/**
	 * Group information
	 *
	 * @var array
	 */
	private $groupInfo;

	/**
	 * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
	*/
	private $dispatcher;

	/**
	 * Constructor
	 *
	 * @param array $groupInfo group info
	 * @param CustomGroupsDatabaseHandler $groupsHandler custom groups handler
	 * @param MembershipHelper $helper membership helper
	 */
	public function __construct(
		array $groupInfo,
		IGroupManager $groupManager,
		CustomGroupsDatabaseHandler $groupsHandler,
		MembershipHelper $helper
	) {
		$this->groupsHandler = $groupsHandler;
		$this->groupManager = $groupManager;
		$this->groupInfo = $groupInfo;
		$this->helper = $helper;

		$this->dispatcher = \OC::$server->getEventDispatcher();
	}

	/**
	 * Deletes the group
	 *
	 * @throws Forbidden if no permisson to delete this group
	 */
	public function delete() {
		$groupId = $this->groupInfo['group_id'];
		if (!$this->helper->isUserAdmin($groupId)) {
			throw new Forbidden("No permission to delete group \"$groupId\"");
		}

		$group = $this->groupManager->get('customgroup_' . $this->groupInfo['uri']);
		if ($group === null) {
			throw new NotFound("Group not found \"$groupId\"");
		}
		$group->delete();

		$event = new GenericEvent(null, [
			'groupName' => $this->groupInfo['display_name'],
			'groupId' => $groupId]);
		$this->dispatcher->dispatch('\OCA\CustomGroups::deleteGroup', $event);
	}

	/**
	 * Returns the name of the node.
	 *
	 * @return string
	 */
	public function getName() {
		return (string)$this->groupInfo['uri'];
	}

	/**
	 * Not supported
	 *
	 * @param string $name the new name
	 * @throws MethodNotAllowed not supported
	 */
	public function setName($name) {
		throw new MethodNotAllowed();
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
	 * Updates properties on this node.
	 *
	 * @param PropPatch $propPatch PropPatch request
	 */
	public function propPatch(PropPatch $propPatch) {
		$propPatch->handle(self::PROPERTY_DISPLAY_NAME, [$this, 'updateDisplayName']);
	}

	/**
	 * Returns a list of properties for this node.
	 *
	 * @param array|null $properties requested properties or null for all
	 * @return array property values
	 */
	public function getProperties($properties) {
		$result = [];
		if ($properties === null || \in_array(self::PROPERTY_DISPLAY_NAME, $properties)) {
			$result[self::PROPERTY_DISPLAY_NAME] = $this->groupInfo['display_name'];
		}
		if ($properties === null || \in_array(self::PROPERTY_GROUP_ID, $properties)) {
			$result[self::PROPERTY_GROUP_ID] = $this->groupInfo['group_id'];
		}
		if ($properties === null || \in_array(self::PROPERTY_ROLE, $properties)) {
			// role is only set if the group info was queried from a specific user
			if (isset($this->groupInfo['role'])) {
				$result[self::PROPERTY_ROLE] = Roles::backendToDav($this->groupInfo['role']);
			}
		}
		return $result;
	}

	/**
	 * Adds a new member to this group
	 *
	 * @param string $userId user id to add
	 * @param resource|string $data unused
	 * @throws Forbidden if the current user has insufficient permissions
	 * @throws PreconditionFailed if the user did not exist
	 */
	public function createFile($userId, $data = null) {
		$groupId = $this->groupInfo['group_id'];
		if (!$this->helper->isUserAdmin($groupId)) {
			throw new Forbidden("No permission to add members to group \"$groupId\"");
		}

		// check if the user name actually exists
		$user = $this->helper->getUser($userId);
		// not existing user or mismatch user casing
		if ($user === null || $userId !== $user->getUID()) {
			throw new PreconditionFailed("The user \"$userId\" does not exist");
		}

		if (!$this->helper->canAddMember($userId)) {
			throw new Forbidden("Cannot add member \"$userId\" to group \"$groupId\"");
		}

		if (!$this->groupsHandler->addToGroup($userId, $groupId, CustomGroupsDatabaseHandler::ROLE_MEMBER)) {
			throw new PreconditionFailed("The user \"$userId\" is already member of this group");
		}

		$this->helper->notifyUser($userId, $this->groupInfo);

		$event = new GenericEvent(null, [
			'groupName' => $this->groupInfo['display_name'],
			'groupId' => $groupId,
			'user' => $userId]);
		$this->dispatcher->dispatch('\OCA\CustomGroups::addUserToGroup', $event);
	}

	/**
	 * Not supported
	 *
	 * @param string $name name
	 * @throws MethodNotAllowed not supported
	 */
	public function createDirectory($name) {
		throw new MethodNotAllowed('Cannot create collections');
	}

	/**
	 * Returns a membership node
	 *
	 * @param string $userId user id
	 * @return MembershipNode membership node
	 * @throws NotFound if the given user has no membership in this group
	 * @throws Forbidden if the current user has insufficient permissions
	 */
	public function getChild($userId) {
		$groupId = $this->groupInfo['group_id'];
		if (!$this->helper->isUserMember($groupId) && !$this->helper->isUserAdmin($groupId)) {
			throw new Forbidden("No permission to list members of group \"$groupId\"");
		}
		$memberInfo = $this->groupsHandler->getGroupMemberInfo($groupId, $userId);
		if ($memberInfo === null) {
			throw new NotFound(
				"User with id \"$userId\" is not member of group with uri \"$groupId\""
			);
		}
		return $this->createCustomGroupMemberNode($memberInfo);
	}

	/**
	 * Returns a list of all memberships
	 *
	 * @return CustomGroupMemberNode[] list of memberships
	 * @throws Forbidden if the current user has insufficient permissions
	 */
	public function getChildren() {
		return $this->search();
	}

	public function search(Search $search = null) {
		$groupId = $this->groupInfo['group_id'];
		if (!$this->helper->isUserMember($groupId)
			&& !$this->helper->isUserAdmin($groupId)) {
			throw new Forbidden("No permission to list members of group \"$groupId\"");
		}
		$members = $this->groupsHandler->getGroupMembers($groupId, $search);
		return \array_map(function ($memberInfo) {
			return $this->createCustomGroupMemberNode($memberInfo);
		}, $members);
	}

	/**
	 * Returns whether a user has a membership in this group.
	 *
	 * @param string $userId user id
	 * @return boolean true if the user has a membership, false otherwise
	 * @throws Forbidden if the current user has insufficient permissions
	 */
	public function childExists($userId) {
		$groupId = $this->groupInfo['group_id'];
		if (!$this->helper->isUserMember($groupId) && !$this->helper->isUserAdmin($groupId)) {
			throw new Forbidden("No permission to list members of group \"$groupId\"");
		}
		return $this->groupsHandler->inGroup($userId, $groupId);
	}

	/**
	 * Update the display name.
	 * Returns 403 status code if the current user has insufficient permissions.
	 *
	 * @param string $displayName display name to set
	 * @return bool|int or status code
	 * @throws ValidationException when group name is empty or starts with a space or less than 2 chars long
	 */
	public function updateDisplayName($displayName) {
		if (($displayName === '') || ($displayName === null)) {
			throw new ValidationException('Can not rename to empty group');
		}

		/**
		 * Verify if the character length is less than 2 or if its multibyte string, then
		 * verify if the multibyte character length is less than 2.
		 */
		if (\mb_strlen($displayName, 'UTF-8') < 2) {
			throw new ValidationException("The group name should be at least 2 characters long.");
		}

		/* Verify if the multibyte character length is more than 64 */
		if (\mb_strlen($displayName, 'UTF-8') > 64) {
			throw new ValidationException('The group name should be maximum 64 characters long.');
		}

		if ($displayName[0] === ' ') {
			throw new ValidationException('The group name can not start with space');
		}

		if (!$this->helper->isUserAdmin($this->groupInfo['group_id'])) {
			return 403;
		}

		if ($this->groupInfo['display_name'] !== $displayName && !$this->helper->isGroupDisplayNameAvailable($displayName)) {
			return 409;
		}

		$event = new GenericEvent(null, [
			'oldGroupName' => $this->groupInfo['display_name'],
			'newGroupName' => $displayName,
			'groupId' => $this->groupInfo['group_id']]);
		$this->dispatcher->dispatch('\OCA\CustomGroups::updateGroupName', $event);

		$result = $this->groupsHandler->updateGroup(
			$this->groupInfo['group_id'],
			$this->groupInfo['uri'],
			$displayName
		);
		$this->groupInfo['display_name'] = $displayName;

		return $result;
	}

	/**
	 * Creates a membership node based on the given membership info.
	 *
	 * @param array $memberInfo membership info
	 * @return MembershipNode membership node
	 */
	private function createCustomGroupMemberNode(array $memberInfo): MembershipNode {
		return new MembershipNode(
			$memberInfo,
			$memberInfo['user_id'],
			$this->groupInfo,
			$this->groupsHandler,
			$this->helper
		);
	}
}
