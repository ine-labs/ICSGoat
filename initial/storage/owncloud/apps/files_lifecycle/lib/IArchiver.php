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

use OCP\Files\File;
use OCP\Files\NotFoundException;
use OCP\IUser;

/**
 * Interface IArchiver
 *
 * @package OCA\Files_Lifecycle
 */
interface IArchiver {

	/**
	 * Move File to the Archive
	 *
	 * @param IUser $user
	 * @param \Closure $closure
	 * @param bool $dryRun
	 *
	 * @throws NotFoundException
	 *
	 * @return void
	 */
	public function archiveForUser($user, $closure, $dryRun = false);

	/**
	 * Archive a single file
	 *
	 * @param File $file
	 * @param IUser $user
	 *
	 * @return void
	 */
	public function moveFile2Archive($file, $user);
}
