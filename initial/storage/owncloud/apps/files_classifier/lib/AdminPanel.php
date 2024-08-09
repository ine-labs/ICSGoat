<?php
/**
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license OCL
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\FilesClassifier;

use OCP\Settings\ISettings;
use OCP\Template;

class AdminPanel implements ISettings {
	public function getPriority() {
		return 1;
	}
	public function getPanel() {
		$tmpl = new Template('files_classifier', 'settings/admin');
		return $tmpl;
	}

	public function getSectionID() {
		return 'workflow';
	}
}
