<?php
/**
 * ownCloud - Ransomware Protection
 *
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 * @copyright 2017 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Ransomware_Protection;

use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Files\ForbiddenException;

/**
 * The wrapper matches file names with blacklist patterns and denies write access
 * for those files when the blacklist is hit.
 *
 * When account locking is enabled in appconfig any further write access by
 * sync clients is prevented as well.
 */
class StorageWrapper extends Wrapper {
	public const CLIENTS_ONLY = 'clients_only';

	/** @var Blocker */
	protected $blocker;

	/** @var Blacklist */
	protected $blacklist;

	/**
	 * @param array $parameters
	 */
	public function __construct($parameters) {
		parent::__construct($parameters);
		$this->blocker = $parameters['blocker'];
		$this->blacklist = $parameters['blacklist'];
	}

	/**
	 * Match path with blacklist, block and throw Exception, if applicable
	 *
	 * @param string $path
	 * @param string $clientsOnly [optional]
	 * @throws ForbiddenException
	 */
	protected function checkLocked($path, $clientsOnly = null) {
		$match = $this->blacklist->match($path);
		$lockingEnabled = (bool)\OC::$server->getConfig()->getAppValue(
			'ransomware_protection',
			'lockingEnabled',
			Blocker::LOCKING_ENABLED_DEFAULT
		);

		if (!empty($match)) {
			if ($lockingEnabled) {
				$this->blocker->lock($match);
				if ($clientsOnly === self::CLIENTS_ONLY) {
					if ($this->blocker->isLockedAndClient()) {
						throw new ForbiddenException($this->blocker->getLockedMessage(), false);
					}
				} else {
					throw new ForbiddenException($this->blocker->getLockedMessage(), false);
				}
			} else {
				$this->blocker->block($match);
				throw new ForbiddenException($this->blocker->getBlockedMessage($match['pattern']), false);
			}
		} else {
			// block even when no match, if locking is enabled
			if ($lockingEnabled && $this->blocker->isLockedAndClient()) {
				throw new ForbiddenException($this->blocker->getLockedMessage(), false);
			}
		}
	}

	/**
	 * see http://php.net/manual/en/function.mkdir.php
	 *
	 * @param string $path
	 * @return bool
	 */
	public function mkdir($path) {
		$this->checkLocked($path, self::CLIENTS_ONLY);
		return $this->getWrapperStorage()->mkdir($path);
	}

	/**
	 * see http://php.net/manual/en/function.rmdir.php
	 *
	 * @param string $path
	 * @return bool
	 */
	public function rmdir($path) {
		$this->checkLocked($path, self::CLIENTS_ONLY);
		return $this->getWrapperStorage()->rmdir($path);
	}

	/**
	 * check if a file can be created in $path
	 *
	 * @param string $path
	 * @return bool
	 */
	public function isCreatable($path) {
		$this->checkLocked($path);
		return $this->getWrapperStorage()->isCreatable($path);
	}

	/**
	 * check if a file can be written to
	 *
	 * @param string $path
	 * @return bool
	 */
	public function isUpdatable($path) {
		$this->checkLocked($path);
		return $this->getWrapperStorage()->isUpdatable($path);
	}

	/**
	 * check if a file can be deleted
	 *
	 * @param string $path
	 * @return bool
	 */
	public function isDeletable($path) {
		$this->checkLocked($path, self::CLIENTS_ONLY);
		return $this->getWrapperStorage()->isDeletable($path);
	}

	/**
	 * see http://php.net/manual/en/function.file_put_contents.php
	 *
	 * @param string $path
	 * @param string $data
	 * @return bool
	 */
	public function file_put_contents($path, $data) {
		$this->checkLocked($path);
		return $this->getWrapperStorage()->file_put_contents($path, $data);
	}

	/**
	 * see http://php.net/manual/en/function.unlink.php
	 *
	 * @param string $path
	 * @return bool
	 */
	public function unlink($path) {
		$this->checkLocked($path, self::CLIENTS_ONLY);
		return $this->getWrapperStorage()->unlink($path);
	}

	/**
	 * see http://php.net/manual/en/function.rename.php
	 *
	 * @param string $path1
	 * @param string $path2
	 * @return bool
	 */
	public function rename($path1, $path2) {
		$this->checkLocked($path2, self::CLIENTS_ONLY);
		return $this->getWrapperStorage()->rename($path1, $path2);
	}

	/**
	 * see http://php.net/manual/en/function.copy.php
	 *
	 * @param string $path1
	 * @param string $path2
	 * @return bool
	 */
	public function copy($path1, $path2) {
		$this->checkLocked($path2);
		return $this->getWrapperStorage()->copy($path1, $path2);
	}

	/**
	 * see http://php.net/manual/en/function.fopen.php
	 *
	 * @param string $path
	 * @param string $mode
	 * @return resource
	 */
	public function fopen($path, $mode) {
		$writeModes = ['r+', 'rb+', 'w+', 'wb+', 'x+', 'xb+', 'a+', 'ab+', 'w', 'wb', 'x', 'xb', 'a', 'ab'];
		if (\in_array($mode, $writeModes)) {
			$this->checkLocked($path);
		}
		return $this->getWrapperStorage()->fopen($path, $mode);
	}

	/**
	 * see http://php.net/manual/en/function.touch.php
	 * If the backend does not support the operation, false should be returned
	 *
	 * @param string $path
	 * @param int $mtime
	 * @return bool
	 */
	public function touch($path, $mtime = null) {
		$this->checkLocked($path);
		return $this->getWrapperStorage()->touch($path, $mtime);
	}

	/**
	 * @param \OCP\Files\Storage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @return bool
	 */
	public function copyFromStorage(\OCP\Files\Storage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		$this->checkLocked($targetInternalPath, self::CLIENTS_ONLY);
		if ($sourceStorage === $this) {
			return $this->copy($sourceInternalPath, $targetInternalPath);
		}

		return $this->getWrapperStorage()->copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	/**
	 * @param \OCP\Files\Storage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @return bool
	 */
	public function moveFromStorage(\OCP\Files\Storage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		$this->checkLocked($targetInternalPath, self::CLIENTS_ONLY);
		if ($sourceStorage === $this) {
			return $this->rename($sourceInternalPath, $targetInternalPath);
		}

		return $this->getWrapperStorage()->moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}
}
