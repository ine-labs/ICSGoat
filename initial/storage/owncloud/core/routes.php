<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

use OC\Core\Application;

$application = new Application();
$application->registerRoutes($this, [
	'routes' => [
		['name' => 'lost#email', 'url' => '/lostpassword/email', 'verb' => 'POST'],
		['name' => 'lost#resetform', 'url' => '/lostpassword/reset/form/{token}/{userId}', 'verb' => 'GET'],
		['name' => 'lost#setPassword', 'url' => '/lostpassword/set/{token}/{userId}', 'verb' => 'POST'],
		['name' => 'user#getDisplayNames', 'url' => '/displaynames', 'verb' => 'POST'],
		['name' => 'avatar#getAvatar', 'url' => '/avatar/{userId}/{size}', 'verb' => 'GET'],
		['name' => 'avatar#deleteAvatar', 'url' => '/avatar/', 'verb' => 'DELETE'],
		['name' => 'avatar#postCroppedAvatar', 'url' => '/avatar/cropped', 'verb' => 'POST'],
		['name' => 'avatar#getTmpAvatar', 'url' => '/avatar/tmp', 'verb' => 'GET'],
		['name' => 'avatar#postAvatar', 'url' => '/avatar/', 'verb' => 'POST'],
		['name' => 'login#tryLogin', 'url' => '/login', 'verb' => 'POST'],
		['name' => 'login#showLoginForm', 'url' => '/login', 'verb' => 'GET'],
		['name' => 'login#logout', 'url' => '/logout', 'verb' => 'GET'],
		['name' => 'token#generateToken', 'url' => '/token/generate', 'verb' => 'POST'],
		['name' => 'OC\Core\Controller\Occ#execute', 'url' => '/occ/{command}', 'verb' => 'POST'],
		['name' => 'TwoFactorChallenge#selectChallenge', 'url' => '/login/selectchallenge', 'verb' => 'GET'],
		['name' => 'TwoFactorChallenge#showChallenge', 'url' => '/login/challenge/{challengeProviderId}', 'verb' => 'GET'],
		['name' => 'TwoFactorChallenge#solveChallenge', 'url' => '/login/challenge/{challengeProviderId}', 'verb' => 'POST'],
		['name' => 'Cron#run', 'url' => '/cron', 'verb' => 'GET'],
		['name' => 'License#getGracePeriod', 'url' => '/license/graceperiod', 'verb' => 'GET'],
		['name' => 'License#setNewLicense', 'url' => '/license/license', 'verb' => 'POST'],
		['name' => 'License#removeLicense', 'url' => '/license/license', 'verb' => 'DELETE'],
		['name' => 'License#getLicenseMessage', 'url' => '/license/licenseMessage', 'verb' => 'GET'],
	],
	'ocs' => [
		['root' => '/cloud', 'name' => 'Cloud#getCapabilities', 'url' => '/capabilities', 'verb' => 'GET'],
		['root' => '/cloud', 'name' => 'Cloud#getCurrentUser', 'url' => '/user', 'verb' => 'GET'],
		['root' => '/cloud', 'name' => 'Cloud#getSigningKey', 'url' => '/user/signing-key', 'verb' => 'GET'],
		['root' => '/cloud', 'name' => 'Roles#getRoles', 'url' => '/roles', 'verb' => 'GET'],
		['root' => '/cloud', 'name' => 'UserSync#syncUser', 'url' => '/user-sync/{userId}', 'verb' => 'POST'],
		// OCS Config
		['root' => '', 'name' => 'OC\Core\Controller\Ocs#getConfig', 'url' => '/config', 'verb' => 'GET'],
		// OCS Person
		['root' => '', 'name' => 'OC\Core\Controller\Ocs#checkPerson', 'url' => '/person/check', 'verb' => 'POST'],
		// OCS Privatedata
		['root' => '', 'name' => 'OC\Core\Controller\Ocs#getDefaultAttributes', 'url' => '/privatedata/getattribute', 'verb' => 'GET'],
		['root' => '', 'name' => 'OC\Core\Controller\Ocs#getAppAttributes', 'url' => '/privatedata/getattribute/{app}', 'verb' => 'GET'],
		['root' => '', 'name' => 'OC\Core\Controller\Ocs#getAttribute', 'url' => '/privatedata/getattribute/{app}/{key}', 'verb' => 'GET'],
		['root' => '', 'name' => 'OC\Core\Controller\Ocs#setAttribute', 'url' => '/privatedata/setattribute/{app}/{key}', 'verb' => 'POST'],
		['root' => '', 'name' => 'OC\Core\Controller\Ocs#deleteAttribute', 'url' => '/privatedata/deleteattribute/{app}/{key}', 'verb' => 'POST'],
	]
]);

// Post installation check

/** @var $this OCP\Route\IRouter */
// Core ajax actions
// Search
$this->create('search_ajax_search', '/core/search')
	->actionInclude('core/search/ajax/search.php');
// AppConfig
$this->create('core_ajax_appconfig', '/core/ajax/appconfig.php')
	->actionInclude('core/ajax/appconfig.php');
// Share
$this->create('core_ajax_share', '/core/ajax/share.php')
	->actionInclude('core/ajax/share.php');
// oC JS config
$this->create('js_config', '/core/js/oc.js')
	->actionInclude('core/js/config.php');
// Routing
$this->create('core_ajax_update', '/core/ajax/update.php')
	->actionInclude('core/ajax/update.php');

// File routes
$this->create('files.viewcontroller.showFile', '/f/{fileId}')->action(static function ($urlParams) {
	$webBaseUrl = \OC::$server->getConfig()->getSystemValue('web.baseUrl', null);
	if (!$webBaseUrl) {
		// Check the old phoenix.baseUrl system key to provide compatibility across the name change
		$webBaseUrl = \OC::$server->getConfig()->getSystemValue('phoenix.baseUrl', null);
	}
	if (isWebRewriteLinksEnabled()) {
		$webBaseUrl = \rtrim($webBaseUrl, '/');
		$fileId = $urlParams['fileId'];
		\OC_Response::redirect("$webBaseUrl/index.html#/f/$fileId");
		return;
	}
	$app = new \OCA\Files\AppInfo\Application($urlParams);
	$app->dispatch('ViewController', 'showFile');
});

// Sharing routes
$this->create('files_sharing.sharecontroller.showShare', '/s/{token}')->action(static function ($urlParams) {
	$webBaseUrl = \OC::$server->getConfig()->getSystemValue('web.baseUrl', null);
	if (!$webBaseUrl) {
		// Check the old phoenix.baseUrl system key to provide compatibility across the name change
		$webBaseUrl = \OC::$server->getConfig()->getSystemValue('phoenix.baseUrl', null);
	}
	if (isWebRewriteLinksEnabled()) {
		$webBaseUrl = \rtrim($webBaseUrl, '/');
		$token = $urlParams['token'];
		\OC_Response::redirect("$webBaseUrl/index.html#/s/$token");
		return;
	}
	$app = new \OCA\Files_Sharing\AppInfo\Application($urlParams);
	$app->dispatch('ShareController', 'showShare');
});
$this->create('files_sharing.sharecontroller.authenticate', '/s/{token}/authenticate')->post()->action(function ($urlParams) {
	$app = new \OCA\Files_Sharing\AppInfo\Application($urlParams);
	$app->dispatch('ShareController', 'authenticate');
});
$this->create('files_sharing.sharecontroller.showAuthenticate', '/s/{token}/authenticate')->get()->action(function ($urlParams) {
	$app = new \OCA\Files_Sharing\AppInfo\Application($urlParams);
	$app->dispatch('ShareController', 'showAuthenticate');
});
$this->create('files_sharing.sharecontroller.downloadShare', '/s/{token}/download')->get()->action(function ($urlParams) {
	$app = new \OCA\Files_Sharing\AppInfo\Application($urlParams);
	$app->dispatch('ShareController', 'downloadShare');
});

// used for heartbeat
$this->create('heartbeat', '/heartbeat')->action(function () {
	$expire = \OC::$server->getRequest()->getParam('t');
	\OC::$server->getSessionCryptoWrapper()->refreshCookie(\OC::$server->getConfig(), $expire);
});

/**
 * Asserts whether rewriting private and public links to the ownCloud Web UI is enabled.
 *
 * Since we have two different deployment modes - external or as oc10 app -
 * we can't just decide based on whether or not the app is enabled. Only if it's
 * installed at all, we return false on the disabled app.
 *
 * @return bool
 */
function isWebRewriteLinksEnabled(): bool {
	if (\OC::$server->getAppManager()->isInstalled('web')
		&& !\OC::$server->getAppManager()->isEnabledForUser('web')) {
		return false;
	}
	return \OC::$server->getConfig()->getSystemValue('web.rewriteLinks', false);
}
