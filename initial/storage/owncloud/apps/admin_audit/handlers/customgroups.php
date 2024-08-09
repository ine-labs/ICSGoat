<?php
/**
 * @author Sujith Haridasan <sharidasan@owncloud.com>
 * @copyright (C) 2017 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Admin_Audit\Handlers;

class CustomGroups extends Base {
	public static function leaveFromGroup($event) {
		if (isset($event['groupId'])) {
			$message = '{actor} user {user} leaves group with name {groupName} and id {groupId}';
		} else {
			$message = '{actor} user {user} leaves group with name {groupName}';
		}
		self::logLeaveFromGroup($message, $event);
	}

	public static function changeRoleInGroup($event) {
		if (isset($event['groupId'])) {
			$message = '{actor} user {user} changed role to {roleDisplayName}, numeric value {roleNumber}, in group with name {groupName} and id {groupId}';
		} else {
			$message = '{actor} user {user} changed role to {roleDisplayName}, numeric value {roleNumber}, in group with name {groupName}';
		}
		self::logChangeRoleInGroup($message, $event);
	}

	public static function updateGroupName($event) {
		if (isset($event['groupId'])) {
			$message = '{actor} group with id {groupId}, has updated name from {oldGroupName} to {newGroupName}';
		} else {
			$message = '{actor} group name has been updated from {oldGroupName} to {newGroupName}';
		}
		self::logCustomGroupNameUpdate($message, $event);
	}

	public static function removeUserFromGroup($event) {
		if (isset($event['groupId'])) {
			$message = '{actor}: {user} deleted from group with id {groupId} and name {group}';
		} else {
			$message = '{actor}: {user} deleted from group name {group}';
		}
		self::logCustomGroupUserDelete($message, $event);
	}

	protected static function logCustomGroupUserDelete($message, $arguments) {
		$params = [
			'user' => $arguments['user'],
			'group' => $arguments['groupName'],
		];
		$extraFields = [
			'action' => 'custom_group_member_removed',
			'removedUser' => $arguments['user'],
			'group' => $arguments['groupName'],
		];

		if (isset($arguments['groupId'])) {
			$params['groupId'] = $arguments['groupId'];
			$extraFields['groupId'] = $arguments['groupId'];
		}
		self::getLogger()->log($message, $params, $extraFields);
	}

	public static function addGroupAndUser($event) {
		if (isset($event['groupId'])) {
			$message = '{actor}: group with id {groupId} and name {group} created. User {user} added to group as admin';
		} else {
			$message = '{actor}: group with name {group} created. User {user} added to group as admin';
		}
		self::logCustomGroupCreated($message, $event);
	}

	protected static function logLeaveFromGroup($message, $arguments) {
		$user = null;
		foreach ($arguments as $k => $v) {
			if ($k === 'user') {
				$user = $v;
			}
			\OC::$server->getLogger()->warning("argument $k => value $v");
		}
		$params = ['groupName' => $arguments['groupName'], 'user' => $user];
		$extraFields = [
			'action' => 'custom_group_user_left',
			'removedUser' => isset($arguments['userId']) ? $arguments['userId'] : \OC::$server->getSession()->getId(),
			'group' => $arguments['groupName'],
		];

		if (isset($arguments['groupId'])) {
			$params['groupId'] = $arguments['groupId'];
			$extraFields['groupId'] = $arguments['groupId'];
		}

		self::getLogger()->log($message, $params, $extraFields);
	}

	protected static function logChangeRoleInGroup($message, $arguments) {
		$params = [
			'user' => $arguments['user'],
			'groupName' => $arguments['groupName'],
			// FIXME: typo in event argument: https://github.com/owncloud/customgroups/issues/143
			'roleDisplayName' => $arguments['roleDisaplayName'],
			'roleNumber' => $arguments['roleNumber']
		];
		$extraFields = [
			'action' => 'custom_group_user_role_changed',
			'targetUser' => $arguments['user'],
			'group' => $arguments['groupName'],
			'roleNumber' => $arguments['roleNumber'],
		];

		if (isset($arguments['groupId'])) {
			$params['groupId'] = $arguments['groupId'];
			$extraFields['groupId'] = $arguments['groupId'];
		}

		self::getLogger()->log($message, $params, $extraFields);
	}

	protected static function logCustomGroupNameUpdate($message, $arguments) {
		$params = [
			'oldGroupName' => $arguments['oldGroupName'],
			'newGroupName' => $arguments['newGroupName']
		];
		$extraFields = [
			'action' => 'custom_group_renamed',
			'oldGroup' => $arguments['oldGroupName'],
			'group' => $arguments['newGroupName'],
		];

		if (isset($arguments['groupId'])) {
			$params['groupId'] = $arguments['groupId'];
			$extraFields['groupId'] = $arguments['groupId'];
		}

		self::getLogger()->log($message, $params, $extraFields);
	}

	protected static function logCustomGroupCreated($message, $arguments) {
		$params = ['group' => $arguments['groupName']];
		$extraFields = [
			'action' => 'custom_group_created',
			'group' => $arguments['groupName'],
			'addedUser' => $arguments['user'],
			'admin' => true
		];

		if (isset($arguments['groupId'])) {
			$params['groupId'] = $arguments['groupId'];
			$extraFields['groupId'] = $arguments['groupId'];
		}

		self::getLogger()->log($message, $params, $extraFields);
	}

	public static function deleteGroup($event) {
		if (isset($event['groupId'])) {
			$message = '{actor}: group {group} with id {groupId} is deleted';
		} else {
			$message = '{actor}: group {group} is deleted';
		}
		self::logCustomGroupDeleted($message, $event);
	}

	protected static function logCustomGroupDeleted($message, $arguments) {
		$params = ['group' => $arguments['groupName']];
		$extraFields = [
			'action' => 'custom_group_deleted',
			'group' => $arguments['groupName'],
		];

		if (isset($arguments['groupId'])) {
			$params['groupId'] = $arguments['groupId'];
			$extraFields['groupId'] = $arguments['groupId'];
		}

		self::getLogger()->log($message, $params, $extraFields);
	}

	public static function addUserToGroup($event) {
		if (isset($event['groupId'])) {
			$message = '{actor}: user {user} is added to group with id {groupId} and name {group}';
		} else {
			$message = '{actor}: user {user} is added to group {group}';
		}
		self::logCustomGroupUserAdd($message, $event);
	}

	protected static function logCustomGroupUserAdd($message, $arguments) {
		$params = [
			'user' => $arguments['user'],
			'group' => $arguments['groupName']
		];
		$extraFields = [
			'action' => 'custom_group_member_added',
			'addedUser' => $arguments['user'],
			'group' => $arguments['groupName'],
			'admin' => false
		];

		if (isset($arguments['groupId'])) {
			$params['groupId'] = $arguments['groupId'];
			$extraFields['groupId'] = $arguments['groupId'];
		}
		self::getLogger()->log($message, $params, $extraFields);
	}
}
