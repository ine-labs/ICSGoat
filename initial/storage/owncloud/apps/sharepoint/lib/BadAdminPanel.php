<?php

/**
 * ownCloud
 *
 * @author Tom Needham <tom@owncloud.com>
 * @copyright (C) 2017 ownCloud, GmbH.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\sharepoint;

use OCP\App;
use OCP\Settings\ISettings;
use OCP\Template;

class BadAdminPanel implements ISettings
{

	/** @var App  */
	protected $app;

	public function __construct(App $app) {
		$this->app = $app;
	}

	public function getSectionID() {
		return 'storage';
	}

	public function getPriority() {
		return 20;
	}

	public function getPanel() {
		// Only show if degraded settings status, else we show the real settings
		if (!class_exists('SoapClient') || !function_exists('curl_version')) {
			return new Template('sharepoint', 'badSettings');
		} else {
			return null;
		}
	}

}
