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

namespace OCA\Files_Lifecycle\Dav;

use OCP\Files\FileInfo;
use OC\Files\View;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\Exception\InvalidPath;
use OCA\DAV\Connector\Sabre\ObjectTree;
use OCP\Files\ForbiddenException;
use OCP\Files\InvalidPathException;
use OCP\Files\StorageNotAvailableException;
use OCP\Share\IManager;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\ServiceUnavailable;
use Sabre\DAV\INode;

/**
 * Class ArchiveDirectory
 *
 * @package OCA\Files_Lifecycle\Dav
 */
class ArchiveDirectory extends Directory {
	/**
	 * @var ObjectTree $tree
	 */
	private $tree;

	/**
	 * ArchiveDirectory constructor.
	 *
	 * @param View $view
	 * @param FileInfo $info
	 * @param null|ObjectTree $tree
	 * @param null|IManager $shareManager
	 */
	public function __construct(
		View $view,
		FileInfo $info,
		ObjectTree $tree = null,
		IManager $shareManager = null
	) {
		parent::__construct($view, $info, $tree, $shareManager);
	}

	/**
	 * @param string $name
	 * @param FileInfo|null $info
	 *
	 * @return INode
	 *
	 * @throws Forbidden
	 * @throws InvalidPath
	 * @throws NotFound
	 * @throws ServiceUnavailable
	 */
	public function getChild($name, $info = null) {
		if (!$this->info->isReadable()) {
			// avoid detecting files through this way
			throw new NotFound();
		}

		$path = $this->path . '/' . $name;
		if ($info === null) {
			try {
				$this->fileView->verifyPath($this->path, $name);
				$info = $this->fileView->getFileInfo($path);
			} catch (StorageNotAvailableException $e) {
				throw new ServiceUnavailable($e->getMessage());
			} catch (InvalidPathException $ex) {
				throw new InvalidPath($ex->getMessage());
			} catch (ForbiddenException $e) {
				throw new Forbidden();
			}
		}

		if (!$info) {
			throw new NotFound('File with name ' . $path . ' could not be located');
		}

		if ($info['mimetype'] === 'httpd/unix-directory') {
			$node = new ArchiveDirectory(
				$this->fileView,
				$info,
				$this->tree,
				$this->shareManager
			);
		} else {
			$node = new ArchivedFile($this->fileView, $info, $this->shareManager);
		}
		if (isset($this->tree)) {
			$this->tree->cacheNode($node);
		}
		return $node;
	}

	/**
	 * @param string $name
	 * @param resource|string|null $data
	 *
	 * @return null|string
	 *
	 * @throws Forbidden
	 */
	public function createFile($name, $data = null) {
		throw new Forbidden(
			'This endpoint does not allow the creation of files'
		);
	}

	/**
	 * @param string $name
	 * @param null $data
	 *
	 * @return null|string
	 *
	 * @throws Forbidden
	 */
	public function createDirectory($name, $data = null) {
		throw new Forbidden(
			'Not allowed to create directories in the archive'
		);
	}

	/**
	 * @return null
	 *
	 * @throws Forbidden
	 */
	public function delete() {
		throw new Forbidden(
			'Not allowed to delete directories in the archive'
		);
	}

	/**
	 * @param string $targetName
	 * @param string $fullSourcePath
	 * @param INode $sourceNode
	 *
	 * @return bool|void
	 *
	 * @throws Forbidden
	 */
	public function moveInto($targetName, $fullSourcePath, INode $sourceNode) {
		throw new Forbidden(
			'You cannot move files into the archive'
		);
	}
}
