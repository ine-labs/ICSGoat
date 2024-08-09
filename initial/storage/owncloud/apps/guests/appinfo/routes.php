<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
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

return [
	'routes' => [
		[
			'name' => 'register#showPasswordForm',
			'url' => '/register/{email}/{token}',
			'verb' => 'GET',
		],
		[
			'name' => 'register#register',
			'url' => '/register',
			'verb' => 'POST',
		],
		[
			'name' => 'settings#getConfig',
			'url' => '/config',
			'verb' => 'GET',
		],
		[
			'name' => 'settings#setConfig',
			'url' => '/config',
			'verb' => 'PUT',
		],
		[
			'name' => 'settings#getWhitelist',
			'url' => '/whitelist',
			'verb' => 'GET',
		],
		[
			'name' => 'settings#resetWhitelist',
			'url' => '/whitelist/reset',
			'verb' => 'POST',
		],
		[
			'name' => 'users#create',
			'url' => '/users',
			'verb' => 'PUT'
		]
	]
];
