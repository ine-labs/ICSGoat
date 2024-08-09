<?php
/**
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

use OCA\Admin_Audit\Helper;
use OCP\AppFramework\App;

class Base {
	/**
	 * @return \OCA\Admin_Audit\Logger
	 */
	protected static function getLogger() {
		$app = new App('admin_audit');
		return $app->getContainer()->query('OCA\Admin_Audit\Logger');
	}

	/**
	 * @return \OCA\Admin_Audit\Helper
	 */
	protected static function getHelper() {
		return new Helper(
			\OC::$server->getUserSession(),
			\OC::$server->getGroupManager(),
			\OC::$server->getConfig(),
			\OC::$server->getLogger(),
			\OC::$server->getUserManager()
		);
	}
}
