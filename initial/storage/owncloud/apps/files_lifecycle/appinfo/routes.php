<?php
/**
 * ownCloud
 *
 * @author Tom Needham <tom@owncloud.com>
 * @author Michael Barz <mbarz@owncloud.com>
 * @copyright (C) 2018 ownCloud GmbH
 * @license ownCloud Commercial License
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see
 * <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Files_Lifecycle\AppInfo;

$application = new \OCA\Files_Lifecycle\Application();

$application->registerRoutes(
	$this, // @phan-suppress-current-line PhanUndeclaredVariable
	[
		'routes' => [
			[
				'name' => 'restore#restore',
				'url' => '/restore',
				'verb' => 'POST'
			],
		]
	]
);
