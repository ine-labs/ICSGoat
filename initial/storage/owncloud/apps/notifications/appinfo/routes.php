<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

return [
	'ocs' => [
		[
			'name' => 'Endpoint#listNotifications',
			'url' => '/api/v1/notifications',
			'verb' => 'GET'
		],
		[
			'name' => 'Endpoint#getNotification',
			'url' => '/api/v1/notifications/{id}',
			'verb' => 'GET'
		],
		[
			'name' => 'Endpoint#deleteNotification',
			'url' => '/api/v1/notifications/{id}',
			'verb' => 'DELETE'
		],
	],

	'routes' => [
		['name' => 'NotificationOptions#getNotificationOptions', 'url' => '/settings/personal/notifications/options', 'verb' => 'GET'],
		['name' => 'NotificationOptions#setNotificationOptions', 'url' => '/settings/personal/notifications/options', 'verb' => 'PUT'],
		['name' => 'NotificationOptions#setNotificationOptionsPartial', 'url' => '/settings/personal/notifications/options', 'verb' => 'PATCH'],
	],
];
