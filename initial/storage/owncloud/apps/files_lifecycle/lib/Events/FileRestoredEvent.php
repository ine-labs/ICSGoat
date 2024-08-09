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
 * Class FileRestoredEvent
 *
 * @package OCA\Files_Lifecycle\Audit
 */
/** @phan-suppress-next-line PhanDeprecatedClass */
class FileRestoredEvent extends Event {
	public const EVENT_NAME = 'lifecycle:file_restored';

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
	 * @param FileInfo $archivedFile
	 * @param FileInfo $originalFile
	 */
	public function __construct(FileInfo $archivedFile, FileInfo $originalFile) {
		$this->archivedFile = $archivedFile;
		$this->originalFile = $originalFile;
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
