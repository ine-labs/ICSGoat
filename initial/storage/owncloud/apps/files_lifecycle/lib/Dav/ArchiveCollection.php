<?php
/**
 * ownCloud
 *
 * @author Michael Barz <mbarz@owncloud.com>
 * @copyright (C) 2021 ownCloud GmbH
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

use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use Sabre\DAV\Collection;
use Sabre\DAV\Exception\NotFound;

/**
 * This is an empty collection
 *
 * @package OCA\Files_Lifecycle\Dav
 */
class ArchiveCollection extends Collection {
	/** @var string */
	private $name;

	/**
	 * TrashBinHome constructor.
	 *
	 * @param string $name
	 */
	public function __construct(string $name) {
		$this->name= $name;
	}

	/**
	 * @param string $elementName
	 *
	 * @return ArchiveCollection
	 *
	 * @@throws NotFound
	 */
	public function getChild($elementName) {
		if ($elementName !== 'files') {
			throw new NotFound();
		}
		return new ArchiveCollection('files');
	}

	public function getChildren() {
		return [];
	}

	public function delete() {
		throw new Forbidden('Permission denied to delete this folder');
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		throw new Forbidden('Permission denied to rename this folder');
	}

	public function getLastModified() {
		return 0;
	}
}
