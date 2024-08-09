<?php
/**
 * ownCloud
 *
 * @author Jesus Macias Portela <jesus@owncloud.com>
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

OCP\JSON::checkAppEnabled('sharepoint');
OCP\JSON::callCheck();
OCP\JSON::checkAdminUser();

$pattern = (isset($_GET['pattern'])) ? $_GET['pattern'] : '';
$limit = (isset($_GET['limit'])) ? $_GET['limit'] : null;
$offset = (isset($_GET['offset'])) ? $_GET['offset'] : null;

$groups = \OC::$server->getGroupManager()->search($pattern, $limit, $offset);
$groupIds = array_map(function($g){
    return $g->getGID();
}, $groups);

$users = \OCP\User::getDisplayNames($pattern, $limit, $offset);

$results = array('groups' => $groupIds, 'users' => $users);
\OCP\JSON::success($results);
