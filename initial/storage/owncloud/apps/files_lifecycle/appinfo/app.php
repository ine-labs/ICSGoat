<?php
/**
 * ownCloud
 *
 * @author Tom Needham <tom@owncloud.com>
 * @author Michael Barz <mbarz@owncloud.com>
 * @copyright (C) 2018 ownCloud GmbH
 * @license ownCloud Commercial License
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see
 * <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Files_Lifecycle\Appinfo;

// @codeCoverageIgnoreStart
use OCA\Files_Lifecycle\Application;

if (\OC::$server->getLicenseManager()->checkLicenseFor(Application::APPID)) {
	$a = new \OCA\Files_Lifecycle\Application();
	$a->registerMountProviders();
	$a->registerFileHooks();
	$a->registerUserHooks();
	$a->registerActivityExtension();
	$a->registerFilesUIPlugins();
}

// Log error if holiding_period is enabled - this app replaces it
if (\OC::$server->getAppManager()->isEnabledForUser('holding_period')) {
	// Disable this app
	\OC::$server->getAppManager()->disableApp(Application::APPID);
	throw new \Exception('holding_period must be disabled before using files_lifecycle! Disabling files_lifecycle.');
}
// @codeCoverageIgnoreEnd
