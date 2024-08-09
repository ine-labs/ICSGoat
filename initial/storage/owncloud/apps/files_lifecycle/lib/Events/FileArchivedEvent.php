<?php
/**
 * ownCloud
 *
 * @author Tom Needham <tom@owncloud.com>
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

namespace OCA\Files_Lifecycle\Events;

use OCP\Files\FileInfo;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class FileArchivedEvent
 *
 * @package OCA\Files_Lifecycle\Audit
 */
/** @phan-suppress-next-line PhanDeprecatedClass */
class FileArchivedEvent extends Event {
	public const EVENT_NAME = 'lifecycle:file_archived';

	/**
	 * @var FileInfo
	 */
	protected $originalFile;
	/**
	 * @var FileInfo
	 */
	protected $archivedFile;

	/**
	 * FileArchivedEvent constructor.
	 *
	 * @param FileInfo $originalFile
	 * @param FileInfo $archivedFile
	 */
	public function __construct(FileInfo $originalFile, FileInfo $archivedFile) {
		$this->originalFile = $originalFile;
		$this->archivedFile = $archivedFile;
	}

	/**
	 * @return FileInfo
	 */
	public function getOriginalFileInfo() {
		return $this->originalFile;
	}

	/**
	 * @return FileInfo
	 */
	public function getArchivedFileInfo() {
		return $this->archivedFile;
	}
}
