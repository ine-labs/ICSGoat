<?php
/**
 * ownCloud
 *
 * @author Tom Needham <tom@owncloud.com>
 * @author Michael Barz <mbarz@owncloud.com>
 * @copyright (C) 2018 ownCloud GmbH
 * @license ownCloud Commercial License
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see
 * <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Files_Lifecycle;

use OCP\IUser;

/**
 * Interface IExpirer
 *
 * @package OCA\Files_Lifecycle
 */
interface IExpirer {

	/**
	 * Expire all Files for a given User
	 *
	 * @param IUser $user
	 * @param \Closure $closure
	 * @param bool $dryRun
	 *
	 * @return void
	 */
	public function expireForUser($user, $closure, $dryRun);
	/**
	 * Expire file from the Archive
	 *
	 * @param int $fileid
	 * @param IUser $user
	 *
	 * @return void
	 */
	public function expireFile($fileid, IUser $user);
}
