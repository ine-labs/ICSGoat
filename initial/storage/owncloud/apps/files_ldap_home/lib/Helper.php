<?php
/**
 * ownCloud
 *
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Files_LDAP_Home;

use OCP\App;

class Helper {

	/**
	 * Checks whether the app and dependencies are enabled
	 * Either returns true or throws exceptions
	 *
	 * @return boolean true
	 * @throws \Exception on failure
	 */
	public static function checkOperability() {
		if (!App::isEnabled('files_ldap_home')) {
			throw new \Exception('files_ldap_home is not enabled');
		}

		if (!App::isEnabled('user_ldap')) {
			throw new \Exception('user_ldap is not enabled');
		}

		return true;
	}

	/**
	 * Validates a rename or delete operation
	 *
	 * @param string $path the path of the file in question
	 * @return bool
	 */
	private static function isFileOperationOK($path) {
		static $settings = null;

		$config = \OC::$server->getConfig();
		if (!Storage::isLdapUser($config)) {
			return true;
		}

		if ($settings === null) {
			$settings = new Settings($config);
		}

		$homeDir = '/' . $settings->MountName;
		if ($path === $homeDir) {
			return false;
		}

		return true;
	}

	/**
	 * Makes sure that the Home Folder will not be renamed
	 *
	 * @param array $parameters array containing oldpath, newpath and run
	 */
	public static function onRename($parameters) {
		if (!self::isFileOperationOK($parameters['oldpath'])) {
			$parameters['run'] = false;
		}
	}

	/**
	 * Makes sure that the Home Folder will not be deleted
	 *
	 * @param array $parameters array containing path and run
	 */
	public static function onDelete($parameters) {
		if (!self::isFileOperationOK($parameters['path'])) {
			$parameters['run'] = false;
		}
	}
}
