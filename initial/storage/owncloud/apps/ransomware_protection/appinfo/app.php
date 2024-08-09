<?php
/**
 * ownCloud - Ransomware Protection
 *
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 * @copyright 2020 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Ransomware_Protection\AppInfo;

use OCA\Ransomware_Protection\Activity;
use OCP\Util;

require_once __DIR__ . '/autoload.php';

$licenseManager = \OC::$server->getLicenseManager();
if ($licenseManager->checkLicenseFor('ransomware_protection')) {
	$app = new Application();
	$app->getContainer()->query('RootHooks')->register();

	\OC::$server->getActivityManager()->registerExtension(function () {
		return new Activity(
			\OC::$server->query('L10NFactory'),
			\OC::$server->getURLGenerator()
		);
	});

	$userObject = \OC::$server->getUserSession()->getUser();
	if ($userObject !== null) {
		if ($app->getContainer()->query('Blocker')->isLocked()) {
			Util::addScript('ransomware_protection', 'notification');
		}
	}

	// Storage wrapper for Blacklist/Locker
	Util::connectHook('OC_Filesystem', 'preSetup', $app, 'setupStorage');
}
