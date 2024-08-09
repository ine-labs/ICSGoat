<?php
/**
 * ownCloud Workflow
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright 2015 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Workflow\Engine\Wrapper;

use OC\Files\Storage\Wrapper\Wrapper;
use OCA\Workflow\Engine\Plugin;
use OCA\Workflow\PublicAPI\Event\FileActionInterface;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Storage;

class StorageWrapper extends Wrapper {
	/** @var \OC\Files\Storage\Storage */
	protected $storage;

	/** @var string */
	protected $mountPoint;

	/** @var Plugin */
	protected $plugin;

	/**
	 * @param array $arguments
	 */
	public function __construct($arguments) {
		parent::__construct($arguments);
		$this->storage = $arguments['storage'];
		$this->mountPoint = $arguments['mountPoint'];
		$this->plugin = $arguments['plugin'];
	}

	/**
	 * @return bool
	 */
	protected function isUserHome() {
		return !$this->storage->instanceOfStorage('OC\Files\Storage\Shared');
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	protected function isPartFile($path) {
		// test.txt.ocTransferId3462648575.part
		$pathInfo = \pathinfo($path);

		if (isset($pathInfo['extension']) && $pathInfo['extension'] === 'part') {
			// test.txt.ocTransferId3462648575
			$pathInfo2 = \pathinfo($pathInfo['filename']);
			if (\preg_match('/^ocTransferId\d+$/', $pathInfo2['extension'])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $path
	 * @return array
	 */
	public function getParentIds($path) {
		$cache = $this->getCache();
		$parents = [];
		$fileId = null;

		$entry = $cache->get($path);
		if ($entry instanceof ICacheEntry) {
			$fileId = $entry['fileid'];
		} else {
			$parentDir = \dirname($path);
			$entry = $cache->get($parentDir);

			if ($entry === false) {
				return ['fileId' => null, 'parentIds' => null];
			}
		}

		$parentId = $entry['fileid'];

		while ($parentId > 0) {
			$parents[] = $parentId;
			$parentData = $cache->get($parentId);
			$parentId = $parentData['parent'];
		}

		return ['fileId' => $fileId, 'parentIds' => $parents];
	}

	/**
	 * see http://php.net/manual/en/function.mkdir.php
	 *
	 * @param string $path
	 * @return bool
	 */
	public function mkdir($path) {
		$return = $this->storage->mkdir($path);

		if ($this->isUserHome() && $return !== false) {
			// Absolute path: /<username</files/<path>
			$absolutePath = $this->mountPoint . $path;

			$pathSegments = \explode('/', $absolutePath, 4);
			if (\sizeof($pathSegments) === 4 && $pathSegments[2] === 'files' && !$this->isPartFile($pathSegments[3])) {
				$ids = $this->getParentIds($path);
				$this->plugin->trigger(FileActionInterface::FILE_CREATE, $absolutePath, $ids['fileId'], $ids['parentIds']);
			}
		}

		return $return;
	}

	/**
	 * see http://php.net/manual/en/function.rmdir.php
	 *
	 * @param string $path
	 * @return bool
	 */
	public function rmdir($path) {
		$ids = ['fileId' => null, 'parentIds' => null];
		if ($this->isUserHome()) {
			$ids = $this->getParentIds($path);
		}

		$return = $this->storage->rmdir($path);

		if ($this->isUserHome() && $return !== false) {
			// Absolute path: /<username</files/<path>
			$absolutePath = $this->mountPoint . $path;

			$pathSegments = \explode('/', $absolutePath, 4);
			if (\sizeof($pathSegments) === 4 && $pathSegments[2] === 'files' && !$this->isPartFile($pathSegments[3])) {
				$this->plugin->trigger(FileActionInterface::FILE_DELETE, $absolutePath, $ids['fileId'], $ids['parentIds']);
			}
		}

		return $return;
	}

	/**
	 * see http://php.net/manual/en/function.file_put_contents.php
	 *
	 * @param string $path
	 * @param string $data
	 * @return bool
	 */
	public function file_put_contents($path, $data) {
		$fileExists = $this->storage->file_exists($path);
		$return = $this->storage->file_put_contents($path, $data);

		if ($this->isUserHome() && $return !== false) {
			// Absolute path: /<username</files/<path>
			$absolutePath = $this->mountPoint . $path;

			$pathSegments = \explode('/', $absolutePath, 4);
			if (\sizeof($pathSegments) === 4 && $pathSegments[2] === 'files' && !$this->isPartFile($pathSegments[3])) {
				$ids = $this->getParentIds($path);
				if ($fileExists) {
					$this->plugin->trigger(FileActionInterface::FILE_UPDATE, $absolutePath, $ids['fileId'], $ids['parentIds']);
				} else {
					$this->plugin->trigger(FileActionInterface::FILE_CREATE, $absolutePath, $ids['fileId'], $ids['parentIds']);
				}
			}
		}

		return $return;
	}

	/**
	 * see http://php.net/manual/en/function.unlink.php
	 *
	 * @param string $path
	 * @return bool
	 */
	public function unlink($path) {
		$ids = ['fileId' => null, 'parentIds' => null];
		if ($this->isUserHome()) {
			$ids = $this->getParentIds($path);
		}

		$return = $this->storage->unlink($path);

		if ($this->isUserHome() && $return !== false) {
			// Absolute path: /<username</files/<path>
			$absolutePath = $this->mountPoint . $path;

			$pathSegments = \explode('/', $absolutePath, 4);
			if (\sizeof($pathSegments) === 4 && $pathSegments[2] === 'files' && !$this->isPartFile($pathSegments[3])) {
				$this->plugin->trigger(FileActionInterface::FILE_DELETE, $absolutePath, $ids['fileId'], $ids['parentIds']);
			}
		}

		return $return;
	}

	/**
	 * see http://php.net/manual/en/function.rename.php
	 *
	 * @param string $path1
	 * @param string $path2
	 * @return bool
	 */
	public function rename($path1, $path2) {
		$fileExists = $this->storage->file_exists($path2);

		// Absolute path: /<username</files/<path>
		$absolutePath1 = $this->mountPoint . $path1;
		$absolutePath2 = $this->mountPoint . $path2;

		$pathSegments1 = \explode('/', $absolutePath1, 4);
		$pathSegments2 = \explode('/', $absolutePath2, 4);

		if ($pathSegments1[2] === 'files' && $pathSegments2[2] === 'files_trashbin') {
			$ids = $this->getParentIds($path1);
		}

		$return = $this->storage->rename($path1, $path2);

		if ($this->isUserHome() && $return !== false) {
			if (\sizeof($pathSegments1) !== 4 || \sizeof($pathSegments2) !== 4) {
				return $return;
			}

			if ($pathSegments2[2] === 'files' && !$this->isPartFile($pathSegments2[3])) {
				$ids = $this->getParentIds($path2);
				if ($pathSegments1[2] === 'files' && !$this->isPartFile($pathSegments1[3])) {
					$this->plugin->trigger(FileActionInterface::FILE_RENAME, $absolutePath2, $ids['fileId'], $ids['parentIds']);
				} elseif ($fileExists) {
					$this->plugin->trigger(FileActionInterface::FILE_UPDATE, $absolutePath2, $ids['fileId'], $ids['parentIds']);
				} else {
					$this->plugin->trigger(FileActionInterface::FILE_CREATE, $absolutePath2, $ids['fileId'], $ids['parentIds']);
				}
			} elseif ($pathSegments2[2] === 'files_trashbin' && $pathSegments1[2] === 'files' && isset($ids)) {
				// Trashbin wrapper around a SharedStorage gives us a move instead of the actual unlink/rmdir
				$this->plugin->trigger(FileActionInterface::FILE_DELETE, $absolutePath1, $ids['fileId'], $ids['parentIds']);
			}
		}

		return $return;
	}

	/**
	 * see http://php.net/manual/en/function.fopen.php
	 *
	 * @param string $path
	 * @param string $mode
	 * @return resource
	 */
	public function fopen($path, $mode) {
		$fileExists = $this->storage->file_exists($path);
		$return = $this->storage->fopen($path, $mode);

		if ($this->isUserHome() && $return !== false) {
			// Absolute path: /<username</files/<path>
			$absolutePath = $this->mountPoint . $path;

			$pathSegments = \explode('/', $absolutePath, 4);
			if (\sizeof($pathSegments) === 4 && $pathSegments[2] === 'files' && !$this->isPartFile($pathSegments[3])) {
				if ($mode !== 'r' && $mode !== 'rb') {
					$ids = $this->getParentIds($path);
					if ($ids['fileId'] !== null) {
						if ($fileExists) {
							$this->plugin->trigger(FileActionInterface::FILE_UPDATE, $absolutePath, $ids['fileId'], $ids['parentIds']);
						} else {
							$this->plugin->trigger(FileActionInterface::FILE_CREATE, $absolutePath, $ids['fileId'], $ids['parentIds']);
						}
					}
				}
			}
		}

		return $return;
	}

	/**
	 * get a cache instance for the storage
	 *
	 * @param string $path
	 * @param \OC\Files\Storage\Storage $storage (optional) the storage to pass to the cache
	 * @return \OC\Files\Cache\Cache
	 */
	public function getCache($path = '', $storage = null) {
		if (!$storage) {
			$storage = $this;
		}

		return new CacheWrapper(
			$this->storage->getCache($path, $storage),
			$this->mountPoint,
			$this->isUserHome(),
			$this->plugin
		);
	}

	/**
	 * @param Storage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @return bool
	 */
	public function moveFromStorage(Storage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		if ($sourceStorage === $this) {
			return $this->rename($sourceInternalPath, $targetInternalPath);
		}

		$fileExists = $this->storage->file_exists($targetInternalPath);
		$return = $this->storage->moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);

		if ($this->isUserHome() && $return !== false) {
			// Absolute path: /<username</files/<path>
			$absolutePath2 = $this->mountPoint . $targetInternalPath;

			$pathSegments2 = \explode('/', $absolutePath2, 4);

			if (\sizeof($pathSegments2) !== 4) {
				return $return;
			}

			if ($pathSegments2[2] === 'files' && !$this->isPartFile($pathSegments2[3])) {
				$ids = $this->getParentIds($targetInternalPath);
				if ($fileExists) {
					$this->plugin->trigger(FileActionInterface::FILE_UPDATE, $absolutePath2, $ids['fileId'], $ids['parentIds']);
				} else {
					$this->plugin->trigger(FileActionInterface::FILE_CREATE, $absolutePath2, $ids['fileId'], $ids['parentIds']);
				}
			}
		}

		return $return;
	}
}
