<?php
/**
 * ownCloud Workflow
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright 2015 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

return [
	'routes' => [
		['name' => 'retention#getRetentionPeriods', 'url' => '/retention', 'verb' => 'GET'],
		['name' => 'retention#addRetentionPeriod', 'url' => '/retention/{tagId}', 'verb' => 'POST'],
		['name' => 'retention#updateRetentionPeriod', 'url' => '/retention/{tagId}', 'verb' => 'PUT'],
		['name' => 'retention#deleteRetentionPeriod', 'url' => '/retention/{tagId}', 'verb' => 'DELETE'],
		['name' => 'flow#getWorkFlows', 'url' => '/flow', 'verb' => 'GET'],
		['name' => 'flow#addWorkFlow', 'url' => '/flow', 'verb' => 'POST'],
		['name' => 'flow#updateWorkFlow', 'url' => '/flow/{flowId}', 'verb' => 'PUT'],
		['name' => 'flow#deleteWorkFlow', 'url' => '/flow/{flowId}', 'verb' => 'DELETE'],
		['name' => 'flow#getConditionValuesAndTypes', 'url' => '/conditions', 'verb' => 'GET'],
	]
];
