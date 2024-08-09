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

use OCP\Files\Node;
use OCP\IUser;

/**
 * Interface IRestorer
 *
 * @package OCA\Files_Lifecycle
 */
interface IRestorer {
	/**
	 * @param string $path
	 * @param IUser $user
	 *
	 * @return string destination path in users home folder
	 */
	public function restorePathFromArchive($path, IUser $user);

	/**
	 * @param Node $node
	 * @param IUser $user
	 *
	 * @return mixed
	 */
	public function restoreFileFromArchive(Node $node, IUser $user);

	/**
	 * Creates the original path from the archived path
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function getRestorePath($path);

	/**
	 * Restores all files known to be in the archive
	 *
	 * @param callable $progressCallback
	 *
	 * @return void
	 */
	public function restoreAllFiles(callable $progressCallback);
}
