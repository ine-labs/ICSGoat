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
 * Class RestoreProcessor
 *
 * @package OCA\Files_Lifecycle
 */
class RestoreProcessor {

	/**
	 * @var  IRestorer
	 */
	protected $restorer;

	/**
	 * Expire Processor constructor.
	 *
	 * @param IRestorer $restorer
	 */
	public function __construct(IRestorer $restorer) {
		$this->restorer = $restorer;
	}

	/**
	 * Restore File from Archive Path
	 *
	 * @param string $path
	 * @param IUser $user
	 *
	 * @return string path to file after restore
	 */
	public function restoreFileFromPath($path, $user) {
		return $this->restorer->restorePathFromArchive($path, $user);
	}

	/**
	 * Get original path from the archived path
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function getRestorePath($path) {
		return $this->restorer->getRestorePath($path);
	}

	/**
	 * Restore all files known to be in the archive
	 *
	 * @param callable $progressCallback
	 *
	 * @return void
	 */
	public function restoreAllFiles(callable $progressCallback) {
		$this->restorer->restoreAllFiles($progressCallback);
	}
}
