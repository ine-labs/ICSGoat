<?php
/**
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

use OCA\PasswordPolicy\HooksHandler;

$handler = new HooksHandler();

OCP\Util::connectHook(
	'\OC\Share',
	'verifyExpirationDate',
	$handler,
	'updateLinkExpiry'
);

$eventDispatcher = \OC::$server->getEventDispatcher();
$eventDispatcher->addListener(
	'OCP\User::validatePassword',
	[$handler, 'verifyUserPassword']
);
$eventDispatcher->addListener(
	'OCP\Share::validatePassword',
	[$handler, 'verifyPublicPassword']
);
$eventDispatcher->addListener(
	'OCP\User::createPassword',
	[$handler, 'generatePassword']
);
$eventDispatcher->addListener(
	'user.aftercreate',
	[$handler, 'savePasswordForCreatedUser']
);
$eventDispatcher->addListener(
	'user.aftersetpassword',
	[$handler, 'saveOldPassword']
);
$eventDispatcher->addListener(
	'user.afterlogin',
	[$handler, 'checkPasswordExpired']
);
$eventDispatcher->addListener(
	'user.afterlogin',
	[$handler, 'checkAdminRequestedPasswordChange']
);
$eventDispatcher->addListener(
	\OCP\IUser::class . '::firstLogin',
	[$handler, 'checkForcePasswordChangeOnFirstLogin']
);
$eventDispatcher->addListener(
	'user.afterdelete',
	[$handler, 'removeUserEntriesFromTable']
);

$app = new \OCA\PasswordPolicy\AppInfo\Application();
$app->registerNotifier();

// only load notification JS code in the logged in layout page (not public links not login page)
$request = \OC::$server->getRequest();
if (\OC::$server->getUserSession() !== null && \OC::$server->getUserSession()->getUser() !== null
	&& \substr($request->getScriptName(), 0 - \strlen('/index.php')) === '/index.php'
	&& \substr($request->getPathInfo(), 0, \strlen('/s/')) !== '/s/'
	&& \substr($request->getPathInfo(), 0, \strlen('/login')) !== '/login') {
	\OCP\Util::addScript('password_policy', 'notification');
}
