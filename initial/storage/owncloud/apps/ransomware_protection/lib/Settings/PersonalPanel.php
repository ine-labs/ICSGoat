<?php
/**
 * ownCloud - Ransomware Protection
 *
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 * @copyright 2017 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Ransomware_Protection\Settings;

use OCA\Ransomware_Protection\AppInfo\Application;
use OCP\Settings\ISettings;

class PersonalPanel implements ISettings {

	/** @var Application */
	protected $app;

	public function __construct(Application $app) {
		$this->app = $app;
	}

	public function getPanel() {
		return $this->app->getContainer()->query('SettingsController')->personalSettings();
	}
	public function getPriority() {
		return 100;
	}
	public function getSectionID() {
		return 'security';
	}
}
