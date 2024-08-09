<?php
/**
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

class Config extends Base {
	/**
	 * @param GenericEvent $event
	 */
	public static function createorupdate(GenericEvent $event) {
		$message = '{actor} {createorupdate} config "{key}" with "{value}"';
		$key = $event->getArgument('key');
		$value = $event->getArgument('value');
		$oldValue = $event->getArgument('oldvalue');
		$update = $event->getArgument('update');

		//Trim the value if the length of value is greater or equal to 100
		if (\is_string($value) && (\strlen($value) >= 100)) {
			$value = 'Value with length ' . (string)\strlen($value);
		}

		//Check if its an update or a newly created config key, value
		if ($update === true) {
			$message = '{actor} {createorupdate} config "{key}" from "{oldvalue}" to "{value}"';
		}

		self::getLogger()->log($message, [
			'createorupdate' => ($update === true) ? 'updated' : 'created',
			'key' => $key,
			'oldvalue' => ($oldValue !== null) ? $oldValue : '',
			'value' => $value
		], [
				'action' => 'config_set',
				'created' => $update === false,
				'settingName' => $key,
				'settingValue' => $value,
				'oldValue' => ($oldValue !== null) ? $oldValue : '',
		]);
	}

	/**
	 * @param GenericEvent $event
	 */
	public static function delete(GenericEvent $event) {
		$message = '{actor} deleted config "{key}" and value: "{value}"';
		$key = $event->getArgument('key');
		$value = $event->getArgument('value');

		//Trim the value if the length of value is greater or equal to 100
		if (\is_string($value) && (\strlen($value) >= 100)) {
			$value = 'Value with length ' . (string)\strlen($value);
		}

		self::getLogger()->log($message, [
			'key' => $key,
			'value' => $value
			], [
				'action' => 'config_delete',
				'settingName' => $key,
				'settingValue' => $value,
			]);
	}
}
