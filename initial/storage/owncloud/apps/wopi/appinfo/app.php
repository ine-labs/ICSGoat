<?php
/**
 * ownCloud Wopi
 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @copyright 2018 ownCloud GmbH.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\WOPI\AppInfo;

$licenseManager = \OC::$server->getLicenseManager();
if ($licenseManager->checkLicenseFor('wopi')) {
	$app = new Application();
	$app->registerScripts();
}
