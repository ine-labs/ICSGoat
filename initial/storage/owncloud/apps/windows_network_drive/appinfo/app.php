<?php
/**
 * ownCloud
 *
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 * @author Jesus Macias Portela <jesus@owncloud.com>
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\windows_network_drive\AppInfo;

$licenseManager = \OC::$server->getLicenseManager();
// check if license is enabled
if ($licenseManager->checkLicenseFor('windows_network_drive')) {
	//Enable file externals because is a dependency
	if (!\OCP\App::isEnabled('files_external')) {
		\OC_App::enable('files_external');
	}

	\OCP\Util::connectHook('OC_Filesystem', 'preSetup', 'OCA\windows_network_drive\lib\Hooks', 'loadWNDBackend');
	$app = new Application();
	if (\method_exists($app, 'setupSymfonyEventListeners')) {
		// this could happen during the WND upgrade 0.7.4 -> 1.0.1
		// the "setupSymfonyEventListeners" method didn't exist in 0.7.4
		// upgrade routines shouldn't be affected by the existence of this method
		$app->setupSymfonyEventListeners();
	}
	if (\method_exists($app, 'registerExtensions')) {
		// same case as with the setupSymfonyEventListeners method
		// upgrade routines shouldn't be affected by the existence of this method
		$app->registerExtensions();
	}
}
