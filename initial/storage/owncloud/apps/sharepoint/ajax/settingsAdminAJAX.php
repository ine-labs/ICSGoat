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
OCP\JSON::checkAdminUser();
OCP\JSON::callCheck();

use \OCA\sharepoint\lib\ConfigMgmt;
use \OCA\sharepoint\lib\Utils;

$l = \OC::$server->getL10N('sharepoint');

require_once __DIR__ . '/../3rdparty/libSharepoint/src/sharepoint/SoapSharepointWrapper.php';
require_once __DIR__ . '/../3rdparty/libSharepoint/src/sharepoint/Auth/SoapClientAuthNTLM.php';

$mandatoryParams = array('o');

if (!Utils::checkParams($_POST, $mandatoryParams)){
    \OCP\JSON::error(array('message' => $l->t('Operation is not defined')));
    exit();
}

/* Ajax request */
/* getDocumentList: Use to retrieve document list list */
if (Utils::checkParams($_POST, array('url')) && $_POST['o'] == 'getDocumentList') {
    \OCP\Util::writeLog('sharepoint', 'Get document list from site '.$_POST['url'], \OCP\Util::DEBUG);
    try {
        $parsed = parse_url($_POST['url']);
        $client = new \sharepoint\SoapSharepointWrapper($_POST['u'], $_POST['p'], $parsed['scheme'].'://'.$parsed['host'].'/'.rawurlencode(ltrim($parsed['path'], '/')), '2010');
        $response = $client->getListCollection();
    } catch (Exception $e) {
        OCP\JSON::error(array('status' => 'error'));
        exit();
    }
    OCP\JSON::success(array('message' => $response));
}
/* addMountPoint: Use to retrieve document list list */
else if (Utils::checkParams($_POST, array('SPMountPoint', 'SPUrl', 'authType', 'selectList')) && $_POST['o'] == 'addMountPoint'){
    \OCP\Util::writeLog('sharepoint', 'Add global mount point to db.', \OCP\Util::DEBUG);

    //Normalice Sharepoint Site URL
    $_POST['SPUrl'] = rtrim($_POST['SPUrl'], "/");

    if(preg_match('/[\\/\<\>:"|?*]/', $_POST['SPMountPoint']) === 1){
        \OCP\JSON::error(array("data" => array("message" => $l->t("Local Folder Name is not valid. Characters \\, \/, <, >, :, \", |, ? and * are not allowed."))));
        exit();
    }

    $current_global_mountpoints = ConfigMgmt::get_global_mounts();
    foreach ($current_global_mountpoints as $mountPoint) {
        if ($mountPoint['mount_point'] == $_POST['SPMountPoint']){
            \OCP\JSON::error(array("data" => array("message" => $l->t("A SharePoint mount point with that name already exists"))));
            exit();
        }
    }

    // global username and password can be empty to connect as guest
    if ($_POST['authType'] !== "1") {
        $_POST['SPGuser'] = (isset($_POST['SPGuser'])) ? $_POST['SPGuser'] : '';
        $_POST['SPGpass'] = (isset($_POST['SPGpass'])) ? $_POST['SPGpass'] : '';
    }
    // build the applicables
    $applicables = array();
    if ($_POST['SPMountType'] === '') {
        $applicables['global'] = 'global';
    } else {
        $applicableList = explode(',', $_POST['SPMountType']);
        foreach ($applicableList as $element) {
            if ($element === 'global') {
                $applicables['global'] = 'global';
            }

            $groupname = substr($element, 0, strrpos($element, '(group)'));

            if (!empty($groupname)) {
                if (!isset($applicables['groups'])) {
                    $applicables['groups'] = array();
                }
                $applicables['groups'][] = $groupname;
            } else {
                if (!isset($applicables['users'])) {
                    $applicables['users'] = array();
                }
                $applicables['users'][] = $element;
            }
        }
    }
    switch ($_POST['authType']) {
        case "1":
            // users provide their own credentials
            ConfigMgmt::create_global_mount($_POST['SPMountPoint'],
                                                                $applicables,
                                                                $_POST['SPUrl'],
                                                                $_POST['selectList'],
                                                                $_POST['authType'],
                                                                $_POST['SPGuser'],
                                                                $_POST['SPGpass']);
            break;
        case "2":
            // admin uses its global credentials
            $gcreds = ConfigMgmt::getAdminGlobalCredentialsCached();
            $params = array('apiurl' => $_POST['SPUrl'],
                        'user' => $gcreds['user'],
                        'password' => ConfigMgmt::decryptPassword($gcreds['password']),
                        'mountPoint' => $_POST['SPMountPoint'],
                        'listName' => $_POST['selectList'],
                        'authType' => $_POST['authType'],
                        'mountType' => 'global');

            $sp = new \OCA\sharepoint\lib\SHAREPOINT($params);
            try{
                if ($sp->checkConnection()) {
                    ConfigMgmt::create_global_mount($_POST['SPMountPoint'],
                                                                        $applicables,
                                                                        $_POST['SPUrl'],
                                                                        $_POST['selectList'],
                                                                        $_POST['authType'],
                                                                        $_POST['SPGuser'],
                                                                        $_POST['SPGpass']);
                }
            } catch (\Exception $e) {
                \OCP\JSON::error(array("data" => array("message" => $l->t("Cannot access the server with the provided information"))));
                exit();
            }
            break;
        case "3":
            // admin provides credentials for this mount point only
            $params = array('apiurl' => $_POST['SPUrl'],
                        'user' => $_POST['SPGuser'],
                        'password' => $_POST['SPGpass'],
                        'mountPoint' => $_POST['SPMountPoint'],
                        'listName' => $_POST['selectList'],
                        'authType' => $_POST['authType'],
                        'mountType' => 'global');

            $sp = new \OCA\sharepoint\lib\SHAREPOINT($params);
            try{
                if ($sp->checkConnection()) {
                    ConfigMgmt::create_global_mount($_POST['SPMountPoint'],
                                                                        $applicables,
                                                                        $_POST['SPUrl'],
                                                                        $_POST['selectList'],
                                                                        $_POST['authType'],
                                                                        $_POST['SPGuser'],
                                                                        $_POST['SPGpass']);
                }
            } catch (\Exception $e) {
                \OCP\JSON::error(array("data" => array("message" => $l->t("Cannot access the server with the provided information"))));
                exit();
            }
            break;
        case "4":
            // Login credentials
            // We use user row in the db to store the domain, because the domain is part of the user
            ConfigMgmt::create_global_mount($_POST['SPMountPoint'],
                                                                $applicables,
                                                                $_POST['SPUrl'],
                                                                $_POST['selectList'],
                                                                4,
                                                                $_POST['SPdomain'],
                                                                null);
            break;
    }
    \OCP\JSON::success(array("data" => "ok"));
}
/* getGlobalMountPoints: Use to retrieve all global mount points*/
else if ($_POST['o'] == 'getGlobalMountPoints'){
    \OCP\Util::writeLog('sharepoint', 'Add global mount point to db.', \OCP\Util::DEBUG);
    \OCP\JSON::success(array("data" => ConfigMgmt::get_global_mounts()));
}
/* setPersonalMounts: Allow/Disallow personal mounts */
else if ($_POST['o'] == 'setPersonalMounts'){
    \OCP\Util::writeLog('sharepoint', 'setPersonalMount: '.$_POST['value'], \OCP\Util::DEBUG);
    \OC::$server->getConfig()->setAppValue('sharepoint', 'allow_personal_mounts', $_POST['value']);
    if(intval($_POST['value']) === 0){
        ConfigMgmt::clearPersonalMounts();
    }
    $status = '200';
    OCP\JSON::success(array('data' => array('message' => $status)));
}
/* setGlobalSharing: Allow/Disallow Sharing on Sharepoint mounts */
else if ($_POST['o'] == 'setGlobalSharing'){
    \OCP\Util::writeLog('sharepoint', 'setGlobalSharing: '.$_POST['value'], \OCP\Util::DEBUG);
    \OC::$server->getConfig()->setAppValue('sharepoint', 'global_sharing', $_POST['value']);

    $cursor = ConfigMgmt::getAllMountsWithCredentials();
    if ($cursor !== false) {
        while (($row = $cursor->fetchRow()) !== false) {
            $sp = Utils::getSPForGlobalRow($row);
            if ($sp) {
                $sp->getCache()->clear();
            }
        }
    } else {
        \OCP\JSON::error(array("data" => array("message" => $l->t('Cannot connect to the DB'))));
        exit();
    }

    $cursor = ConfigMgmt::getAllPersonalMounts();
    if ($cursor !== false) {
        while (($row = $cursor->fetchRow()) !== false) {
            $sp = Utils::getSPForPersonalRow($row);
            if ($sp) {
                $sp->getCache()->clear();
            }
        }
    } else {
        \OCP\JSON::error(array("data" => array("message" => $l->t('Cannot connect to the DB'))));
        exit();
    }

    $status = '200';
    OCP\JSON::success(array('data' => array('message' => $status)));
}
/* setAdminGlobalCredentials: Store admin global credentials */
else if ($_POST['o'] == 'setAdminGlobalCredentials'){
    \OCP\Util::writeLog('sharepoint', 'setAdminGlobalCredentials', \OCP\Util::DEBUG);
    $pass = null;
    if($_POST['c'] === "true"){
        $pass = $_POST['p'];
    }
    ConfigMgmt::set_user_credentials('$harepointAdmin', $_POST['u'], $pass);
    $status = '200';
    OCP\JSON::success(array('data' => array('message' => $status)));
}
/*  deleteMountPoint: Delete global mountPoint and their applicables*/
else if (Utils::checkParams($_POST, array('SPMountId')) && $_POST['o'] == 'deleteMountPoint'){
    \OCP\Util::writeLog('sharepoint', 'deleteMountPoint: '.$_POST['SPMountId'], \OCP\Util::DEBUG);

    $cursor = ConfigMgmt::getMountById($_POST['SPMountId']);
    if ($cursor !== false) {
        while (($row = $cursor->fetchRow()) !== false) {
            try{
                    $sp = Utils::getSPForGlobalRow($row);
                    if ($sp) {
                        $sp->getCache()->clear();
                    }
                } catch (\Exception $e) {
                    // In case of a "Login Credentials" type, we might not be able to get the
                    // SP connection, so we just ignore it, otherwise we rethrow the exception
                    if (intval($row['use_custom_credentials']) !== 4) {
                        throw $e;
                    }
                }
        }
    }
    ConfigMgmt::delete_global_mount($_POST['SPMountId']);
    $status = '200';
    OCP\JSON::success(array('data' => array('message' => $status)));
}
/* UpdatemountPoint*/
else if (Utils::checkParams($_POST, array('SPMountId', 'authType')) && $_POST['o'] == 'updateMountPoint'){
    \OCP\Util::writeLog('sharepoint', 'updateMountPoint: '.$_POST['SPMountId'], \OCP\Util::DEBUG);
    // global username and password can be empty to connect as guest
    if ($_POST['authType'] !== "1") {
        $_POST['user'] = (isset($_POST['user'])) ? $_POST['user'] : '';
        $_POST['pass'] = (isset($_POST['pass'])) ? $_POST['pass'] : '';
    }
    switch ($_POST['authType']) {
        case "1":
            // users provide their own credentials
            ConfigMgmt::update_global_mount($_POST['SPMountId'], NULL, NULL, $_POST['authType']);
            break;
        case "2":
            // admin uses its global credentials
            $gcreds = ConfigMgmt::getAdminGlobalCredentialsCached();
            $mountData = ConfigMgmt::getMountById($_POST['SPMountId']);
            if ($mountData !== false) {
                if (($row = $mountData->fetchRow()) !== false) {
                    //Check connectivity
                    $params = array('apiurl' => $row['url'],
                                'user' => $gcreds['user'],
                                'password' => ConfigMgmt::decryptPassword($gcreds['password']),
                                'mountPoint' => $row['mount_point'],
                                'listName' => $row['list_name']);
                    $sp = new \OCA\sharepoint\lib\SHAREPOINT($params);
                    try{
                        if ($sp->checkConnection()) {
                            ConfigMgmt::update_global_mount($_POST['SPMountId'], NULL, NULL, $_POST['authType']);
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
            break;
        case "3":
            // admin uses its global credentials
            $mountData = ConfigMgmt::getMountById($_POST['SPMountId']);
            if ($mountData !== false) {
                if (($row = $mountData->fetchRow()) !== false) {
                    //Check connectivity
                    $params = array('apiurl' => $row['url'],
                                'user' => $_POST['user'],
                                'password' => $_POST['pass'],
                                'mountPoint' => $row['mount_point'],
                                'listName' => $row['list_name']);
                    $sp = new \OCA\sharepoint\lib\SHAREPOINT($params);
                    try{
                        if ($sp->checkConnection()) {
                            ConfigMgmt::update_global_mount($_POST['SPMountId'], $_POST['user'], $_POST['pass'], $_POST['authType']);
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
            break;
        case "4":
            // Using login creentials
            ConfigMgmt::update_global_mount($_POST['SPMountId'], $_POST['user'], NULL, 4);
            break;
    }
    \OCP\JSON::success(array("data" => "ok"));
}
else {
    OCP\JSON::error(array('data' => array('message' => $l->t('Operation not available'))));
}
