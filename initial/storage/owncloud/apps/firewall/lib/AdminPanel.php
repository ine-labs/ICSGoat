<?php
/**
 * ownCloud Firewall
 *
 * @author Clark Tomlinson <clark@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright Copyright (c) 2018, ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Firewall;

use OCP\Settings\ISettings;
use OCP\Template;

class AdminPanel implements ISettings {
	public function getPanel() {
		$t = new Template('firewall', 'settings');
		return $t;
	}

	public function getSectionID() {
		return 'security';
	}

	public function getPriority() {
		return 0;
	}
}
