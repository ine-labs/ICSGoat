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

require_once __DIR__ . '/../3rdparty/libSharepoint/src/sharepoint/SoapSharepointWrapper.php';
require_once __DIR__ . '/../3rdparty/libSharepoint/src/sharepoint/Auth/SoapClientAuthNTLM.php';

if (!isset($_POST['o'])){
    \OCP\JSON::error(array('message' => $l->t('Operation is not defined')));
}

if (isset($_POST['o']) && $_POST['o'] == 'getMountPoints'){

    \OCP\Util::writeLog('sharepoint', 'Get applicable mount points', \OCP\Util::DEBUG);

    $adminMounts = array();
    $personalMounts = array();

    $user = \OCP\User::getUser();

    try {
        $mountList = \OCA\sharepoint\lib\ConfigMgmt::get_mounts_for_user($user, array_keys(\OC::$server->getGroupManager()->getUserIdGroups($user)));
        if ($mountList !== false) {
            while(($row = $mountList->fetchRow()) !== false) {
                $adminMounts[] = $row;
            }
        }

        $personal_enabled = \OC::$server->getConfig()->getAppValue('sharepoint', 'allow_personal_mounts', false);
        if ($personal_enabled) {
            $personalMountList = \OCA\sharepoint\lib\ConfigMgmt::get_personal_mounts_per_user($user);
            if ($personalMountList !== false) {
                while(($row = $personalMountList->fetchRow()) !== false) {
                    $personalMounts[] = $row;
                }
            }
        }
    } catch (Exception $e) {
        OCP\JSON::error(array('status' => 'error'));
        die();
    }
    OCP\JSON::success(array('mountPoint' => array('global' =>$adminMounts, 'personal'=>$personalMounts)));
}
