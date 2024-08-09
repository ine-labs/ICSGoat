<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
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
use OC\Files\Node\Folder;
use OCP\Files\NotFoundException;

/**
 * Iterates over all given filesystem nodes recursively skipping
 * ext. storages, shares and failed storages.
 * Also ignores paths starting with $ignorePaths
 *
 * Use RecursiveNodeIterator::create() to correctly instantiate the iterator.
 *
 * @package OCA\DataExporter\Exporter
 */
class RecursiveNodeIterator implements \RecursiveIterator {
	/**
	 * @var array
	 */
	private $rootNodes;

	/**
	 * @var int
	 */
	private $nodeCount;

	/**
	 * @var int
	 */
	private $currentIndex;

	/**
	 * @var string[]
	 */
	private $ignorePaths = [
		'cache',
		'thumbnails',
		'uploads',
		'files',
	];

	/**
	 * Constructor
	 *
	 * @param Node[] $nodes
	 */
	public function __construct(array $nodes) {
		$this->rootNodes = \array_values(
			\array_filter(
				$nodes,
				function (Node $node) {
					return !$this->isIgnoredPath($node);
				}
			)
		);
		$this->nodeCount = \count($this->rootNodes);
		$this->currentIndex = 0;
	}

	/**
	 * @param Node $node
	 *
	 * @return bool
	 */
	private function isIgnoredPath(Node $node): bool {
		$path = $node->getPath();
		$relativePath = $path;
		$uidOwner = $node->getOwner()->getUID();
		if (\strpos($path, "/$uidOwner/") === 0) {
			$relativePath = \substr($path, \strlen("/$uidOwner/"));
		}
		foreach ($this->ignorePaths as $ignorePath) {
			if (\strpos($relativePath, $ignorePath) === 0) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param Folder $folder
	 *
	 * @return \RecursiveIteratorIterator
	 *
	 * @throws NotFoundException
	 */
	public static function create(
		Folder $folder
	): \RecursiveIteratorIterator {
		return new \RecursiveIteratorIterator(
			new RecursiveNodeIterator($folder->getDirectoryListing()),
			\RecursiveIteratorIterator::SELF_FIRST
		);
	}

	/**
	 * Returns true if an iterator can be created for the current entry.
	 *
	 * @return bool
	 *
	 * @throws NotFoundException
	 */
	public function hasChildren(): bool {
		$node = $this->rootNodes[$this->currentIndex];
		return (($node instanceof Folder)
			&& (\count($node->getDirectoryListing()) > 0));
	}

	/**
	 * Returns an iterator for the current entry.
	 *
	 * @return RecursiveNodeIterator|\RecursiveIterator
	 *
	 * @throws NotFoundException
	 */
	public function getChildren() {
		$node = $this->rootNodes[$this->currentIndex];
		if ($node instanceof Folder) {
			return new RecursiveNodeIterator($node->getDirectoryListing());
		}
		throw new NotFoundException('Cannot getChildren of node that is not a folder');
	}

	/**
	 * Access the current element value
	 *
	 * @return mixed
	 */
	public function current() {
		return $this->rootNodes[$this->currentIndex];
	}

	/**
	 * Move forward to the next element
	 *
	 * @return void
	 */
	public function next() {
		$this->currentIndex++;
	}

	/**
	 * Access the current key
	 *
	 * @return int|mixed
	 */
	public function key() {
		return $this->currentIndex;
	}

	/**
	 * Check whether the current position is valid
	 *
	 * @return bool
	 */
	public function valid() {
		return $this->currentIndex < $this->nodeCount;
	}

	/**
	 * Rewind the iterator to the first element of the top level inner iterator
	 *
	 * @return void
	 */
	public function rewind() {
		$this->currentIndex = 0;
	}
}
