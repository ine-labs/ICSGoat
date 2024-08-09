<?php
/**
 * ownCloud
 *
 * @author Thomas Müller <deepdiver@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@owncloud.com>
 * @copyright (C) 2014-2018 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

use OCP\AppFramework\App;

$application = new App('user_shibboleth');
$application->registerRoutes(
	$this,
	[
		'routes' => [
			[
				'name' => 'Admin#getMode',
				'url' => '/mode',
				'verb' => 'GET',
			],
			[
				'name' => 'Admin#setMode',
				'url' => '/mode',
				'verb' => 'PUT',
			],
			[
				'name' => 'Admin#getEnvSourceConfig',
				'url' => '/envSourceConfig',
				'verb' => 'GET',
			],
			[
				'name' => 'Admin#setEnvSourceConfig',
				'url' => '/envSourceConfig',
				'verb' => 'PUT',
			],
		],
	]
);

/** @var $this OCP\Route\IRouter */
$this->create('shibboleth_set_timezone', 'timezone')
	->method('POST')
	->action('OCA\User_Shibboleth\UserBackend', 'setTimezone');
