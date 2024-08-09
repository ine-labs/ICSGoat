<?php
/**
 * ownCloud - Ransomware Protection
 *
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 * @copyright 2017 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

return [
	'routes' => [
		['name' => 'Settings#lock', 'url' => '/lock', 'verb' => 'GET'],
		['name' => 'Settings#unlock', 'url' => '/unlock', 'verb' => 'GET'],
		['name' => 'Settings#lockingEnabled', 'url' => '/lockingEnabled', 'verb' => 'POST']
	]
];
