<?php
/**
 * ownCloud
 *
 * @author Thomas Müller <deepdiver@owncloud.com>
 * @author Frank Karlitscheck <frank@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@owncloud.com>
 * @copyright (C) 2014-2018 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

$licenseManager = \OC::$server->getLicenseManager();
// check if license is enabled
if ($licenseManager->checkLicenseFor('user_shibboleth')) {
	$userBackend = \OCA\User_Shibboleth\UserBackendFactory::createForStaticLegacyCode();

	if ($userBackend !== null) {
		$isAutoProvision = ($userBackend->getMode() === \OCA\User_Shibboleth\UserBackendFactory::MODE_AUTOPROVISION);

		// If the user is logged in, add the JS file to fix the logout button
		if (\OC::$server->getUserSession()->isLoggedIn() && $userBackend->getCurrentUserId() !== null) {
			\OCP\Util::addScript('user_shibboleth', 'shibboleth');
		}

		// Make sure ownCloud knows of the shibboleth backend
		\OC_User::useBackend($userBackend);

		if (\OC::$server->getUserSession()->getUser() !== null) {
			if (!\OC::$server->getSession()->exists('timezone')) {
				\OCP\Util::addScript('user_shibboleth', 'timezone');
			}
		}
	}
}
