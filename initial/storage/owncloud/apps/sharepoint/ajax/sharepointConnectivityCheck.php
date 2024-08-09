<?php
/**
 * ownCloud
 *
 * @author Jesus Macias Portela <jesus@owncloud.com>
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

OCP\JSON::checkAppEnabled('sharepoint');
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$l = \OC::$server->getL10N('sharepoint');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	exit();
}

// mountPoint to check
$m = (isset($_POST['m'])) ? OC_Util::sanitizeHTML($_POST['m']) : '';
$t = (isset($_POST['t'])) ? OC_Util::sanitizeHTML($_POST['t']) : '';
$a = (isset($_POST['a'])) ? OC_Util::sanitizeHTML($_POST['a']) : '';


$result = array();
OC::$server->getSession()->close();
$mountPoints = \OC\Files\Filesystem::getMountPoints("/");

if ($m !== '') {
	foreach ($mountPoints as $mount) {
		/** @var \OCA\sharepoint\lib\SHAREPOINT $storage*/
		$storage = \OC\Files\Filesystem::getStorage($mount);
		if ($storage->instanceOfStorage('OCA\sharepoint\lib\SHAREPOINT') &&
			$storage->getMountPoint() === $m
		) {
			$result["message"] = "All OK";
			$result["code"] = 200;
			try {
				$b = $storage->checkConnection();
			} catch (Exception $e) {
				$result["message"] = $e->getMessage();
				$result["code"] = $e->getCode();
			}
			$result["m"] = $m;
			$result["t"] = $t;
			$result["a"] = $a;
			\OCP\JSON::success($result);
			return;
		} else if ($storage->instanceOfStorage('OCA\sharepoint\lib\FakeFilesystem') &&
			$storage->getMountPoint() === $m) {
			$result["message"] = "Fake filesystem";
			$result["code"] = 412;
			\OCP\JSON::success($result);
			return;
		}
	}
}

\OCP\JSON::error([
	'message' => 'Mount not found',
	'code' => '404',
	$result["m"] = $m,
	$result["t"] = $t,
	$result["a"] = $a
]);
