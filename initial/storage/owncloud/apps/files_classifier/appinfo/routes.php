<?php
/**
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license OCL
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

return [
	'routes' => [
		[
			'name' => 'tags#listTags',
			'url' => '/tags',
			'verb' => 'GET',
		],
		[
			'name' => 'tags#setRules',
			'url' => '/rules',
			'verb' => 'PUT',
		],
		[
			'name' => 'tags#listRules',
			'url' => '/rules',
			'verb' => 'GET',
		],
	],
];
