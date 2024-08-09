<?php
/**
 * ownCloud
 *
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 * @author Jesus Macias Portela <jesus@owncloud.com>
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\windows_network_drive\lib;

/**
 * Class to manage the access to the DB
 */
class DBAccess {
	private static $globalAdminCreds;
	private static $globalUserCreds = [];
	/**
	 * Save a mount point in the DB. All the parameters will be saved in the DB
	 * If you don't want to use the same credentials for all users applicable to the mount
	 * point, you need to use the $customCredentials = 0 and use null for global username and
	 * password
	 *
	 * @param string $mountPoint the mount point where the FS will be mounted (the folder name
	 * will match this)
	 * @param array $applicables an array with possible values: $appl['global'] if the mount
	 * point is available for all users, $appl['groups'] = array('g1', 'g2', 'g3') to apply
	 * the mount point to the groups "g1", "g2" and "g3", and / or $appl['users'] = array('u1',
	 * 'u2', 'u2') to apply the mount point to users "u1", "u2" and "u3"
	 * @param string $url the url for the mount point so the connector knows where it should
	 * connect
	 * @param string $share the share name
	 * @param string $root the folder that will act as root
	 * @param int $customCredentials 0 to use the same credentials for all applicable users, 1
	 * if each user must provide their own credentials
	 * @param string|null $globalUsername the username that will be used to connect if we need
	 * to use the same credentials for all users ($customCredentials === 0)
	 * @param string|null $globalPassword the password that will be used to connect if we need
	 * to use the same credentials for all users ($customCredentials === 0)
	 */
	public static function saveMount($mountPoint, $applicables, $url, $share, $root, $customCredentials, $globalUsername, $globalPassword) {
		// check if the mount point exists
		$mount = self::getMountByProps($mountPoint, $url, $share, $root);
		if ($mount !== false) {
			if (($mountData = $mount->fetchRow()) !== false) {
				// mount point exists -> update applicables
				self::saveApplicables($mountData['mount_id'], $applicables);
				return;
			}
		}

		$query = 'INSERT INTO `*PREFIX*wnd_mounts` (`mount_point`, `mount_url`, `share`, `root`, `use_custom_credentials`, `global_username`, `global_password`) values (?,?,?,?,?,?,?)';
		self::executeManipulationQuery($query, [$mountPoint, $url, $share, $root, $customCredentials, $globalUsername, $globalPassword]);

		// we'll need the mount id
		$mount = self::getMountByProps($mountPoint, $url, $share, $root);
		$mountData = $mount->fetchRow();
		self::saveApplicables($mountData['mount_id'], $applicables);
	}

	/**
	 * Get all the available mounts
	 *
	 * @see \OC_DB_StatementWrapper::execute() this method returns the same values
	 * @return \OC_DB_StatementWrapper|int a DB cursor to retrieve the DB rows
	 */
	public static function getAllMounts() {
		$query = 'SELECT * FROM `*PREFIX*wnd_mounts`';
		return self::executeSelectAndGetCursor($query, []);
	}

	public static function getAllMountsWithCredentials() {
		$query = 'SELECT * FROM `*PREFIX*wnd_mounts` LEFT JOIN `*PREFIX*wnd_credentials` ON (`*PREFIX*wnd_mounts`.`mount_id` = `*PREFIX*wnd_credentials`.`credentials_mount_id_fk`)';
		return self::executeSelectAndGetCursor($query, []);
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
	public static function getAllMountsWithApplicables() {
		$query = 'SELECT * FROM `*PREFIX*wnd_mounts` LEFT JOIN `*PREFIX*wnd_mounts_applicable` ON (`*PREFIX*wnd_mounts`.`mount_id` = `*PREFIX*wnd_mounts_applicable`.`applicable_mount_id_fk`) ORDER BY `*PREFIX*wnd_mounts`.`mount_id`';
		return self::executeSelectAndGetCursor($query, []);
	}

	/**
	 * Get the mount points available for an user (restricted to WND mounts, other external
	 * storages won't be shown)
	 *
	 * @param string $user the ownCloud user
	 * @param array $groups the groups the user belongs to
	 * @see \OC_DB_StatementWrapper::execute() this method returns the same values
	 * @return \OC_DB_StatementWrapper|int a DB cursor to retrieve the DB rows
	 */
	public static function getMountsForUser($user, array $groups) {
		$numberOfGroups = \count($groups);
		$injectedGroupClause = '';
		if ($numberOfGroups > 0) {
			$groupMarks = \array_fill(0, $numberOfGroups, '?');
			$chunks = \array_chunk($groupMarks, 1000);  // split in chunks of 1000 for Oracle DB
			foreach ($chunks as $chunk) {
				$groupClause = \implode(',', $chunk);
				$injectedGroupClause .= " OR (`mount_type` = 'group' AND `mount_type_name` IN ($groupClause))";
			}
		}
		$query = "SELECT DISTINCT `*PREFIX*wnd_mounts`.* , `*PREFIX*wnd_credentials`.* FROM `*PREFIX*wnd_mounts` JOIN `*PREFIX*wnd_mounts_applicable` ON `*PREFIX*wnd_mounts`.`mount_id` = `*PREFIX*wnd_mounts_applicable`.`applicable_mount_id_fk` LEFT JOIN `*PREFIX*wnd_credentials` ON (`*PREFIX*wnd_mounts`.`mount_id` = `*PREFIX*wnd_credentials`.`credentials_mount_id_fk` AND `*PREFIX*wnd_credentials`.`mounting_user` = ?) WHERE (`mount_type` = 'global' OR (`mount_type` = 'user' AND `mount_type_name` = ?) $injectedGroupClause)";
		$params = \array_merge([$user, $user], $groups);
		return self::executeSelectAndGetCursor($query, $params);
	}

	/**
	 * Get a mount point information based on its properties. All params are mandatory.
	 * This method will return a cursor with just the first result (if any). Other results will
	 * be ignored
	 *
	 * @param string $mountPoint the mount point
	 * @param string $url the target url
	 * @param string $share the share name
	 * @param string $root the root folder
	 * @see \OC_DB_StatementWrapper::execute() this method returns the same values
	 * @return \OC_DB_StatementWrapper|int a DB cursor to retrieve the DB rows
	 */
	public static function getMountByProps($mountPoint, $url, $share, $root) {
		$query = 'SELECT * FROM `*PREFIX*wnd_mounts` WHERE `mount_point` = ? and `mount_url` = ? and `share` = ? and `root` = ?';
		return self::executeSelectAndGetCursor($query, [$mountPoint, $url, $share, $root], 1);
	}

	public static function getMountByPropsLast($mountPoint, $url, $share, $root) {
		$query = 'SELECT * FROM `*PREFIX*wnd_mounts` WHERE `mount_point` = ? and `mount_url` = ? and `share` = ? and `root` = ? ORDER BY `mount_id` desc';
		return self::executeSelectAndGetCursor($query, [$mountPoint, $url, $share, $root], 1);
	}

	/**
	 * Get a mount point information by its id
	 *
	 * @param int $id the id of the mount
	 * @see \OC_DB_StatementWrapper::execute() this method returns the same values
	 * @return \OC_DB_StatementWrapper|int a DB cursor to retrieve the DB rows
	 */
	public static function getMountById($id) {
		$query = 'SELECT * FROM `*PREFIX*wnd_mounts` WHERE `mount_id` = ?';
		return self::executeSelectAndGetCursor($query, [$id]);
	}

	/**
	 * Get a mount point information by its id with the credentials stored to access to the mount
	 * point. If the user is null, you might get more than one row depending on the credentials
	 * stored per each user for that mount (mainly when the mount has been set to "let users use
	 * their own credentials"), if not, we'll try to get only the credentials for that user
	 *
	 * @param int $id the id of the mount
	 * @param null|string $user the user mounting or null to get all
	 * @see \OC_DB_StatementWrapper::execute() this method returns the same values
	 * @return \OC_DB_StatementWrapper|int a DB cursor to retrieve the DB rows
	 */
	public static function getMountByIdWithCreds($id, $user = null) {
		if ($user === null) {
			$query = 'SELECT * FROM `*PREFIX*wnd_mounts` LEFT JOIN `*PREFIX*wnd_credentials` ON (`*PREFIX*wnd_mounts`.`mount_id` = `*PREFIX*wnd_credentials`.`credentials_mount_id_fk`) WHERE `*PREFIX*wnd_mounts`.`mount_id` = ?';
			return self::executeSelectAndGetCursor($query, [$id]);
		} else {
			$query = 'SELECT * FROM `*PREFIX*wnd_mounts` LEFT JOIN `*PREFIX*wnd_credentials` ON (`*PREFIX*wnd_mounts`.`mount_id` = `*PREFIX*wnd_credentials`.`credentials_mount_id_fk` AND `*PREFIX*wnd_credentials`.`mounting_user` = ?) WHERE `*PREFIX*wnd_mounts`.`mount_id` = ?';
			return self::executeSelectAndGetCursor($query, [$user, $id]);
		}
	}

	/**
	 * Delete the mount point by its id. This method will also delete the applicables for that
	 * mount because it won't be use anymore
	 *
	 * @param int $id the id for the mount point
	 */
	public static function deleteMountById($id) {
		$query = 'DELETE FROM `*PREFIX*wnd_mounts` WHERE `mount_id` = ?';
		\OC_DB::beginTransaction();
		self::executeManipulationQuery($query, [$id]);

		self::deleteApplicablesByMountId($id);
		self::deleteCustomCredentialsByMountId($id);
		\OC_DB::commit();
	}

	/**
	 * Update the admin credentials for the selected mount id
	 *
	 * @param int $mountId the mount id
	 * @param string $globalUsername the new username
	 * @param string $globalPassword the new encrypted password
	 */
	public static function updateMountGlobalCreds($mountId, $globalUsername, $globalPassword) {
		$query = 'UPDATE `*PREFIX*wnd_mounts` SET `global_username` = ?, `global_password` = ? WHERE `mount_id` = ?';
		self::executeManipulationQuery($query, [$globalUsername, $globalPassword, $mountId]);
	}

	/*
	 * Update the credentials and allow changing the credential type. $user and $pass will
	 * be required for type 0 (to use those credentials for all users accessing to that mount)
	 * Custom credentials will be deleted if the type changes
	 *
	 * @param int $mountId the id of the mount to change the credentials type
	 * @param int $type 0 to use the same credentials for all applicable users, 1
	 * if each user must provide their own credentials, 2 to use global credentials
	 * @param string $user the credentials username
	 * @param string $pass the credentials password
	 */
	public static function updateAdminMountCredentialsType($mountId, $type, $user = null, $pass = null) {
		$query = 'UPDATE `*PREFIX*wnd_mounts` SET `use_custom_credentials` = ?, `global_username` = ?, `global_password` = ? WHERE `mount_id` = ?';
		if ($type === 0) {
			self::executeManipulationQuery($query, [$type, $user, $pass, $mountId]);
			self::deleteCustomCredentialsByMountId($mountId);
		} elseif ($type === 1) {
			self::executeManipulationQuery($query, [$type, null, null, $mountId]);
		} elseif ($type === 2) {
			self::executeManipulationQuery($query, [$type, null, null, $mountId]);
			self::deleteCustomCredentialsByMountId($mountId);
		} elseif ($type === 3) {
			self::executeManipulationQuery($query, [$type, $user, null, $mountId]);
			self::deleteCustomCredentialsByMountId($mountId);
		}
	}

	/*
	 * Save credentials for the mount point per user
	 *
	 * @param int $mountId the id of the mount
	 * @param string $mountingUser the user mounting this mount point
	 * @param int $useGlobalCreds use global credentials for the user? 0 = no, 2 = yes
	 * @param string $credsUser credentials username if not using global credentials for the user
	 * @param string $credsPassword credentials password if not using global credentials
	 * for the user
	 */
	public static function saveCustomCredentials($mountId, $mountingUser, $useGlobalCreds, $credsUser, $credsPassword) {
		$cursor = self::getCustomCredentials($mountId, $mountingUser);
		if ($cursor !== false && $cursor->fetchRow() !== false) {
			// we have an entry for the user and that mountid, we'll need to update
			$query = 'UPDATE `*PREFIX*wnd_credentials` SET `use_global_credentials` = ? , `credential_username` = ? , `credential_password` = ? WHERE `credentials_mount_id_fk` = ? AND `mounting_user` = ?';
			$params = [$useGlobalCreds, $credsUser, $credsPassword, $mountId, $mountingUser];
		} else {
			$query = 'INSERT INTO `*PREFIX*wnd_credentials` (`credentials_mount_id_fk`, `mounting_user`, `use_global_credentials`, `credential_username`, `credential_password`) VALUES (?,?,?,?,?)';
			$params = [$mountId, $mountingUser, $useGlobalCreds, $credsUser, $credsPassword];
		}
		self::executeManipulationQuery($query, $params);
	}

	/*
	 * Get the custom credentials for the mount and the specific user. This method will return
	 * the DB row, but if this mount is using global credentials for the user, those credentials
	 * won't be returned. (Check the use_global_credentials field to know where you should pick
	 * the credentials from)
	 *
	 * @param int $mountId the id of the mount
	 * @param string $mountingUser the user mounting this mount
	 * @return \OC_DB_StatementWrapper|int a DB cursor to retrieve the DB rows
	 */
	public static function getCustomCredentials($mountId, $mountingUser) {
		$query = 'SELECT * FROM `*PREFIX*wnd_credentials` WHERE `credentials_mount_id_fk` = ? and `mounting_user` = ?';
		return self::executeSelectAndGetCursor($query, [$mountId, $mountingUser]);
	}

	/**
	 * Delete all the custom credentials for the mount
	 *
	 * @param int $mountId the id of the mount
	 */
	public static function deleteCustomCredentialsByMountId($mountId) {
		$query = 'DELETE FROM `*PREFIX*wnd_credentials` WHERE `credentials_mount_id_fk` = ?';
		self::executeManipulationQuery($query, [$mountId]);
	}

	/**
	 * Save the applicables for the mount id. The "saveMount" method of this class uses this
	 * method internally, so there is no need to save the applicables again
	 *
	 * @param int $mountId the id of the mount
	 * @param array $applicables an array with possible values: $appl['global'] if the mount
	 * point is available for all users, $appl['groups'] = array('g1', 'g2', 'g3') to apply
	 * the mount point to the groups "g1", "g2" and "g3", and / or $appl['users'] = array('u1',
	 * 'u2', 'u2') to apply the mount point to users "u1", "u2" and "u3"
	 */
	public static function saveApplicables($mountId, $applicables) {
		// delete the applicables and reinsert them seems faster than check one by one
		\OC_DB::beginTransaction();
		$deleteQuery = 'DELETE FROM `*PREFIX*wnd_mounts_applicable` WHERE `applicable_mount_id_fk` = ?';
		self::executeManipulationQuery($deleteQuery, [$mountId]);

		$dataArray = [];
		if (isset($applicables['global'])) {
			$dataArray[] = [$mountId, 'global', null];
		}
		if (isset($applicables['groups'])) {
			foreach ($applicables['groups'] as $group) {
				$dataArray[] = [$mountId, 'group', $group];
			}
		}
		if (isset($applicables['users'])) {
			foreach ($applicables['users'] as $user) {
				$dataArray[] = [$mountId, 'user', $user];
			}
		}

		$insertQuery = 'INSERT INTO `*PREFIX*wnd_mounts_applicable` (`applicable_mount_id_fk`, `mount_type`, `mount_type_name`) VALUES (?,?,?)';
		self::executeManipulationQueryList($insertQuery, $dataArray);
		\OC_DB::commit();
	}

	/**
	 * Get the applicables for the mount id
	 *
	 * @param int $mountId the id of the mount
	 * @return \OC_DB_StatementWrapper|int a DB cursor to retrieve the DB rows
	 */
	public static function getApplicablesForMountId($mountId) {
		$query = 'SELECT * FROM `*PREFIX*wnd_mounts_applicable` WHERE `applicable_mount_id_fk` = ?';
		return self::executeSelectAndGetCursor($query, [$mountId]);
	}

	/**
	 * Delete the applicables for the mount id
	 *
	 * @param int $mountId the id of the mount
	 */
	public static function deleteApplicablesByMountId($mountId) {
		$query = 'DELETE FROM `*PREFIX*wnd_mounts_applicable` WHERE `applicable_mount_id_fk` = ?';
		return self::executeManipulationQuery($query, [$mountId]);
	}

	/**
	 * Helper function over the getAllMountsWithApplicables one. This function does extra
	 * processing.
	 *
	 * The function return an array (instead of a cursor) with extra data (the "applicables"
	 * field) like the following:
	 *
	 * <code>
	 * $mounts = array(0 => array('mount_id' => id,
									'url' => url,
									.... (other DB fields)
									'applicables' => array('global', 'adminGroup(group)',
															'comercials(group)', 'Joe(user)',
															'Mary(user)')
									),
					 1 => array(.....),
					 2 => array(.....));
	 * </code>
	 *
	 * Each mount id will be unique, with all the data coming from the DB (in case the mount id
	 * has several applicables, just the info for the first one will be returned).
	 *
	 * Notice, the information about the mount point should be correct, but for the applicables
	 * use the information in the "applicables" field
	 *
	 * @return array as explained in the description
	 */
	public static function getAllMountsWithApplicablesParsed() {
		$mountsCursor = self::getAllMountsWithApplicables();
		$globalMounts = [];
		if ($mountsCursor !== false) {
			$currentMountId = null;
			while (($row = $mountsCursor->fetchRow()) !== false) {
				if ($currentMountId !== $row['mount_id']) {
					$row['applicables'] = [];
					$globalMounts[] = $row;
					$currentMountId = $row['mount_id'];
				}
				$applicable = ($row['mount_type'] === 'global') ? 'global' : $row['mount_type_name'] . "(" . $row['mount_type'] . ")";
				\end($globalMounts);
				$globalMounts[\key($globalMounts)]['applicables'][] = $applicable;
			}
		}
		return $globalMounts;
	}

	/**
	 * Save a personal mount point in the DB.
	 *
	 * @param string $mountPoint the mount point where the FS will be mounted (the folder name
	 * will match this)
	 * @param string $url the url for the mount point so the connector knows where it should
	 * connect
	 * @param string $share the share name
	 * @param string $root the folder that will act as root
	 * @param string $mountingUser the user that will mount this mount point
	 * @param string $credsUser username to use to connect
	 * @param string $credsPassword password to use to connect
	 */
	public static function savePersonalMount($mountPoint, $url, $share, $root, $mountingUser, $useGlobalCreds, $credsUser, $credsPassword) {
		$query = 'INSERT INTO `*PREFIX*wnd_personal_mounts` (`mount_point`, `mount_url`, `share`, `root`, `target_user`, `use_global_credentials`, `creds_username`, `creds_password`) values (?,?,?,?,?,?,?,?)';
		self::executeManipulationQuery($query, [$mountPoint, $url, $share, $root, $mountingUser, $useGlobalCreds, $credsUser, $credsPassword]);
	}

	/**
	 * Get the personal mount by the id
	 *
	 * @param int $mountId the id of the mount
	 * @see \OC_DB_StatementWrapper::execute() this method returns the same values
	 * @return \OC_DB_StatementWrapper|int a DB cursor to retrieve the DB rows
	 */
	public static function getPersonalMountById($mountId) {
		$query = 'SELECT * FROM `*PREFIX*wnd_personal_mounts` WHERE `personal_mount_id` = ?';
		return self::executeSelectAndGetCursor($query, [$mountId]);
	}

	/**
	 * Get all personal mounts
	 *
	 * @see \OC_DB_StatementWrapper::execute() this method returns the same values
	 * @return \OC_DB_StatementWrapper|int a DB cursor to retrieve the DB rows
	 */
	public static function getAllPersonalMounts() {
		$query = 'SELECT * FROM `*PREFIX*wnd_personal_mounts`';
		return self::executeSelectAndGetCursor($query, []);
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
		$query = 'SELECT * FROM `*PREFIX*wnd_personal_mounts` WHERE `personal_mount_id` = ? AND `target_user` = ?';
		return self::executeSelectAndGetCursor($query, [$mountId, $user]);
	}

	/**
	 * Get the personal mounts for an specific user
	 *
	 * @param string $user the user that owns the mount points
	 * @see \OC_DB_StatementWrapper::execute() this method returns the same values
	 * @return \OC_DB_StatementWrapper|int a DB cursor to retrieve the DB rows
	 */
	public static function getPersonalMountsPerUser($user) {
		$query = 'SELECT * FROM `*PREFIX*wnd_personal_mounts` WHERE `target_user` = ?';
		return self::executeSelectAndGetCursor($query, [$user]);
	}

	/**
	 * Update the credentials for the mount point
	 *
	 * @param int $mountId the mount id
	 * @param string $credsUser the new username
	 * @param string $credsPassword the new encrypted password
	 */
	public static function updatePersonalMountCredentials($mountId, $credsUser, $credsPassword) {
		$query = 'UPDATE `*PREFIX*wnd_personal_mounts` SET `creds_username` = ?, `creds_password` = ? WHERE `personal_mount_id` = ?';
		self::executeManipulationQuery($query, [$credsUser, $credsPassword, $mountId]);
	}

	public static function updatePersonalMountCredentialsType($mountId, $type, $credsUser = null, $credsPassword = null) {
		$query = 'UPDATE `*PREFIX*wnd_personal_mounts` SET `use_global_credentials` = ?, `creds_username` = ?, `creds_password` = ? WHERE `personal_mount_id` = ?';
		if ($type === 0) {
			self::executeManipulationQuery($query, [$type, $credsUser, $credsPassword, $mountId]);
		} elseif ($type === 2) {
			self::executeManipulationQuery($query, [$type, null, null, $mountId]);
		}
	}

	/**
	 * Delete a personal mount point based on its id
	 * @see deletePersonalMountByIdAndUser
	 * @param int $mountId the id of the mount
	 */
	public static function deletePersonalMountById($mountId) {
		$query = 'DELETE FROM `*PREFIX*wnd_personal_mounts` WHERE `personal_mount_id` = ?';
		self::executeManipulationQuery($query, [$mountId]);
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
		$query = 'DELETE FROM `*PREFIX*wnd_personal_mounts` WHERE `personal_mount_id` = ? and `target_user` = ?';
		self::executeManipulationQuery($query, [$mountId, $user]);
	}

	/**
	 * Delete all personal mount points
	 */
	public static function clearPersonalMounts() {
		// truncate doesn't work on sqlite, might be worthy to drop the table and recreate it
		// instead of delete each row
		$query = 'DELETE FROM `*PREFIX*wnd_personal_mounts`';
		self::executeManipulationQuery($query, []);
	}

	/**
	 * Get the global credentials for the administrator. There should be only 1 row for admin
	 * credentials, so we'll only return 1 row
	 *
	 * @return \OC_DB_StatementWrapper|int a DB cursor to retrieve the DB rows
	 */
	public static function getAdminGlobalCredentials() {
		$query = 'SELECT * FROM `*PREFIX*wnd_global_creds` WHERE `is_admin_credentials` = 1';
		return self::executeSelectAndGetCursor($query, [], 1);
	}

	/**
	 * Return the cached row. The result is the row that the getAdminGlobalCredentials method
	 * returns
	 *
	 * @return array The cached row from the getAdminGlobalCredentials (not the cursor)
	 */
	public static function getAdminGlobalCredentialsCached() {
		if (!isset(self::$globalAdminCreds)) {
			$cursor = self::getAdminGlobalCredentials();
			if ($cursor) {
				$row = $cursor->fetchRow();
				self::$globalAdminCreds = $row;
			}
		}
		return self::$globalAdminCreds;
	}

	/**
	 * Save or update global credentials for the administrator
	 *
	 * @param string $user the user saving the admin credentials
	 * @param string $cred_username the credetials username
	 * @param string $cred_password the credetials password
	 */
	public static function saveAdminGlobalCredentials($user, $cred_username, $cred_password) {
		$cursor = self::getAdminGlobalCredentials();
		if ($cursor !== false && $cursor->fetchRow() !== false) {
			// we have an entry for the user and that mountid, we'll need to update
			$query = 'UPDATE `*PREFIX*wnd_global_creds` SET `global_username` = ? , `global_password` = ? , `target_user` = ? WHERE `is_admin_credentials` = 1';
			$params = [$cred_username, $cred_password, $user];
		} else {
			$query = 'INSERT INTO `*PREFIX*wnd_global_creds` (`target_user`, `global_username`, `global_password`, `is_admin_credentials`) VALUES (?,?,?,1)';
			$params = [$user, $cred_username, $cred_password];
		}
		self::executeManipulationQuery($query, $params);
	}

	/**
	 * Get the global credentials for the user. There should be only 1 row for admin
	 * credentials, so we'll only return 1 row
	 *
	 * @param string $user the user whose global credentials we want to get
	 * @return \OC_DB_StatementWrapper|int a DB cursor to retrieve the DB rows
	 */
	public static function getPersonalGlobalCredentials($user) {
		$query = 'SELECT * FROM `*PREFIX*wnd_global_creds` WHERE `target_user` = ? AND `is_admin_credentials` = 0';
		return self::executeSelectAndGetCursor($query, [$user], 1);
	}

	/**
	 * Return the cached row. The result is the row that the getPersonalGlobalCredentials method
	 * returns
	 *
	 * @return array The cached row from the getPersonalGlobalCredentials (not the cursor)
	 */
	public static function getPersonalGlobalCredentialsCached($user) {
		if (!isset(self::$globalUserCreds[$user])) {
			$cursor = self::getPersonalGlobalCredentials($user);
			if ($cursor) {
				$row = $cursor->fetchRow();
				self::$globalUserCreds[$user] = $row;
			}
		}
		return self::$globalUserCreds[$user];
	}

	/**
	 * Save or update global credentials for the user
	 *
	 * @param string $user the user saving the credentials
	 * @param string $cred_username the credetials username
	 * @param string $cred_password the credetials password
	 */
	public static function savePersonalGlobalCredentials($user, $cred_username, $cred_password) {
		$cursor = self::getPersonalGlobalCredentials($user);
		if ($cursor !== false && $cursor->fetchRow() !== false) {
			// we have an entry for the user and that mountid, we'll need to update
			$query = 'UPDATE `*PREFIX*wnd_global_creds` SET `global_username` = ? , `global_password` = ? WHERE `target_user` = ? AND `is_admin_credentials` = 0';
			$params = [$cred_username, $cred_password, $user];
		} else {
			$query = 'INSERT INTO `*PREFIX*wnd_global_creds` (`target_user`, `global_username`, `global_password`, `is_admin_credentials`) VALUES (?,?,?,0)';
			$params = [$user, $cred_username, $cred_password];
		}
		self::executeManipulationQuery($query, $params);
	}

	public static function updateGlobalCredentials($user, $column, $value, $isPersonal = true) {
		if ($column !== 'global_username' && $column !== 'global_password') {
			return;
		}

		$isAdmin = ($isPersonal) ? 0 : 1;
		$query = 'UPDATE `*PREFIX*wnd_global_creds` SET `' . $column .'` = ? WHERE `target_user` = ? AND `is_admin_credentials` = ?';
		$params = [$value, $user, $isAdmin];
		self::executeManipulationQuery($query, $params);
	}

	/**
	 * Remove all global credentials (including user and admin)
	 */
	public static function wipeOutGlobalCredentials() {
		$query = 'DELETE FROM `*PREFIX*wnd_global_creds`';
		self::executeManipulationQuery($query, []);
	}

	private static function executeManipulationQuery($stmtString, array $stmtParams) {
		$query = \OC_DB::prepare($stmtString);
		$result = $query->execute($stmtParams);
		return $result;
	}

	private static function executeManipulationQueryList($stmtString, array $stmtParamsList) {
		$query = \OC_DB::prepare($stmtString);
		foreach ($stmtParamsList as $params) {
			$query->execute($params);
		}
	}

	private static function executeSelectAndGetCursor($stmtString, array $stmtParams, $limit = null) {
		$query = \OC_DB::prepare($stmtString, $limit);
		$result = $query->execute($stmtParams);
		return $result;
	}
}
