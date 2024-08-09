<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
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
namespace OCA\Guests;

use OCP\Template;

/**
 * Only allow whitelisted apps to be accessed by guests
 *
 * @package OCA\Guests
 */
class AppWhitelist {
	public const CORE_WHITELIST = ',core,files,guests';
	public const DEFAULT_WHITELIST = 'settings,avatar,files_external,files_trashbin,files_versions,files_sharing,files_texteditor,activity,firstrunwizard,gallery,notifications,password_policy,oauth2,files_pdfviewer,files_mediaviewer,richdocuments,onlyoffice,wopi,oco_selfservice';

	public static function preSetup($params) {
		$uid = $params['user'];

		if (empty($uid)) {
			return;
		}

		$config = \OC::$server->getConfig();
		$isGuest = $config->getUserValue($uid, 'owncloud', 'isGuest', false);
		$whitelistEnabled = $config->getAppValue('guests', 'usewhitelist', 'true') === 'true';

		if ($isGuest && $whitelistEnabled) {
			$path = \OC::$server->getRequest()->getRawPathInfo();
			$app = self::getRequestedApp($path);
			$whitelist = self::getWhitelist();

			if (!\in_array($app, $whitelist)) {
				\header('HTTP/1.0 403 Forbidden');
				$l = \OC::$server->getL10NFactory()->get('guests');
				Template::printErrorPage($l->t(
					'Access to this resource is forbidden for guests.'
				));
				exit;
			}
		}
	}

	public static function getWhitelist() {
		$whitelist = self::CORE_WHITELIST;
		$whitelist .=  ',' . \OC::$server->getConfig()->getAppValue(
			'guests',
			'whitelist',
			self::DEFAULT_WHITELIST
		);

		return \explode(',', $whitelist);
	}

	/**
	 * Core has \OC::$REQUESTEDAPP but it isn't set until the routes are matched
	 * taken from \OC\Route\Router::match()
	 */
	private static function getRequestedApp($url) {
		if (\substr($url, 0, 6) === '/apps/') {
			// empty string / 'apps' / $app / rest of the route
			list(, , $app, ) = \explode('/', $url, 4);
			return  \OC_App::cleanAppId($app);
		} elseif (\substr($url, 0, 6) === '/core/') {
			return 'core';
		} elseif (\substr($url, 0, 10) === '/settings/') {
			return 'settings';
		} elseif (\substr($url, 0, 8) === '/avatar/') {
			return 'avatar';
		} elseif (\substr($url, 0, 10) === '/heartbeat') {
			return 'heartbeat';
		} elseif (\substr($url, 0, 13) === '/dav/comments') {
			return 'comments';
		}
		return false;
	}
}
