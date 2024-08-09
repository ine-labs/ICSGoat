<?php
/**
 * ownCloud
 *
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

// Check user and app status
OCP\JSON::checkAdminUser();
OCP\JSON::checkAppEnabled('files_ldap_home');
OCP\JSON::callCheck();

$settings = new \OCA\Files_LDAP_Home\Settings(\OC::$server->getConfig());
$ok = true;
foreach ($_POST as $key => $value) {
	$key = \str_replace('filesLdapHome', '', $key);
	try {
		$settings->$key = $value;
	} catch (Exception $e) {
		\OCP\Util::writeLog('files_ldap_home',
			'Error on settings save: '.$e->getMessage(), \OCP\Util::WARN);
		$ok = false;
	}
}
if ($ok) {
	\OCP\JSON::success();
} else {
	\OCP\JSON::error();
}
