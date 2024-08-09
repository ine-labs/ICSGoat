<?php
/**
 * ownCloud Admin_Audit
 *
 * @author Sujith Haridasan <sharidasan@owncloud.com>
 * @copyright (C) 2019 ownCloud GmbH.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Admin_Audit\Handlers;

use Symfony\Component\EventDispatcher\GenericEvent;

class UserPreferences extends Base {
	private static $ignoreKeys = [
		'lostpassword',
	];

	public static function updateUserKeyValue(GenericEvent $event) {
		$message = '{actor} updated user preference of user "{user}" of app "{appname}" "{key}" with "{value}"';

		if (!$event->hasArgument('key') &&
			!$event->hasArgument('value') &&
			!$event->hasArgument('uid') &&
			!$event->hasArgument('app')) {
			return null;
		}

		$key = $event->getArgument('key');
		$value = $event->getArgument('value');

		if (\in_array($key, self::$ignoreKeys, true)) {
			return null;
		}

		//Trim the value if the length of value is greater or equal to 100
		if (\strlen($value) >= 100) {
			$value = 'Value with length ' . (string)\strlen($value);
		}

		self::getLogger()->log($message, [
			'key' => $key,
			'value' => $value,
			'appname' => $event->getArgument('app'),
			'user' => $event->getArgument('uid'),
		], [
			'action' => 'update_user_preference_value',
			'key' => $key,
			'value' => $value,
			'appname' => $event->getArgument('app'),
			'user' => $event->getArgument('uid')
		]);
	}

	public static function deleteUserKey(GenericEvent $event) {
		$message = '{actor} deleted user preference of user "{user}" preference "{key}" of app "{appname}"';

		if (!$event->hasArgument('key') && !$event->hasArgument('app')) {
			return null;
		}

		$key = $event->getArgument('key');
		$app = $event->getArgument('app');

		if (\in_array($key, self::$ignoreKeys, true)) {
			return null;
		}

		self::getLogger()->log($message, [
			'key' => $key,
			'appname' => $app,
			'user' => $event->getArgument('uid'),
		], [
			'action' => 'remove_user_preference_key',
			'key' => $key,
			'appname' => $app,
			'user' => $event->getArgument('uid')
		]);
	}

	public static function deleteAllUserPreferencesUser(GenericEvent $event) {
		$message = '{actor} deleted all user preferences of user "{user}"';
		$user = $event->getArgument('uid');
		self::getLogger()->log($message, [
			'user' => $user,
		], [
			'action' => 'remove_preferences_of_user',
			'user' => $event->getArgument('uid')
		]);
	}

	public static function deleteAllUserPreferencesApp(GenericEvent $event) {
		$message = '{actor} deleted all user preferences of app "{appname}"';
		$app = $event->getArgument('app');
		self::getLogger()->log($message, [
			'appname' => $app,
		], [
			'action' => 'delete_all_user_preference_of_app',
			'appname' => $app
		]);
	}
}
