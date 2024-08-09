<?php
/**
 * ownCloud Workflow
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright 2016 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

$licenseManager = \OC::$server->getLicenseManager();
if ($licenseManager->checkLicenseFor('workflow')) {
	$app = new \OCA\Workflow\AppInfo\Application();
	$app->registerListeners();
}
