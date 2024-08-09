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

/**
 * A general utilities class for the sharepoint drive app
 */
class Utils {
    /**
     * Check key on array
     * @param array $array array to be analizedused as
     * @param array $params keys to be found on $array
     * @return boolean the encrypted text
     */
    public static function checkParams($array, $params) {
        // check mandatory parameters
        foreach ($params as $mparam) {
            if (!isset($array[$mparam]) || ($array[$mparam] !== "0" && empty($array[$mparam]))) {
                return false;
            }
        }
        return true;
    }

    public static function getMountsForApi() {
        $entries = array();
        $user = \OC_User::getUser();
        $groups = array_keys(\OC::$server->getGroupManager()->getUserIdGroups($user));
        $mountList = ConfigMgmt::get_mounts_for_user($user, $groups);
        if ($mountList !== false) {
            while (($row = $mountList->fetchRow()) !== false) {
                $entries[] = self::generateApiEntryForRow($row, 'system');
            }
        }
        $personalMountList = ConfigMgmt::get_personal_mounts_per_user($user);
        if ($personalMountList !== false) {
            while (($row = $personalMountList->fetchRow()) !== false) {
                $entries[] = self::generateApiEntryForRow($row, 'personal');
            }
        }
        return new \OC_OCS_Result($entries);
    }

    /**
     * @param string $type
     */
    private static function generateApiEntryForRow($row, $type) {

        $l = \OC::$server->getL10N('sharepoint');

        $path = dirname($row['mount_point']);
        if ($path === '.') {
            $path = "";
        }

        $authType = $l->t('unknown');
        if($type === 'system' && (string)$row['auth_type'] === "1"){
            $authType = $l->t('User credentials');
        } else if ($type === 'system' && (string)$row['auth_type'] === "2"){
            $authType = $l->t('Global credentials');
        } else if ($type === 'system' && (string)$row['auth_type'] === "3"){
            $authType = $l->t('Custom credentials');
        } else if ($type === 'system' && (string)$row['auth_type'] === "4"){
            $authType = $l->t('Login credentials');
        } else if ($type === 'personal' && (string)$row['auth_type'] === "1"){
            $authType = $l->t('Custom credentials');
        } else if ($type === 'personal' && (string)$row['auth_type'] === "2"){
            $authType = $l->t('Personal credentials');
        }

        return array(
            'name' => basename($row['mount_point']),
            'site' =>$row['url'],
            'documentList' =>$row['list_name'],
            'authType' => $authType,
            'path' => $path,
            'type' => 'dir',
            'backend' => 'Sharepoint',
            'scope' => $type,
            'permissions' => \OCP\PERMISSION_READ
        );
    }

    /**
     * Get a SHAREPOINT instance according to the data coming from row.
     *
     * @return \OCA\sharepoint\lib\SHAREPOINT The SHAREPOINT instance for the row
     */
    public static function getSPForGlobalRow($mount) {
            $credentials = array("user"=>"", "password"=>"");
            $oc_sharing = \OC::$server->getConfig()->getAppValue('sharepoint', 'global_sharing', false);

            if(intval($mount['auth_type']) === 1){
                //credentials assigned by each user
                $user = \OC::$server->getUserSession()->getUser();
                if ($user !== null) {
                        $user = $user->getUID();
                }
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
            } else if (intval($mount['auth_type']) === 4){
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
                                          "password" => "",
                                          "mountPoint" => $mount['mount_point'],
                                          "listName" => $mount['list_name'],
                                          "oc_sharing" => $oc_sharing,
                                          "authType" => $mount['auth_type'],
                                          "mountType" => "global");

        return new \OCA\sharepoint\lib\SHAREPOINT($params);
    }

    /**
     * Get a SHAREPOINT instance according to the data coming from row.
     *
     * @return \OCA\sharepoint\lib\SHAREPOINT The SHAREPOINT instance for the row
     */
    public static function getSPForPersonalRow($mount) {
        $personalCredentials = array();
        $oc_sharing = \OC::$server->getConfig()->getAppValue('sharepoint', 'global_sharing', false);
        if(intval($mount['auth_type']) === 1){
            //credentials assigned by each user
            $cursor = ConfigMgmt::getPersonalMountByIdAndUser($mount['mount_id'], $mount['uid']);
            if($row = $cursor->fetchRow()){
                $personalCredentials['user'] = $row['user'];
                if($row['password'] !== '' ){
                    $personalCredentials['password'] = ConfigMgmt::decryptPassword($row['password']);
                }
            } else{
                $personalCredentials['user'] = '';
                $personalCredentials['password'] = '';
            }
        } else if (intval($mount['auth_type']) === 2){
            //credentials point to global credentials
            $cursor = ConfigMgmt::getUserGlobalCredentials($mount['uid']);
            if($row = $cursor->fetchRow()){
                $personalCredentials['user'] = $row['user'];
                if($row['password'] !== '' ){
                    $personalCredentials['password'] = ConfigMgmt::decryptPassword($row['password']);
                }
            } else{
                $personalCredentials['user'] = '';
                $personalCredentials['password'] = '';
            }
        }

        $params = array("apiurl" => $mount['url'],
                                      "user" => $personalCredentials['user'],
                                      "password" => $personalCredentials['password'],
                                      "mountPoint" => $mount['mount_point'],
                                      "listName" => $mount['list_name'],
                                      "oc_sharing" => $oc_sharing,
                                      "authType" => $mount['auth_type'],
                                      "mountType" => "personal");

        return new \OCA\sharepoint\lib\SHAREPOINT($params);
    }

    public static function checkIE(){
        preg_match('/MSIE (.*?);/', $_SERVER['HTTP_USER_AGENT'], $matches);
        if(count($matches)<2){
          preg_match('/Trident\/\d{1,2}.\d{1,2}; rv:([0-9]*)/', $_SERVER['HTTP_USER_AGENT'], $matches);
        }

        if (count($matches)>1){
          //Then we're using IE
          $version = $matches[1];
          return $version;
          } else{
          return Null;
          }
    }


    public static function InvalidateConfig($params){

        // mountType = Global && authType = 1
        //
        if ($params["mountType"] == "global") {
            switch ($params["authType"]) {
                case 1:
                    // Delete user credentials from sp_credentials table
                    if( $mount_id = ConfigMgmt::mountPoint_exists("global", $params["mountPoint"] )){
                        $user = \OC::$server->getUserSession()->getUser();
                        if ($user !== null) {
                                $user = $user->getUID();
                        }
                        ConfigMgmt::save_custom_credentials($mount_id, $user, "", "");
                    }
                    break;
                case 2:
                    // Delete SP global credentials from sp_global_credentials table
                    ConfigMgmt::set_user_credentials('$harepointAdmin', '', '');
                    break;
                case 3:
                    // Delete custom credentials from sp_global_dl table
                    if( $mount_id = ConfigMgmt::mountPoint_exists("global", $params["mountPoint"] )){
                        ConfigMgmt::update_global_mount($mount_id, "", "", 3);
                    }
                    break;
                case 4:
                    // Invalidate Session credentials
                    \OC::$server->getSession()->remove('sp-credentials');
                    \OC::$server->getSession()->set('sp-credentials',
                        \OC::$server->getCrypto()->encrypt(json_encode(array("uid" => "", "password" => ""))));
                    break;
            }
        } else if ($params["mountType"] == "personal") {

            $user = \OC::$server->getUserSession()->getUser();
            if ($user !== null) {
                    $user = $user->getUID();
            }
            switch ($params["authType"]) {
                case 1:
                    if( $mount_id = ConfigMgmt::mountPoint_exists("personal", $params["mountPoint"], $user)){
                            ConfigMgmt::updatePersonalMountCredentials($mount_id, "", "", 1);
                    }
                    break;
                case 2:
                    ConfigMgmt::set_user_credentials($user, '', '');
                    break;
            }

        }

    }

}
