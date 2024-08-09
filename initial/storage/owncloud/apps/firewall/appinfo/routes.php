<?php
/**
 * @author Clark Tomlinson  <clark@owncloud.com>
 * @since 10/30/14, 1:08 PM
 * @copyright Copyright (c) 2018, ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/
 */

namespace OCA\Firewall\AppInfo;

return [
	'routes' => [
		['name' => 'rules#save', 'url' => '/ajax/save', 'verb' => 'POST'],
		['name' => 'rules#debug', 'url' => '/ajax/debug', 'verb' => 'POST'],
		['name' => 'rules#getUIData', 'url' => '/ajax/getUIData', 'verb' => 'GET'],
	],
];
