<?php
/**
 * ownCloud
 *
 * @author Frank Karlitscheck <frank@owncloud.com>
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

use OCP\License\ILicenseManager;

$licenseManager = \OC::$server->getLicenseManager();
// check if license app is enabled
if ($licenseManager->checkLicenseFor('files_ldap_home')) {
	try {
		\OCA\Files_LDAP_Home\Helper::checkOperability();
	} catch (\Exception $e) {
		\OCP\Util::writeLog('files_ldap_home',
			$e->getMessage(),
			\OCP\Util::ERROR
		);
		// Disable self to not block the UI
		\OC::$server->getAppManager()->disableApp('files_ldap_home');
		return;
	}

	OCP\Util::connectHook('OC_Filesystem', 'post_initMountPoints',
		'\OCA\Files_LDAP_Home\Storage', 'setup');
	\OCP\Util::connectHook('OC_Filesystem', 'rename',
		'\OCA\Files_LDAP_Home\Helper', 'onRename');
	\OCP\Util::connectHook('OC_Filesystem', 'delete',
		'\OCA\Files_LDAP_Home\Helper', 'onRename');
}
