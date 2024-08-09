<?php
/**
 * @author Sujith Haridasan <sharidasan@owncloud.com>
 * @copyright (C) 2019 ownCloud GmbH
 * @license ownCloud Commercial License
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

use OCA\Metrics\Application;

$app = new Application();
$app->registerRoutes(
	$this, // @phan-suppress-current-line PhanUndeclaredThis @phpstan-ignore-line
	[
		// user metrics end point
		'ocs' => [
			['name' => 'UserMetrics#getMetrics', 'url' => '/api/v1/metrics'],
		],
		'routes' => [
			// DEPRECATED has been displaced by /download-web/users route
			// using lower snake case here to avoid identical names which would result in errors
			['name' => 'Download#download_user_metrics_as_admin', 'url' => '/download-web'],
			// DEPRECATED has been displaced by /download-api/users route
			// using lower snake case here to avoid identical names which would result in errors
			['name' => 'Download#download_user_metrics_as_guest', 'url' => '/download-api'],

			['name' => 'Download#downloadUserMetricsAsAdmin', 'url' => '/download-web/users'],
			['name' => 'Download#downloadUserMetricsAsGuest', 'url' => '/download-api/users'],
			['name' => 'Download#downloadSystemMetricsAsAdmin', 'url' => '/download-web/system'],
			['name' => 'Download#downloadSystemMetricsAsGuest', 'url' => '/download-api/system'],
			['name' => 'Page#get', 'url' => '/metrics'],
			['name' => 'Page#token', 'url' => '/token']
		]
	]
);
