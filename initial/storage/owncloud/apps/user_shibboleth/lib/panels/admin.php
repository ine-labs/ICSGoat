<?php
/**
 * ownCloud
 *
 * @author Tom Needham <tom@owncloud.com>
 * @copyright (C) 2017-2018 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\User_Shibboleth\Panels;

use OCP\Settings\ISettings;
use OCP\Template;

class Admin implements ISettings {
	public function getPriority() {
		return 10;
	}

	public function getPanel() {
		$tmpl = new Template('user_shibboleth', 'settings/admin');
		$tmpl->assign('env', $_SERVER);
		return $tmpl;
	}

	public function getSectionID() {
		return 'authentication';
	}
}
