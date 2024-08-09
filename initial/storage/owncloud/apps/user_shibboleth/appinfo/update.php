<?php
/**
 * ownCloud
 *
 * @author Jörn Friedrich Dreyer <jfd@owncloud.com>
 * @copyright (C) 2015-2018 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

use OCA\User_Shibboleth\UserBackendFactory;

$installedVersion = \OC::$server->getConfig()->getAppValue('user_shibboleth', 'installed_version');

if (\version_compare($installedVersion, '2.0', '<')) {
	// activation config has changed
	if (\OC::$server->getConfig()->getAppValue(
		'user_shibboleth', 'shibboleth_active', false)
	) {
		\OC::$server->getConfig()->setAppValue(
			'user_shibboleth', 'mode', UserBackendFactory::MODE_AUTOPROVISION
		);
		\OC::$server->getConfig()->deleteAppValue(
			'user_shibboleth', 'shibboleth_active'
		);
	}
}
