<?php
/**
 * ownCloud Admin_Audit
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright (C) 2016 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Admin_Audit\Handlers;

use OCP\IUser;
use Symfony\Component\EventDispatcher\GenericEvent;

class User extends Base {
	/**
	 * Log when a user is created
	 * @param GenericEvent $params
	 */
	public static function user_create($params) {
		self::getLogger()->log('User {user} created by {actor}', [
			'user' => $params['uid'],
		], [
			'action' => 'user_created',
			'targetUser' => $params['uid'],
		]);
	}

	/**
	 * Log when a user is deleted
	 * @param GenericEvent $params
	 */
	public static function user_delete($params) {
		self::getLogger()->log('User {user} deleted by {actor}', [
			'user' => $params['uid'],
		], [
			'action' => 'user_deleted',
			'targetUser' => $params['uid'],
		]);
	}

	public static function postSetPassword($params) {
		if ($params['user']->getBackendClassName() === 'Database') {
			// log only for local users
			self::getLogger()->log('Password of User {user} changed by {actor}', [
				'user' => $params['user']->getUID()
			], [
				'action' => 'user_password_reset',
				'targetUser' => $params['user']->getUID(),
			]);
		}
	}

	public static function postAddUser(\OCP\IGroup $group, \OCP\IUser $user) {
		// log only for local users
		self::getLogger()->log('User {user} was added to group {group} by {actor}', [
			'user' => $user->getUID(),
			'group' => $group->getGID()
		], [
			'action' => 'group_member_added',
			'targetUser' => $user->getUID(),
			'group' => $group->getGID(),
		]);
	}

	public static function postRemoveUser(\OCP\IGroup $group, \OCP\IUser $user) {
		// log only for local users
		self::getLogger()->log('User {user} was removed from group {group} by {actor}', [
			'user' => $user->getUID(),
			'group' => $group->getGID(),
		], [
			'action' => 'group_member_removed',
			'targetUser' => $user->getUID(),
			'group' => $group->getGID(),
		]);
	}

	public static function changeUser($params) {
		if ($params['user']->getBackendClassName() === 'Database') {
			// log only for local users
			$valueStr = '';
			if ($params['value'] !== null) {
				$valueStr = 'to value "{value}" ';
			}

			$message = 'Feature {feature} of User {user} was changed ' .$valueStr . 'by {actor}';
			if ($params['feature'] === 'groupAdmin') {
				$valueStr = ($params['value'] === 'create') ? ' became' : ' removed as';
				$message = 'Feature {feature} User {user}' . $valueStr . ' group admin of group "{group}" by {actor}';
			}
			self::getLogger()->log($message, [
				'user' => $params['user']->getUID(),
				'feature' => $params['feature'],
				'value' => $params['value'],
				'group' => (!isset($params['group'])) ? '' : $params['group']->getGID()
			], [
				'action' => 'user_feature_changed',
				'targetUser' => $params['user']->getUID(),
				'group' => (!isset($params['group'])) ? '' : $params['group']->getGID(),
				'feature' => $params['feature'],
				'value' => $params['value'],
			]);
		}
	}

	/**
	 * @param GenericEvent $event
	 */
	public static function postSetEnabled(GenericEvent $event) {
		/** @var IUser $user */
		$user = $event->getSubject();
		if ($user instanceof IUser) {
			$statusChange = ($user->isEnabled()) ? 'enabled' : 'disabled';
			self::getLogger()->log('User {user} was {statusChange} by {actor}', [
				'user' => $user->getUID(),
				'statusChange' => $statusChange
			], [
				'action' => 'user_state_changed',
				'targetUser' => $user->getUID(),
				'enabled' => $user->isEnabled(),
			]);
		}
	}

	public static function createGroup($event) {
		$group = $event->getSubject();

		self::getLogger()->log('Group {group} created by {actor}', [
			'group' => $group->getGID()
		], [
			'action' => 'group_created',
			'group' => $group->getGID(),
		]);
	}

	public static function deleteGroup($event) {
		$group = $event->getSubject();

		self::getLogger()->log('Group {group} deleted by {actor}', [
			'group' => $group->getGID(),
		], [
			'action' => 'group_deleted',
			'group' => $group->getGID(),
		]);
	}
}
