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
use \OCA\sharepoint\lib\Utils;

$l = \OC::$server->getL10N('sharepoint');

require_once __DIR__ . '/../3rdparty/libSharepoint/src/sharepoint/SoapSharepointWrapper.php';
require_once __DIR__ . '/../3rdparty/libSharepoint/src/sharepoint/Auth/SoapClientAuthNTLM.php';

$mandatoryParams = array('o');

if (!Utils::checkParams($_POST, $mandatoryParams)){
    \OCP\JSON::error(array('message' => 'Operation is not defined'));
    exit();
}

/* Ajax request */
/* getDocumentList: Use to retrieve document list list */
if (Utils::checkParams($_POST, array('url')) && $_POST['o'] == 'getDocumentList') {
    \OCP\Util::writeLog('sharepoint', 'Get document list from site '.$_POST['url'], \OCP\Util::DEBUG);
    $gcreds = Array ($_POST['u'],$_POST['p']);
    //Use user credentials
    if($_POST['a'] === "2"){
        $gcreds = ConfigMgmt::get_password_for_user(OC_User::getUser());
    }
    try {
        $parsed = parse_url($_POST['url']);
        $parsed["path"] = (isset($parsed["path"])) ? $parsed["path"] : "";
        $client = new \sharepoint\SoapSharepointWrapper($gcreds[0], $gcreds[1], $parsed['scheme'].'://'.$parsed['host'].'/'.rawurlencode(ltrim($parsed['path'], '/')), '2010');
        $response = $client->getListCollection();
    } catch (Exception $e) {
        OCP\JSON::error(array('status' => 'error'));
        exit();
    }
    OCP\JSON::success(array('message' => $response));
}
/* setUserGlobalCredentials: Store user global credentials */
else if ($_POST['o'] == 'setUserGlobalCredentials'){
    \OCP\Util::writeLog('sharepoint', 'setUserGlobalCredentials: '.$_POST['u'], \OCP\Util::DEBUG);
    $pass = null;
    if($_POST['c'] === "true"){
        $pass = $_POST['p'];
    }
    ConfigMgmt::set_user_credentials(OC_User::getUser(), $_POST['u'], $pass);
    $status = '200';
    OCP\JSON::success(array('data' => array('message' => $status)));
}
/* getMountPointForUser: Return array with user mount points*/
else if (Utils::checkParams($_POST, array('type')) && $_POST['o'] == 'getMountPointForUser'){
    \OCP\Util::writeLog('sharepoint', 'getMountPointForUser type: '.$_POST['type'], \OCP\Util::DEBUG);
    $type = $_POST['type'];
    $mounts = array();
    $user = \OCP\User::getUser();
    $mountList = false;

    if ($type === 'personal') {
        $mountList = ConfigMgmt::get_personal_mounts_per_user($user);
    } else if ($type === 'admin') {
        $mountList = ConfigMgmt::get_mounts_for_user($user, array_keys(\OC::$server->getGroupManager()->getUserIdGroups($user)));
    }
    if ($mountList !== false) {
        while(($row = $mountList->fetchRow()) !== false) {
            $mounts[] = $row;
        }
        \OCP\JSON::success(array("data" => $mounts));
    } else {
        \OCP\JSON::error(array("message" => "Error accessing the DB"));
        exit();
    }
}
/* saveUserCredentials: Save user custom credentials for a mountpoint*/
else if (Utils::checkParams($_POST, array('SPMountId')) && $_POST['o'] == 'saveUserCredentials'){
    \OCP\Util::writeLog('sharepoint', 'saveUserCredentials: '.$_POST['SPuser'], \OCP\Util::DEBUG);

    $_POST['SPuser'] = (isset($_POST['SPuser'])) ? $_POST['SPuser'] : '';
    $_POST['SPpass'] = (isset($_POST['SPpass'])) ? $_POST['SPpass'] : '';

    $mountData = ConfigMgmt::getMountById($_POST['SPMountId']);
    if ($mountData !== false) {
        if (($row = $mountData->fetchRow()) !== false) {
            $params = array('apiurl' => $row['url'],
                        'user' => $_POST['SPuser'],
                        'password' => $_POST['SPpass'],
                        'mountPoint' => $row['SPMountPoint'],
                        'listName' => $row['selectList']);
            $sp = new \OCA\sharepoint\lib\SHAREPOINT($params);
            try{
                if ($sp->checkConnection()) {
                    $encryptedPass = ConfigMgmt::encryptPassword($_POST['SPpass']);
                    $mountingUser = \OCP\User::getUser();
                    ConfigMgmt::save_custom_credentials($_POST['SPMountId'], $mountingUser, $_POST['SPuser'], $encryptedPass);
                    $sp->getCache()->clear();
                }
            } catch (\Exception $e) {
                \OCP\JSON::error(array("data" => array("message" => $l->t("Cannot access the server with the provided information"))));
                exit();
            }
        } else {
            \OCP\JSON::error(array("data" => array("message" => $l->t("Mount data not found"))));
            exit();
        }
    } else {
        \OCP\JSON::error(array("data" => array("message" => $l->t("Error accessing the DB"))));
        exit();
    }
    \OCP\JSON::success(array("data" => "ok"));
}
/* savePersonalMountPoint: Save personal mountpoint*/
else if (Utils::checkParams($_POST, array('SPMountPoint', 'SPUrl', 'selectList', 'authType')) && $_POST['o'] == 'savePersonalMountPoint'){
    \OCP\Util::writeLog('sharepoint', 'Add personal mount point to db.', \OCP\Util::DEBUG);

    //Normalice Sharepoint Site URL
    $_POST['SPUrl'] = rtrim($_POST['SPUrl'], "/");

    if(preg_match('/[\\/\<\>:"|?*]/', $_POST['SPMountPoint']) === 1){
        \OCP\JSON::error(array("data" => array("message" => $l->t("Local Folder Name is not valid. Characters \\, \/, <, >, :, \", |, ? and * are not allowed."))));
        exit();
    }

    $user = \OCP\User::getUser();
    $current_personal_mountpoints = ConfigMgmt::get_mounts_for_user($user, array_keys(\OC::$server->getGroupManager()->getUserIdGroups($user)));
    foreach ($current_personal_mountpoints as $mountPoint) {
        if ($mountPoint['mount_point'] == $_POST['SPMountPoint']){
            \OCP\JSON::error(array("data" => array("message" => $l->t("A SharePoint mount point with that name already exists"))));
            exit();
        }
    }

    $current_global_mountpoints = ConfigMgmt::get_global_mounts();
    foreach ($current_global_mountpoints as $mountPoint) {
        if ($mountPoint['mount_point'] == $_POST['SPMountPoint']){
            \OCP\JSON::error(array("data" => array("message" => $l->t("A global SharePoint mount point with that name already exists"))));
            exit();
        }
    }

    // global username and password can be empty to connect as guest
    $_POST['SPGuser'] = (isset($_POST['SPGuser'])) ? $_POST['SPGuser'] : '';
    $_POST['SPGpass'] = (isset($_POST['SPGpass'])) ? $_POST['SPGpass'] : '';

    $user = \OCP\User::getUser();

    switch ($_POST['authType']) {
        case "1":
            $params = array('apiurl' => $_POST['SPUrl'],
                        'user' => $_POST['SPGuser'],
                        'password' => $_POST['SPGpass'],
                        'mountPoint' => $_POST['SPMountPoint'],
                        'listName' => $_POST['selectList'],
                        'authType' => 1,
                        'mountType' => 'personal');

            $sp = new \OCA\sharepoint\lib\SHAREPOINT($params);
            try {
                if ($sp->checkConnection()) {
                    $encryptedPass = ConfigMgmt::encryptPassword($_POST['SPGpass']);
                    ConfigMgmt::create_personal_mount($user, $_POST['SPMountPoint'], $_POST['SPUrl'],
                                                $_POST['selectList'], $_POST['SPGuser'],
                                                $encryptedPass, $_POST['authType']);
                    $sp->getCache()->clear();
                } else {
                    \OCP\JSON::error(array("data" => array("message" => $l->t("Cannot access the server with the provided information"))));
                    exit();
                }
            } catch (\Exception $e) {
                \OCP\JSON::error(array("data" => array("message" => $l->t("Cannot access the server with the provided information"))));
                exit();
            }
            break;
        case "2":
            $gcreds = ConfigMgmt::get_password_for_user(OC_User::getUser());
            $params = array('apiurl' => $_POST['SPUrl'],
                        'user' => $gcreds[0],
                        'password' => $gcreds[1],
                        'mountPoint' => $_POST['SPMountPoint'],
                        'listName' => $_POST['selectList'],
                        'authType' => 2,
                        'mountType' => 'personal');

            $sp = new \OCA\sharepoint\lib\SHAREPOINT($params);
            try {
                if ($sp->checkConnection()) {
                    ConfigMgmt::create_personal_mount($user, $_POST['SPMountPoint'], $_POST['SPUrl'],
                                                $_POST['selectList'], null,
                                                null, intval($_POST['authType']));
                    $sp->getCache()->clear();
                } else {
                    \OCP\JSON::error(array("data" => array("message" => $l->t("Cannot access the server with the provided information"))));
                    exit();
                }
            } catch (\Exception $e) {
                \OCP\JSON::error(array("data" => array("message" => $l->t("Cannot access the server with the provided information"))));
                exit();
            }
            break;
    }
    \OCP\JSON::success(array("data" => "ok"));
}
/* deletePersonalMount: delete personal mountpoint*/
else if (Utils::checkParams($_POST, array('SPMountId')) && $_POST['o'] == 'deletePersonalMount'){
    \OCP\Util::writeLog('sharepoint', 'Remove user mount point from db.', \OCP\Util::DEBUG);
    if (!isset($_POST['SPMountId'])) {
        \OCP\JSON::error(array("data" => array("message" => "Missing data")));
        exit();
    }
    ConfigMgmt::deletePersonalMountByIdAndUser($_POST['SPMountId'], \OCP\User::getUser());
    \OCP\JSON::success(array("data" => array("message" => "ok")));
}
/* updatePersonalMountPoint: update personal mountpoint*/
else if (Utils::checkParams($_POST, array('SPMountId', 'authType')) && $_POST['o'] == 'updatePersonalMountPoint'){
    \OCP\Util::writeLog('sharepoint', 'Update user mount point', \OCP\Util::DEBUG);

    $_POST['SPGuser'] = (isset($_POST['SPGuser'])) ? $_POST['SPGuser'] : '';
    $_POST['SPGpass'] = (isset($_POST['SPGpass'])) ? $_POST['SPGpass'] : '';

    $user = \OCP\User::getUser();
    $cursor = ConfigMgmt::getPersonalMountByIdAndUser($_POST['SPMountId'], $user);
    if ($cursor !== false) {
        // we'll only access to the first row (mount id should be unique anyway)
        if (($row = $cursor->fetchRow()) !== false) {

            $params = array('apiurl' => $row['url'],
                        'user' => $_POST['SPGuser'],
                        'password' => $_POST['SPGpass'],
                        'mountPoint' => $_POST['SPMountPoint'],
                        'listName' => $row['list_name']);

            if($_POST['authType'] === '2'){
                $credentials = ConfigMgmt::get_password_for_user(OC_User::getUser());
                $params['user'] = $credentials[0];
                $params['password'] = $credentials[1];
            }

            $sp = new \OCA\sharepoint\lib\SHAREPOINT($params);
            try {
                if ($sp->checkConnection()) {
                    $encryptedPass = ConfigMgmt::encryptPassword($_POST['SPGpass']);
                    ConfigMgmt::updatePersonalMountCredentials(intval($_POST['SPMountId']), $_POST['SPGuser'], $encryptedPass, intval($_POST['authType']));
                    $sp->getCache()->clear();
                } else {
                    \OCP\JSON::error(array("data" => array("message" => $l->t("Cannot access the server with the provided information"))));
                    exit();
                }
            } catch (\Exception $e) {
                \OCP\JSON::error(array("data" => array("message" => $l->t("Cannot access the server with the provided information"))));
                exit();
            }
        } else {
            \OCP\JSON::error(array("data" => array("message" => $l->t("Mount point not found"))));
            exit();
        }
    } else {
        \OCP\JSON::error(array("data" => array("message" => $l->t("Error accessing data"))));
        exit();
    }
    \OCP\JSON::success(array("data" => "ok"));
}
else {
    OCP\JSON::error(array('data' => array('message' => $l->t('Operation not available'))));
}
