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

use OCP\License\ILicenseManager;

require_once __DIR__ . '/../vendor/autoload.php';

$licenseManager = \OC::$server->getLicenseManager();
// check if license app is enabled
if ($licenseManager->checkLicenseFor('files_classifier')) {
	$app = new \OCA\FilesClassifier\Application('files_classifier');
	OCP\Util::connectHook('OC_Filesystem', 'preSetup', $app, 'setupWrapper');
}
