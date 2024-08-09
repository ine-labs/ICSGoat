<?php
/**
 * @author Benedikt Kulmann <bkulmann@owncloud.com>
 * @copyright (C) 2019 ownCloud GmbH
 * @license ownCloud Commercial License
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

use OCA\Metrics\Application;

if ((@include __DIR__ . '/../vendor/autoload.php') === false) {
	throw new Exception('Cannot include autoload. Did you run install dependencies using composer?');
}

if (\OC::$server->getLicenseManager()->checkLicenseFor(Application::APPID)) {
	$app = new Application();
	$app->addNavigationEntry();
}
