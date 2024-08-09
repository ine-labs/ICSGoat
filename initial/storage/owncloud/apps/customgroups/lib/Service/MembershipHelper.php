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

namespace OCA\CustomGroups\Service;

use OCA\CustomGroups\CustomGroupsDatabaseHandler;
use OCA\CustomGroups\Dav\Roles;
use OCP\IUserSession;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCA\CustomGroups\Search;
use OCP\IUser;
use OCP\Notification\IManager;
use OCP\IURLGenerator;
use OCP\IConfig;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Membership helper
 *
 * Provides method related to the current user's membership and admin roles.
 */
class MembershipHelper {

	/**
	 * Custom groups handler
	 *
	 * @var CustomGroupsDatabaseHandler
	 */
	private $groupsHandler;

	/**
	 * User session
	 *
	 * @var IUserSession
	 */
	private $userSession;

	/**
	 * User manager
	 *
	 * @var IUserManager
	 */
	private $userManager;

	/**
	 * Group manager
	 *
	 * @var IGroupManager
	 */
	private $groupManager;

	/**
	 * Notification manager
	 *
	 * @var IManager
	 */
	private $notificationManager;

	/**
	 * URL generator
	 *
	 * @var IURLGenerator
	 */
	private $urlGenerator;

	/**
	 * Membership info for the currently logged in user
	 *
	 * @var array
	 */
	private $userMemberInfo = [];

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
	*/
	private $dispatcher;

	/**
	 * Membership helper
	 *
	 * @param CustomGroupsDatabaseHandler $groupsHandler custom groups handler
	 * @param IUserSession $userSession user session
	 * @param IUserManager $userManager user manager
	 * @param IGroupManager $groupManager group manager
	 * @param IManager $notificationManager notification manager
	 * @param IURLGenerator $urlGenerator URL generator
	 * @param IConfig $config config
	 */
	public function __construct(
		CustomGroupsDatabaseHandler $groupsHandler,
		IUserSession $userSession,
		IUserManager $userManager,
		IGroupManager $groupManager,
		IManager $notificationManager,
		IURLGenerator $urlGenerator,
		IConfig $config
	) {
		$this->groupsHandler = $groupsHandler;
		$this->userSession = $userSession;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->notificationManager = $notificationManager;
		$this->urlGenerator = $urlGenerator;
		$this->config = $config;

		$this->dispatcher = \OC::$server->getEventDispatcher();
	}

	/**
	 * Returns the currently logged in user id
	 *
	 * @return string user id
	 */
	public function getUserId() {
		return $this->userSession->getUser()->getUID();
	}

	/**
	 * Returns the user object for a given user id
	 *
	 * @param string $userId user id
	 * @return IUser|null user object or null if user does not exist
	 */
	public function getUser($userId) {
		return $this->userManager->get($userId);
	}

	/**
	 * Returns membership information for the current user
	 *
	 * @param int $groupId group id
	 * @return array membership information
	 */
	private function getUserMemberInfo($groupId) {
		if (!isset($this->userMemberInfo[$groupId])) {
			$userId = $this->getUserId();
			$this->userMemberInfo[$groupId] = $this->groupsHandler->getGroupMemberInfo($groupId, $userId);
		}
		return $this->userMemberInfo[$groupId];
	}

	/**
	 * Returns whether the current user can administrate this group
	 *
	 * @param int $groupId group id
	 * @return boolean true if the user can administrate, false otherwise
	 */
	public function isUserAdmin($groupId) {
		$isCurrentUserAdmin = $this->isUserSuperAdmin();
		$memberInfo = $this->getUserMemberInfo($groupId);
		if ($isCurrentUserAdmin) {
			$denyAdminAccessAll = $this->config->getSystemValue('customgroups.disallow-admin-access-all', false);
			/**
			 * If system config customgroups.disallow-admin-access-all is set to true
			 * then ownCloud admin is denied from the groups which it is not the
			 * owner of or member of. It's also denied from the display name and editing members
			 * of the group which its not not allowed to.
			 */
			if ($denyAdminAccessAll) {
				return ($memberInfo !== null && $memberInfo['role']);
			}
			/**
			 * If customgroups.disallow-admin-access-all is not set then ownCloud admin
			 * has access to all (which is default behaviour)
			 */
			return true;
		}

		return ($memberInfo !== null && $memberInfo['role']);
	}

	/**
	 * Returns whether the current user is an ownCloud admin
	 *
	 * @return boolean true if the user is an ownCloud admin, false otherwise
	 */
	public function isUserSuperAdmin() {
		return ($this->groupManager->isAdmin($this->getUserId()));
	}

	/**
	 * Returns whether the current user is member of this group
	 *
	 * @param int $groupId group id
	 * @return boolean true if the user is member, false otherwise
	 */
	public function isUserMember($groupId) {
		$memberInfo = $this->getUserMemberInfo($groupId);
		return ($memberInfo !== null);
	}

	/**
	 * Returns whether the given group's member is the one and only group admin
	 *
	 * @param int $groupId group id
	 * @param string $userId user id of the admin to check
	 * @return bool true if it's the only admin, false otherwise
	 */
	public function isTheOnlyAdmin($groupId, $userId) {
		$searchAdmins = new Search();
		$searchAdmins->setRoleFilter(CustomGroupsDatabaseHandler::ROLE_ADMIN);
		$groupAdmins = $this->groupsHandler->getGroupMembers($groupId, $searchAdmins);
		if (\count($groupAdmins) > 1) {
			return false;
		}
		if ($groupAdmins[0]['user_id'] !== $userId) {
			return false;
		}

		return true;
	}

	/**
	 * Notify the given user about the given group
	 *
	 * @param string $targetUserId user to notify
	 * @param array $groupInfo group info
	 */
	public function notifyUser($targetUserId, array $groupInfo) {
		$link = $this->urlGenerator->linkToRoute('settings.SettingsPage.getPersonal', ['sectionid' => 'customgroups', 'group' => $groupInfo['uri']]);

		$user = $this->getUser($this->getUserId());

		$notification = $this->notificationManager->createNotification();
		$notification->setApp('customgroups')
			->setDateTime(new \DateTime())
			->setObject('customgroup', $groupInfo['group_id'])
			->setSubject('added_member', [$user->getDisplayName(), $groupInfo['display_name']])
			->setMessage('added_member', [$user->getDisplayName(), $groupInfo['display_name']])
			->setUser($targetUserId)
			->setLink($link);
		$this->notificationManager->notify($notification);
	}

	/**
	 * Notify the given user about a role change in given group.
	 *
	 * @param array $groupInfo group info
	 * @param string $targetUserId user to notify
	 * @param array $memberInfo membership info
	 */
	public function notifyUserRoleChange($targetUserId, array $groupInfo, array $memberInfo) {
		$link = $this->urlGenerator->linkToRoute('settings.SettingsPage.getPersonal', ['sectionid' => 'customgroups', 'group' => $groupInfo['uri']]);
		$user = $this->getUser($this->getUserId());

		$notification = $this->notificationManager->createNotification();
		$notification->setApp('customgroups')
			->setDateTime(new \DateTime())
			->setObject('customgroup', $memberInfo['group_id'])
			->setSubject('changed_member_role', [$user->getDisplayName(), $groupInfo['display_name'], $memberInfo['role']])
			->setMessage('changed_member_role', [$user->getDisplayName(), $groupInfo['display_name'], $memberInfo['role']])
			->setUser($targetUserId)
			->setLink($link);
		$this->notificationManager->notify($notification);
		if ($memberInfo['role'] === Roles::BACKEND_ROLE_MEMBER) {
			$roleName = "Member";
		} elseif ($memberInfo['role'] === Roles::BACKEND_ROLE_ADMIN) {
			$roleName = "Group owner";
		}
		$event = new GenericEvent(null, [
			'user' => $targetUserId,
			'groupName' => $groupInfo['display_name'],
			'roleNumber' => $memberInfo['role'],
			'roleDisaplayName' => $roleName,
			'groupId' => $memberInfo['group_id']]);
		$this->dispatcher->dispatch('\OCA\CustomGroups::changeRoleInGroup', $event);
	}

	/**
	 * Notify the given user that they were removed from a group
	 *
	 * @param string $targetUserId user to notify
	 * @param array $groupInfo group info
	 */
	public function notifyUserRemoved($targetUserId, array $groupInfo) {
		$link = $this->urlGenerator->linkToRoute('settings.SettingsPage.getPersonal', ['sectionid' => 'customgroups']);
		$user = $this->getUser($this->getUserId());

		$notification = $this->notificationManager->createNotification();
		$notification->setApp('customgroups')
			->setDateTime(new \DateTime())
			->setObject('customgroup', $groupInfo['group_id'])
			->setSubject('removed_member', [$user->getDisplayName(), $groupInfo['display_name']])
			->setMessage('removed_member', [$user->getDisplayName(), $groupInfo['display_name']])
			->setUser($targetUserId)
			->setLink($link);
		$this->notificationManager->notify($notification);
	}

	/**
	 * Returns whether the current user is allowed to create custom groups.
	 *
	 * @return bool true if allowed, false otherwise
	 */
	public function canCreateGroups() {
		$restrictToSubadmins = $this->config->getAppValue('customgroups', 'only_subadmin_can_create', 'false') === 'true';

		// if the restriction is set, only admins or subadmins are allowed to create, not regular users
		return (
			!$restrictToSubadmins
			|| $this->isUserSuperAdmin()
			|| $this->groupManager->getSubAdmin()->isSubAdmin($this->userSession->getUser())
		);
	}

	/**
	 * Checks whether the current user is allowed to add a member into the target group
	 *
	 * @param string $targetUserId new member to add
	 */
	public function canAddMember($targetUserId) {
		$shareWithGroupOnly = $this->config->getAppValue('core', 'shareapi_only_share_with_group_members', 'no') === 'yes';
		if (!$shareWithGroupOnly) {
			return true;
		}

		// check if user to add is member of any groups
		$userGroups = $this->groupManager->getUserGroupIds($this->userSession->getUser());
		$targetUserGroups = $this->groupManager->getUserGroupIds($this->userManager->get($targetUserId));

		foreach ($userGroups as $userGroup) {
			if (\in_array($userGroup, $targetUserGroups)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks whether the given display name exists, if applicable from the configuration
	 *
	 * @param string $displayName group display name
	 * @return bool true if a duplicate group was found and the operation is not allowed, false if
	 * no duplicate group was found or duplicates are allowed
	 */
	public function isGroupDisplayNameAvailable($displayName) {
		if ($this->config->getAppValue('customgroups', 'allow_duplicate_names', 'false') === 'true') {
			return true;
		}

		$groups = $this->groupsHandler->getGroupsByDisplayName($displayName);

		return empty($groups);
	}
}
