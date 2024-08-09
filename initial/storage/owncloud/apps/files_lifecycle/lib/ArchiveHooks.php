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

namespace OCA\Files_Lifecycle;

use OC\Files\Cache\Storage;
use OC\Files\Node\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class ArchiveHooks
 *
 * @package OCA\Files_Lifecycle
 */
class ArchiveHooks {
	/**
	 * @var IRootFolder
	 */
	protected $rootFolder;

	/**
	 * ArchiveHooks constructor.
	 *
	 * @param IRootFolder $rootFolder
	 */
	public function __construct(IRootFolder $rootFolder) {
		$this->rootFolder = $rootFolder;
	}

	/**
	 * @param GenericEvent $event
	 *
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 *
	 * @return void
	 */
	public function deleteUser(GenericEvent $event) {
		$userId = $event->getArguments()['uid'];
		$archivePath = $userId . '/archive';
		if (\is_dir($archivePath)) {
			$archiveNode = $this->rootFolder->get($archivePath);
		}
		if (isset($archiveNode) && $archiveNode instanceof Folder) {
			$archiveNode->delete();
		}
		Storage::remove('archive::' . $userId);
	}
}
