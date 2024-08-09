<?php
/**
 * ownCloud Admin_Audit
 *
 * @author Sujith Haridasan <sharidasan@owncloud.com>
 * @copyright (C) 2018 ownCloud GmbH.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Admin_Audit\Handlers;

use Symfony\Component\EventDispatcher\GenericEvent;

class AppConfig extends Base {
	private static $ignoreKeys = ['oc.integritycheck.checker', 'lastupdatedat', 'lastcron', 'lastjob'];

	public static function createorupdate(GenericEvent $event) {
		$message = '{actor} {createorupdate} appconfig of "{appname}" "{key}" with "{value}"';

		if (!$event->hasArgument('key') &&
			!$event->hasArgument('value') &&
			!$event->hasArgument('app')) {
			return null;
		}

		$appName = $event->getArgument('app');
		$key = $event->getArgument('key');
		$value = $event->getArgument('value');
		$update = $event->getArgument('update');
		$oldValue = $event->getArgument('oldvalue');

		if (\in_array($key, self::$ignoreKeys, true)) {
			return null;
		}

		//Trim the value if the length of value is greater or equal to 100
		if (\is_string($value) && (\strlen($value) >= 100)) {
			$value = 'Value with length ' . (string)\strlen($value);
		}

		//Check if its an update or a newly created config key, value
		if ($update === true) {
			$message = '{actor} {createorupdate} appconfig of "{appname}" "{key}" from "{oldvalue}" with "{value}"';
		}

		self::getLogger()->log($message, [
			'createorupdate' => ($update === true) ? 'updated' : 'created',
			'key' => $key,
			'oldvalue' => ($oldValue !== null) ? $oldValue : '',
			'value' => $value,
			'appname' => $appName
			], [
				'action' => 'config_set',
				'created' => $update === false,
				'settingName' => "${appName}.${key}",
				'settingValue' => $value,
				'oldValue' => ($oldValue !== null) ? $oldValue : '',
			]);
	}

	public static function delete(GenericEvent $event) {
		$message = '{actor} deleted appconfig "{key}" from "{appname}"';

		if (!$event->hasArgument('key') && !$event->hasArgument('app')) {
			return null;
		}

		$key = $event->getArgument('key');
		$app = $event->getArgument('app');
		self::getLogger()->log($message, [
			'key' => $key,
			'appname' => $app
			], [
				'action' => 'config_delete',
				'settingName' => "${app}.${key}",
			]);
	}

	public static function deleteapp(GenericEvent $event) {
		$message = '{actor} deleted appconfig "{appname}"';
		$app = $event->getArgument('app');
		self::getLogger()->log($message, [
			'appname' => $app,
			], [
				'action' => 'config_delete',
				'settingName' => "${app}.*",
			]);
	}
}
