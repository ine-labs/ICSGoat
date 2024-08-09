<?php
/**
 * ownCloud Workflow
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright 2016 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Workflow\Engine\Wrapper;

use OCA\Workflow\Engine\Plugin;
use OCA\Workflow\PublicAPI\Event\FileActionInterface;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;

class CacheWrapper extends \OC\Files\Cache\Wrapper\CacheWrapper {

	/** @var string */
	protected $mountPoint;

	/** @var bool */
	protected $isLocalStorage;

	/** @var Plugin */
	protected $plugin;

	/**
	 * CacheWrapper constructor.
	 *
	 * @param ICache $cache
	 * @param string $mountPoint
	 * @param bool $isLocalStorage
	 * @param Plugin $plugin
	 */
	public function __construct(ICache $cache, $mountPoint, $isLocalStorage, Plugin $plugin) {
		parent::__construct($cache);
		$this->mountPoint = $mountPoint;
		$this->isLocalStorage = $isLocalStorage;
		$this->plugin = $plugin;
	}

	/**
	 * @param ICacheEntry $entry
	 * @return int[]
	 */
	public function getParentIds(ICacheEntry $entry) {
		$parents = [];
		$parentId = $entry['parent'];

		while ($parentId > 0) {
			$parents[] = $parentId;
			$parentData = $this->cache->get($parentId);
			$parentId = $parentData['parent'];
		}

		return $parents;
	}

	/**
	 * insert meta data for a new file or folder
	 *
	 * @param string $file
	 * @param array $data
	 *
	 * @return int file id
	 * @throws \RuntimeException
	 * @since 9.0.0
	 */
	public function insert($file, array $data) {
		$fileId = $this->cache->insert($file, $data);
		$absolutePath = $this->mountPoint . $file;

		$pathSegments = \explode('/', $absolutePath, 4);
		if ($this->isLocalStorage && \sizeof($pathSegments) === 4 && $pathSegments[2] === 'files') {
			$entry = $this->cache->get($fileId);
			if ($entry instanceof ICacheEntry) {
				$parents = $this->getParentIds($entry);

				$this->plugin->trigger(FileActionInterface::CACHE_INSERT, $absolutePath, $fileId, $parents);
			}
		}

		return $fileId;
	}
}
