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
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

use \OCA\sharepoint\lib\ConfigMgmt;

$l = \OC::$server->getL10N('sharepoint');

if (!isset($_POST['SPMountId']) || !isset($_POST['type']) || !isset($_POST['authType'])) {
    \OCP\JSON::error(array('data' => array('message' => 'Missing mandatory parameters')));
    exit();
}

$_POST['SPuser'] = (isset($_POST['SPuser'])) ? $_POST['SPuser'] : '';
$_POST['SPpass'] = (isset($_POST['SPpass'])) ? $_POST['SPpass'] : '';

$mountData = false;

if($_POST['type'] === 'global') {
    $mountData = ConfigMgmt::getMountById($_POST['SPMountId']);
} else if ($_POST['type'] === 'personal'){
    $mountData = ConfigMgmt::getPersonalMountByIdAndUser($_POST['SPMountId'], \OCP\User::getUser());
}

if ($mountData !== false && ($row = $mountData->fetchRow()) !== false) {

    $params = array('apiurl' => $row['url'],
            'user' => $_POST['SPuser'],
            'password' => $_POST['SPpass'],
            'mountPoint' => $row['SPMountPoint'],
            'listName' => $row['selectList']);

    $sp = new \OCA\sharepoint\lib\SHAREPOINT($params);

    try{
        if ($sp->checkConnection()) {
            $encryptedPass = ConfigMgmt::encryptPassword($_POST['SPpass']);
            if($_POST['type'] === 'global') {
                ConfigMgmt::save_custom_credentials($_POST['SPMountId'], \OCP\User::getUser(), $_POST['SPuser'], $encryptedPass);
                $sp->getCache()->clear();
            } else if($_POST['type'] === 'personal' && $_POST['authType'] === '1' ) {
                ConfigMgmt::updatePersonalMountCredentials($_POST['SPMountId'], $_POST['SPuser'], $encryptedPass, $row['auth_type']);
                $sp->getCache()->clear();
            } else if($_POST['type'] === 'personal' && $_POST['authType'] === '2' ) {
                ConfigMgmt::set_user_credentials(\OCP\User::getUser(), $_POST['SPuser'], $_POST['SPpass']);
                $sp->getCache()->clear();
            }
        }else {
            \OCP\JSON::error(array('data' => array('message' => $l->t('Cannot access the server with the provided information'))));
            exit();
        }
    } catch (\Exception $e) {
        \OCP\JSON::error(array('data' => array('message' => $l->t('Cannot access the server with the provided information'))));
        exit();
    }

} else {
    \OCP\JSON::error(array('data' => array('message' => $l->t('Error accessing the DB'))));
    exit();
}
\OCP\JSON::success(array('data' => 'ok'));
