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

class ConfigMgmt {

    /**
     * Check if exist mount point
     *
     * @param string $mp the ownCloud user
     * @param string $type
     * @return boolean
     */
    public static function mountPoint_exists($type, $mp, $uid=null){
        $query = \OC_DB::prepare('SELECT * FROM `*PREFIX*sp_global_dl` WHERE `mount_point` = ?');
        $params = array($mp);
        if($type === 'personal'){
            $query = \OC_DB::prepare('SELECT * FROM `*PREFIX*sp_user_dl` WHERE `mount_point` = ? AND `uid` = ?');
            $params[] = $uid;
        }
        $result = $query->execute($params);

        $point = $result->fetchRow();
        if($point) {
            return $point["mount_id"];
        }
        return false;
    }

    /**
     * Save applicable from global mount point
     *
     * @param boolean $mountId
     * @return boolean
     */
    public static function save_applicable($mountId, $mountType, $value){
        $query = \OC_DB::prepare('INSERT INTO `*PREFIX*sp_applicables` (`applicable_mount_id_fk`, `mount_type`, `mount_type_name`) values (?,?,?)');
        $query->execute(array($mountId, $mountType, $value));
        \OCP\Util::writeLog('sharepoint', 'save_applicable', \OCP\Util::DEBUG);
        return true;
    }

    /**
     * Get all the available mounts
     *
     * @see \OC_DB_StatementWrapper::execute() this method returns the same values
     * @return \OC_DB_StatementWrapper|int a DB cursor to retrieve the DB rows
     */
    public static function getAllMounts() {
        $query = 'SELECT * FROM `*PREFIX*sp_global_dl`';
        return self::executeSelectAndGetCursor($query, array());
    }

    public static function getAllMountsWithCredentials() {
        $query = 'SELECT * FROM `*PREFIX*sp_global_dl` LEFT JOIN `*PREFIX*sp_credentials` ON (`*PREFIX*sp_global_dl`.`mount_id` = `*PREFIX*sp_credentials`.`credentials_mount_id_fk`)';
        return self::executeSelectAndGetCursor($query, array());
    }

    /**
     * Get all personal mounts
     *
     * @see \OC_DB_StatementWrapper::execute() this method returns the same values
     * @return \OC_DB_StatementWrapper|int a DB cursor to retrieve the DB rows
     */
    public static function getAllPersonalMounts() {
        $query = 'SELECT * FROM `*PREFIX*sp_user_dl`';
        return self::executeSelectAndGetCursor($query, array());
    }

    /**
     * Get all the mount with the applicables per mount. There might be duplicated mount points
     * if there are several applicables for it.
     *
     * This method performs a join of the tables and return the result
     *
     * @see \OC_DB_StatementWrapper::execute() this method returns the same values
     * @return \OC_DB_StatementWrapper|int a DB cursor to retrieve the DB rows
     */
    public static function get_all_mounts_with_applicables() {
        $query = 'SELECT * FROM `*PREFIX*sp_global_dl` LEFT JOIN `*PREFIX*sp_applicables` ON (`*PREFIX*sp_global_dl`.`mount_id` = `*PREFIX*sp_applicables`.`applicable_mount_id_fk`) ORDER BY `*PREFIX*sp_global_dl`.`mount_id`';
        return self::executeSelectAndGetCursor($query, array());
    }

    /**
     * Save global mount point in the DB. All the parameters will be saved in the DB
     *
     * @param string $SPMountPoint the mount point where the FS will be mounted
     * @param array $applicables an array with possible values: $appl['global'] if the mount
     * point is available for all users, $appl['groups'] = array('g1', 'g2', 'g3') to apply
     * the mount point to the groups "g1", "g2" and "g3", and / or $appl['users'] = array('u1',
     * 'u2', 'u2') to apply the mount point to users "u1", "u2" and "u3"
     * @param string $SPUrl the url for the sharepoint site
     * @param string $selectList the documente list name inside sharepoint site
     * @param int $authType 1=credentials provided by the user, 2=use admin credentials, 3=use custom credentials
     * @param string|null $SPGuser the username that will be used to connect
     * @param string|null $SPGpass the password that will be used to connect
     */
    public static function create_global_mount($SPMountPoint, $applicables, $SPUrl, $selectList, $authType, $SPGuser, $SPGpass){
        \OCP\Util::writeLog('sharepoint', 'create_global_mount', \OCP\Util::DEBUG);

        if(self::mountPoint_exists('global', $SPMountPoint)){
            return false;
        }

        if ($authType === '3'){
            $password = self::encryptPassword($SPGpass);
        } else if ($authType != '4') {
            $SPGuser = NULL;
            $password = NULL;
        }

        $query = \OC_DB::prepare('INSERT INTO `*PREFIX*sp_global_dl` (`mount_point`, `url`, `list_name`, `auth_type`, `user`, `password`) values (?,?,?,?,?,?)');
        $result = $query->execute(array($SPMountPoint, $SPUrl, $selectList, (int)$authType, $SPGuser, $password));

        if($mountId = self::mountPoint_exists('global', $SPMountPoint)) {
            foreach($applicables as $type=>$value) {
                if(is_array($value)){
                    foreach ($value as $applicable) {
                        self::save_applicable($mountId, $type, $applicable);
                    }
                } else{
                        self::save_applicable($mountId, $type, $value);
                }
            }
        }

        \OCP\Util::writeLog('sharepoint', 'create_global_mount: '.$result, \OCP\Util::DEBUG);
        return true;
    }

    /**
     * Save personal mount point in the DB. All the parameters will be saved in the DB
     *
     * @param string $uid the user propietary ot the mount point
     * @param string $mountPoint the mount point where the FS will be mounted
     * @param string $url the url for the sharepoint site
     * @param string $listName the documente list name inside sharepoint site
     * @param int $authType 1=use custom credentials, 2=use user credentials
     * @param string|null $credsUser the username that will be used to connect
     * @param string|null $credsPassword the password that will be used to connect
     */
    public static function create_personal_mount($uid, $mountPoint, $url, $listName, $credsUser, $credsPassword, $authType) {
        if(self::mountPoint_exists('personal', $mountPoint, $uid)){
            return false;
        }
        if($authType === 2){
            $credsUser = NULL;
            $credsPassword = NULL;
        }
        $query = 'INSERT INTO `*PREFIX*sp_user_dl` (`uid`, `mount_point`, `url`, `list_name`, `user`, `password`, `auth_type`) values (?,?,?,?,?,?,?)';
        self::executeManipulationQuery($query, array($uid, $mountPoint, $url, $listName, $credsUser, $credsPassword, $authType));
        return true;
    }

    public static function get_global_mounts(){
        \OCP\Util::writeLog('sharepoint', 'get_global_mounts', \OCP\Util::DEBUG);

        $mountsCursor = self::get_all_mounts_with_applicables();

        $globalMounts = array();
        if ($mountsCursor !== false) {
            $currentMountId = null;
            while (($row = $mountsCursor->fetchRow()) !== false) {
                if ($currentMountId !== $row['mount_id']) {
                    $row['applicables'] = array();
                    $globalMounts[] = $row;
                    $currentMountId = $row['mount_id'];
                }
                $applicable = ($row['mount_type'] === 'global') ? 'global' : $row['mount_type_name'] . "(" . $row['mount_type'] . ")";
                end($globalMounts);
                $globalMounts[key($globalMounts)]['applicables'][] = $applicable;
            }
        }
        return $globalMounts;
    }

    /**
     * Get the personal mounts for an specific user
     *
     * @param string $user the user that owns the mount points
     * @see \OC_DB_StatementWrapper::execute() this method returns the same values
     * @return \OC_DB_StatementWrapper|int a DB cursor to retrieve the DB rows
     */
    public static function get_personal_mounts_per_user($user) {
        $query = 'SELECT * FROM `*PREFIX*sp_user_dl` WHERE `uid` = ?';
        return self::executeSelectAndGetCursor($query, array($user));
    }

    /**
     * Get the mount points available for an user (restricted to SP mounts, other external
     * storages won't be shown)
     *
     * @param string $user the ownCloud user
     * @param array $groups the groups the user belongs to
     * @see \OC_DB_StatementWrapper::execute() this method returns the same values
     * @return \OC_DB_StatementWrapper|int a DB cursor to retrieve the DB rows
     */
    public static function get_mounts_for_user($user, array $groups) {
        $numberOfGroups = count($groups);
        $injectedGroupClause = '';
        if ($numberOfGroups > 0) {
            $groupMarks = array_fill(0, $numberOfGroups, '?');
            $chunks = array_chunk($groupMarks, 1000);  // split in chunks of 1000 for Oracle DB
            foreach ($chunks as $chunk) {
                $groupClause = implode(',', $chunk);
                $injectedGroupClause .= " OR (`mount_type` = 'groups' AND `mount_type_name` IN ($groupClause))";
            }
        }
        $query = "SELECT DISTINCT `*PREFIX*sp_global_dl`.*, `*PREFIX*sp_credentials`.* FROM `*PREFIX*sp_global_dl` JOIN `*PREFIX*sp_applicables` ON `*PREFIX*sp_global_dl`.`mount_id` = `*PREFIX*sp_applicables`.`applicable_mount_id_fk` LEFT JOIN `*PREFIX*sp_credentials` ON (`*PREFIX*sp_global_dl`.`mount_id` = `*PREFIX*sp_credentials`.`credentials_mount_id_fk` AND `*PREFIX*sp_credentials`.`mounting_user` = ?) WHERE (`mount_type` = 'global' OR (`mount_type` = 'users' AND `mount_type_name` = ?) $injectedGroupClause)";
        $params = array_merge(array($user, $user), $groups);
        return self::executeSelectAndGetCursor($query, $params);
    }

    /**
     * Update global mount point credentials
     *
     * @param string $mountPointId The internal mountPoint Id
     * @param string $user user value to mount
     * @param string $password password value
     * @param string $authType authentication type
     * @return boolean
     */
    public static function update_global_mount($mountPointId, $user, $password, $authType){
        \OCP\Util::writeLog('sharepoint', 'Update update_global_mount credentials', \OCP\Util::DEBUG);

        if ($authType === '3'){
            $password = self::encryptPassword($password);
        } else if ($authType != '4') {
            $user = NULL;
            $password = NULL;
        }

        $query = \OC_DB::prepare('UPDATE `*PREFIX*sp_global_dl` SET `user` = ?, `password` = ?, `auth_type` = ?  WHERE `mount_id` = ?');
        $query->execute(array($user, $password, $authType, $mountPointId));
        \OCP\Util::writeLog('sharepoint', 'MountPoint updated', \OCP\Util::DEBUG);
        return true;
    }

    /**
     * Set user global credentials
     *
     * @param string $uid The uid of the owncloud user
     * @param string $user user value to mount
     * @param string $password password value
     * @return boolean
     */
    public static function set_user_credentials($uid, $user, $password){
        \OCP\Util::writeLog('sharepoint', 'Set user credentials', \OCP\Util::DEBUG);

        if($password !== null){
            $password = self::encryptPassword($password);
        }

        $query = \OC_DB::prepare('SELECT `user`, `password` FROM `*PREFIX*sp_global_credentials` WHERE `uid` = ?');
        $result = $query->execute(array($uid));

        $userRow = $result->fetchRow();
        if ($userRow) {
            if($password === null){
                $query = \OC_DB::prepare('UPDATE `*PREFIX*sp_global_credentials` SET `user` = ? WHERE `uid` = ?');
                $query->execute(array($user, $uid));
            } else if ($userRow['password'] !== $password) {
                $query = \OC_DB::prepare('UPDATE `*PREFIX*sp_global_credentials` SET `user` = ?, `password` = ? WHERE `uid` = ?');
                $query->execute(array($user, $password, $uid));
            }
        } else {
            if($password === null){
                $query = \OC_DB::prepare('INSERT INTO `*PREFIX*sp_global_credentials` (`uid`, `user`) values (?,?)');
                $query->execute(array($uid, $user));
            } else {
                $query = \OC_DB::prepare('INSERT INTO `*PREFIX*sp_global_credentials` (`uid`, `user`, `password`) values (?,?,?)');
                $query->execute(array($uid, $user, $password));
            }
        }
        return true;
    }

    /**
     * Delete global mount point and applicables
     *
     * @param string $mountPointId The id of mountPoint
     * @return boolean
     */
    public static function delete_global_mount($mountPointId){
        $query = \OC_DB::prepare('DELETE FROM `*PREFIX*sp_global_dl` WHERE `mount_id` = ?');
        $query->execute(array($mountPointId));
        $query = \OC_DB::prepare('DELETE FROM `*PREFIX*sp_applicables` WHERE `applicable_mount_id_fk` = ?');
        $query->execute(array($mountPointId));
        \OCP\Util::writeLog('sharepoint', 'delete_global_mount: '.$mountPointId, \OCP\Util::DEBUG);
        return true;
    }

    /**
     * Delete user mount point and applicables
     *
     * @param string $mountPoint The id of mountPoint
     * @return boolean
     */
    public static function delete_user_mount($mountPoint, $user){
        $query = \OC_DB::prepare('DELETE FROM `*PREFIX*sp_user_dl` WHERE `mount_point` = ? AND `uid` = ?');
        $query->execute(array($mountPoint, $user));
        \OCP\Util::writeLog('sharepoint', 'delete_user_mount: '.$mountPoint, \OCP\Util::DEBUG);
        return true;
    }

    /**
     * Set custom credentials for a mountPoint
     *
     * @param string $mountPointId The id of the mount point
     * @param string $mountingUser uid of the user
     * @param string $credsUser user value to mount
     * @param string $credsPassword password value
     * @return boolean a DB cursor to retrieve the DB rows
     */
    public static function save_custom_credentials($mountPointId, $mountingUser, $credsUser, $credsPassword) {
        $cursor = self::get_custom_credentials($mountPointId, $mountingUser);
        if ($cursor !== false && $cursor->fetchRow() !== false) {
            // we have an entry for the user and that mountid, we'll need to update
            $query = 'UPDATE `*PREFIX*sp_credentials` SET `credential_username` = ? , `credential_password` = ? WHERE `credentials_mount_id_fk` = ? AND `mounting_user` = ?';
            $params = array($credsUser, $credsPassword, $mountPointId, $mountingUser);
        } else {
            $query = 'INSERT INTO `*PREFIX*sp_credentials` (`credentials_mount_id_fk`, `mounting_user`, `credential_username`, `credential_password`) VALUES (?,?,?,?)';
            $params = array($mountPointId, $mountingUser, $credsUser, $credsPassword);
        }
        self::executeManipulationQuery($query, $params);
        return true;
    }

    /**
     * get custom credentials for a mountPoint and user
     *
     * @param string $mountPointId The id of the mount point
     * @param string $mountingUser uid of the user
     * @return \OC_DB_StatementWrapper|int a DB cursor to retrieve the DB rows
     */
    public static function get_custom_credentials($mountPointId, $mountingUser) {
        $query = 'SELECT * FROM `*PREFIX*sp_credentials` WHERE `credentials_mount_id_fk` = ? and `mounting_user` = ?';
        return self::executeSelectAndGetCursor($query, array($mountPointId, $mountingUser));
    }


    /**
     * get global credentials credentials for a owncloud user
     *
     * @param string $uid The uid of the owncloud user
     * @return array credentials
     */
    public static function get_password_for_user($uid) {
        $query = \OC_DB::prepare('SELECT `user`, `password` FROM `*PREFIX*sp_global_credentials` WHERE `uid` = ?');
        $result = $query->execute(array($uid));
        $row = $result->fetchRow();
        if (!$row) {
            return false;
        }
        $password = self::decryptPassword($row['password']);
        return array($row['user'], $password);
    }

    /**
     * Delete a personal mount point based on its id and user
     * This method should be a bit safer because the user must match the id so we can prevent
     * the deletion of mount points of other users
     *
     * @param int $mountId the id of the mount
     * @param string $user the target user
     */
    public static function deletePersonalMountByIdAndUser($mountId, $user) {
        $query = 'DELETE FROM `*PREFIX*sp_user_dl` WHERE `mount_id` = ? and `uid` = ?';
        self::executeManipulationQuery($query, array($mountId, $user));
        return true;
    }

    /**
     * Get the personal mount by the id
     *
     * @param int $mountId the id of the mount
     * @param string $user the target user
     * @see \OC_DB_StatementWrapper::execute() this method returns the same values
     * @return \OC_DB_StatementWrapper|int a DB cursor to retrieve the DB rows
     */
    public static function getPersonalMountByIdAndUser($mountId, $user) {
        $query = 'SELECT * FROM `*PREFIX*sp_user_dl` WHERE `mount_id` = ? AND `uid` = ?';
        return self::executeSelectAndGetCursor($query, array($mountId, $user));
    }


    /**
     * Update the credentials for the mount point
     *
     * @param int $mountId the mount id
     * @param string $credsUser the new username
     * @param string $credsPassword the new encrypted password
     */
    public static function updatePersonalMountCredentials($mountId, $credsUser, $credsPassword, $authType) {
        \OCP\Util::writeLog('sharepoint', 'updatePersonalMountCredentials: '. $authType, \OCP\Util::DEBUG);

        $query = 'UPDATE `*PREFIX*sp_user_dl` SET `user` = ?, `password` = ? , `auth_type` = ? WHERE `mount_id` = ?';

        if($authType === 2){
            \OCP\Util::writeLog('sharepoint', 'updatePersonalMountCredentials: Type 2 '.$credsUser."-".$credsPassword, \OCP\Util::DEBUG);
            $credsUser = NULL;
            $credsPassword = NULL;
        }
        self::executeManipulationQuery($query, array($credsUser, $credsPassword, $authType, $mountId));
        return true;
    }

    /**
     * Get a mount point information by its id
     *
     * @param int $id the id of the mount
     * @see \OC_DB_StatementWrapper::execute() this method returns the same values
     * @return \OC_DB_StatementWrapper|int a DB cursor to retrieve the DB rows
     */
    public static function getMountById($id) {
        $query = 'SELECT * FROM `*PREFIX*sp_global_dl` WHERE `mount_id` = ?';
        return self::executeSelectAndGetCursor($query, array($id));
    }


    /**
     * Delete all personal mount points
     */
    public static function clearPersonalMounts() {
        // truncate doesn't work on sqlite, might be worthy to drop the table and recreate it
        // instead of delete each row
        \OCP\Util::writeLog('sharepoint', 'clearPersonalMounts', \OCP\Util::DEBUG);
        $query = 'DELETE FROM `*PREFIX*sp_user_dl`';
        self::executeManipulationQuery($query, array());
        return true;
    }

    /**
     * Get the global credentials for the administrator. There should be only 1 row for admin
     * credentials, so we'll only return 1 row
     */
    public static function getAdminGlobalCredentials() {
        $query = 'SELECT * FROM `*PREFIX*sp_global_credentials` WHERE `uid` = ?';
        return self::executeSelectAndGetCursor($query, array("\$harepointAdmin"), 1);
    }

    public static function getAdminGlobalCredentialsCached() {
        $cursor = self::getAdminGlobalCredentials();
        if ($cursor) {
            return $cursor->fetchRow();
        }
    }

    /**
     * Get the global credentials for the administrator. There should be only 1 row for admin
     * credentials, so we'll only return 1 row
     */
    public static function getUserGlobalCredentials($user) {
        $query = 'SELECT * FROM `*PREFIX*sp_global_credentials` WHERE `uid` = ?';
        return self::executeSelectAndGetCursor($query, array($user), 1);
    }

    public static function decryptPassword($password) {
        $cipher = self::getCipher();
        $encryptedPassword = base64_decode($password);
        $iv = substr($encryptedPassword, 0, 16);
        $binaryPassword = substr($encryptedPassword, 16);
        $cipher->setIV($iv);
        $password = $cipher->decrypt($binaryPassword);
        return $password;
    }

    /**
     * @param string|null $password
     */
    public static function encryptPassword($password) {
        $cipher = self::getCipher();
        $iv = \OC::$server->getSecureRandom()->generate(16);
        $cipher->setIV($iv);
        $password = base64_encode($iv . $cipher->encrypt($password));
        return $password;
    }

    /**
     * Returns the encryption cipher
     */
    private static function getCipher() {
        $cipher = new \phpseclib\Crypt\Rijndael(\phpseclib\Crypt\Base::MODE_CBC);
        $cipher->setKey(\OC::$server->getConfig()->getSystemValue('passwordsalt'));
        return $cipher;
    }

    /**
     * @param integer $limit
     */
    private static function executeSelectAndGetCursor($stmtString, array $stmtParams, $limit = null) {
        $query = \OC_DB::prepare($stmtString, $limit);
        $result = $query->execute($stmtParams);
        return $result;
    }

    /**
     * @param string $stmtString
     */
    private static function executeManipulationQuery($stmtString, array $stmtParams) {
        $query = \OC_DB::prepare($stmtString);
        $result = $query->execute($stmtParams);
        return $result;
    }

}
