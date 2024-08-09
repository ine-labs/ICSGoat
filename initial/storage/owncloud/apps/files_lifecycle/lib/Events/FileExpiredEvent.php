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

use OCP\IUser;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class FileExpiredEvent
 *
 * @package OCA\Files_Lifecycle\Audit
 */
/** @phan-suppress-next-line PhanDeprecatedClass */
class FileExpiredEvent extends Event {
	public const EVENT_NAME = 'lifecycle:file_expired';

	/**
	 * @var int
	 */
	protected $fileId;
	/**
	 * @var string
	 */
	protected $path;
	/**
	 * @var IUser
	 */
	protected $owner;

	/**
	 * FileArchivedEvent constructor.
	 *
	 * @param int $fileId
	 * @param string $path
	 * @param IUser $owner
	 */
	public function __construct(int $fileId, $path, IUser $owner) {
		$this->fileId = $fileId;
		$this->path = $path;
		$this->owner = $owner;
	}

	/**
	 * @return int
	 */
	public function getFileId() {
		return $this->fileId;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @return IUser
	 */
	public function getOwner() {
		return $this->owner;
	}
}
