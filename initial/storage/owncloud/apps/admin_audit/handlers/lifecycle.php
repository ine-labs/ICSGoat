<?php
/**
 *
 * @author Tom Needham <tom@owncloud.com>
 * @copyright 2018 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see
 * <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Admin_Audit\Handlers;
use Symfony\Component\EventDispatcher\Event;

/**
 * This code reaches into files_lifecycle at run-time.
 * During phan code analysis it will think that the lifecycle classes are not found.
 * Suppress those phan analysis messages.
 *
 * @phan-file-suppress PhanUndeclaredClassMethod
 */

/**
 * Class Lifecycle
 *
 * @package OCA\Admin_Audit\Handlers
 */
class Lifecycle extends Base {

	/**
	 * Audit entry for when a single file is moved to the archive
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function handleFileArchived(Event $event) {
		if (\get_class($event) !== 'OCA\Files_Lifecycle\Events\FileArchivedEvent') {
			return;
		}
		/**
		 * @var \OCA\Files_Lifecycle\Events\FileArchivedEvent $event
		 */
		self::getLogger()->log(
			'The file at {origin} was moved to the archive at {archived}',
			[
				'origin' => $event->getOriginalFileInfo()->getPath(),
				'archived' => $event->getArchivedFileInfo()->getPath()
			],
			[
				'action' => 'lifecycle_archived',
				'user' => $event->getOriginalFileInfo()->getOwner()->getUID(),
				'fileId' => (int) $event->getOriginalFileInfo()->getId(),
				'owner' => $event->getOriginalFileInfo()->getOwner()->getUID(),
				'path' => $event->getOriginalFileInfo()->getPath()
			]
		);
	}

	/**
	 * @param Event $event
	 *
	 * @return void
	 */
	public function handleFileRestored(Event $event) {
		if (\get_class($event) !== 'OCA\Files_Lifecycle\Events\FileRestoredEvent') {
			return;
		}
		/**
		 * @var \OCA\Files_Lifecycle\Events\FileRestoredEvent $event
		 */
		self::getLogger()->log(
			'A file was restored from the archive at {archived} to {path}',
			[
				'archived' => $event->getArchivedFileInfo()->getPath(),
				'path' => $event->getOriginalFileInfo()->getPath()
			],
			[
				'action' => 'lifecycle_restored',
				'user' => $event->getOriginalFileInfo()->getOwner()->getUID(),
				'fileId' => (int) $event->getOriginalFileInfo()->getId(),
				'owner' => $event->getOriginalFileInfo()->getOwner()->getUID(),
				'path' => $event->getOriginalFileInfo()->getPath()
			]
		);
	}

	/**
	 * @param Event $event
	 *
	 * @return void
	 */
	public function handleFileExpired(Event $event) {
		if (\get_class($event) !== 'OCA\Files_Lifecycle\Events\FileExpiredEvent') {
			return;
		}
		/**
		 * @var \OCA\Files_Lifecycle\Events\FileExpiredEvent $event
		 */
		self::getLogger()->log(
			'A file at {archived} was expired from the archive',
			[
				'archived' => $event->getPath()
			],
			[
				'action' => 'lifecycle_expired',
				'fileId' => (int) $event->getFileId(),
				'owner' => $event->getOwner()->getUID(),
				// TODO we need to store the original owner on archive so we can use it here
			]
		);
	}
}
