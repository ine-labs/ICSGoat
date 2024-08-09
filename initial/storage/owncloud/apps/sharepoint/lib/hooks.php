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

namespace OCA\sharepoint\lib;

use \OCA\sharepoint\lib\ConfigMgmt;

class Hooks {

    static public function mount_storage($parameters) {

        //Not used for now
        $realMaxSize = floatval(25) * 1024 * 1024;
        $extensions = array("exe", "wsdl");

        $global = array();

        $user = $parameters['user'];

        $mountList = ConfigMgmt::get_mounts_for_user($user, array_keys(\OC::$server->getGroupManager()->getUserIdGroups($user)));
        if ($mountList !== false) {
            while(($row = $mountList->fetchRow()) !== false) {
                $global[] = $row;
            }
        }

        $oc_sharing = \OC::$server->getConfig()->getAppValue('sharepoint', 'global_sharing', false);

        foreach ($global as $mount) {

            $credentials = array("user"=>"", "password"=>"");

            if(intval($mount['auth_type']) === 1){
                //credentials assigned by each user
                $cursor = ConfigMgmt::get_custom_credentials($mount['mount_id'], $user);
                if($row = $cursor->fetchRow()){
                    $credentials['user'] = $row['credential_username'];
                    if($row['credential_password'] !== '' ){
                        $credentials['password'] = ConfigMgmt::decryptPassword($row['credential_password']);
                    }
                } else {
                    $credentials['user'] = '';
                    $credentials['password'] = '';
                }

            } else if (intval($mount['auth_type']) === 2){
                //credentials point to global credentials
                $cursor = ConfigMgmt::getAdminGlobalCredentials();
                if($row = $cursor->fetchRow()){
                    $credentials['user'] = $row['user'];
                    if($row['password'] !== '' ){
                        $credentials['password'] = ConfigMgmt::decryptPassword($row['password']);
                    }
                } else{
                    $credentials['user'] = '';
                    $credentials['password'] = '';
                }
            } else if (intval($mount['auth_type']) === 3){
                //custom credentials for this mount
                $credentials['user'] = $mount['user'];
                if($mount['password'] !== '' ){
                    $credentials['password'] = ConfigMgmt::decryptPassword($mount['password']);
                }
            } else if(intval($mount['auth_type']) === 4){
                $encrypted_credentials = \OC::$server->getSession()->get('sp-credentials');

                // Sharing is disabled with login credentials
                $oc_sharing = false;

                if(isset($mount['user']) && $mount['user']!=""){
                    $domain = $mount['user'];
                }

                if ($encrypted_credentials !== null){
                        $credentials = json_decode(\OC::$server->getCrypto()->decrypt($encrypted_credentials), true);
                        $credentials["user"] = $credentials["uid"];
                        if(isset($domain) && $domain !==""){
                            $credentials["user"] = $domain."\\".$credentials['uid'];
                        }
                }
            }

            $params = array("apiurl" => $mount['url'],
                                          "user" => $credentials['user'],
                                          "password" => $credentials['password'],
                                          "mountPoint" => $mount['mount_point'],
                                          "listName" => $mount['list_name'],
                                          "forbiddenExt" => $extensions,
                                          "maxSize" => $realMaxSize,
                                          "oc_sharing" => $oc_sharing,
                                          "authType" => $mount['auth_type'],
                                          "mountType" => "global");

            \OC\Files\Filesystem::mount('OCA\sharepoint\lib\SHAREPOINT', $params,
                                        '/' . $user . '/files/' . $mount['mount_point']);

        }

        //personal mount points
        $enabledPersonal = \OC::$server->getConfig()->getAppValue('sharepoint', 'allow_personal_mounts');
        if($enabledPersonal == 1){
            $personal = Array();
            $personalMountList = ConfigMgmt::get_personal_mounts_per_user($user);
            if ($personalMountList !== false) {
                while(($row = $personalMountList->fetchRow()) !== false) {
                    $personal[] = $row;
                }
            }

            foreach ($personal as $mount) {
                $personalCredentials = array();
                $personalCredentials['user'] = '';
                $personalCredentials['password'] = '';

                if(intval($mount['auth_type']) === 1){
                    //credentials assigned by each user
                    $cursor = ConfigMgmt::getPersonalMountByIdAndUser($mount['mount_id'], $user);
                    if($row = $cursor->fetchRow()){
                        $personalCredentials['user'] = $row['user'];
                        if($row['password'] !== '' ){
                            $personalCredentials['password'] = ConfigMgmt::decryptPassword($row['password']);
                        }
                    }
                } else if (intval($mount['auth_type']) === 2){
                    //credentials point to global credentials
                    $cursor = ConfigMgmt::getUserGlobalCredentials($user);
                    if($row = $cursor->fetchRow()){
                        $personalCredentials['user'] = $row['user'];
                        if($row['password'] !== '' ){
                            $personalCredentials['password'] = ConfigMgmt::decryptPassword($row['password']);
                        }
                    }
                }

                $params = array("apiurl" => $mount['url'],
                                              "user" => $personalCredentials['user'],
                                              "password" => $personalCredentials['password'],
                                              "mountPoint" => $mount['mount_point'],
                                              "listName" => $mount['list_name'],
                                              "forbiddenExt" => $extensions,
                                              "maxSize" => $realMaxSize,
                                              "oc_sharing" => $oc_sharing,
                                              "authType" => $mount['auth_type'],
                                              "mountType" => "personal");

                \OC\Files\Filesystem::mount('OCA\sharepoint\lib\SHAREPOINT', $params,
                                            '/' . $user . '/files/' . $mount['mount_point']);
            }
        }
    }


    /**
     * Intercepts the user credentials on login and stores them
     * encrypted inside the session if SP storage is enabled.
     * @param array $params
     */
    public static function login($params) {
        \OC::$server->getSession()->set('sp-credentials', \OC::$server->getCrypto()->encrypt(json_encode($params)));
    }

}
