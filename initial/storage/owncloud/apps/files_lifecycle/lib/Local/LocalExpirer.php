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

namespace OCA\Files_Lifecycle\Local;

use OC\Files\Node\Folder;
use OCA\Files_Lifecycle\Application;
use OCA\Files_Lifecycle\Events\FileExpiredEvent;
use OCA\Files_Lifecycle\ExpireQuery;
use OCA\Files_Lifecycle\IExpirer;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\StorageNotAvailableException;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\IUser;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class LocalExpirer
 *
 * @package OCA\Files_Lifecycle
 */
class LocalExpirer implements IExpirer {
	/**
	 * @var IRootFolder
	 */
	protected $rootFolder;
	/**
	 * @var EventDispatcherInterface
	 */
	protected $eventDispatcher;

	/**
	 * @var IDBConnection
	 */
	protected $connection;

	/**
	 * @var ExpireQuery
	 */
	protected $query;

	/**
	 * @var ILogger
	 */
	protected $logger;

	/**
	 * LocalExpirer constructor.
	 *
	 * @param IRootFolder $rootFolder
	 * @param IDBConnection $connection
	 * @param EventDispatcherInterface $eventDispatcher
	 * @param ExpireQuery $query
	 * @param ILogger $logger
	 */
	public function __construct(
		IRootFolder $rootFolder,
		IDBConnection $connection,
		EventDispatcherInterface $eventDispatcher,
		ExpireQuery $query,
		ILogger $logger
	) {
		$this->rootFolder = $rootFolder;
		$this->connection = $connection;
		$this->eventDispatcher = $eventDispatcher;
		$this->query = $query;
		$this->logger = $logger;
	}

	/**
	 * @param IUser $user
	 * @param \Closure $closure
	 * @param bool $dryRun
	 *
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @throws StorageNotAvailableException
	 * @throws InvalidPathException
	 *
	 * @return void
	 */
	public function expireForUser($user, $closure, $dryRun = false) {
		$this->setupFS($user);
		$count = 0;
		foreach ($this->query->getUserFilesForExpiry($user) as $fileRow) {
			if (!$dryRun) {
				$this->expireFile($fileRow['fileid'], $user);
			}
			$count++;
			$closure->call($this, 'Expiring file ' . $fileRow['fileid'] . ' at ' . $fileRow['path'] . ' for user ' . $user->getUID());
		}
	}
	/**
	 * Expire file from Archive
	 *
	 * @param int $fileId
	 * @param IUser $user
	 *
	 * @return void
	 *
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @throws InvalidPathException
	 */
	public function expireFile($fileId, IUser $user) {
		$file = $this->rootFolder->getById($fileId, true);
		if (\count($file) === 0) {
			$this->logger->warning(
				'File with ID ' . $fileId . ' scheduled for expiry by not found in cache.',
				[
					'app' => Application::APPID,
					'fileid' => $fileId
				]
			);
			return;
		}
		$path = $file[0]->getPath();
		$owner = $file[0]->getOwner();
		$parentPath = $file[0]->getParent()->getPath();
		try {
			$file[0]->delete();
		} catch (NotPermittedException $exception) {
			$this->logger->error(
				'Not permitted to expire ' . $path . ' from the archive',
				['app' => Application::APPID, 'fileid' => $file[0]->getId()]
			);
			return;
		}

		// Declare the file expired
		// TODO pass in original path into the expired event
		$this->eventDispatcher->dispatch(
			new FileExpiredEvent($fileId, $path, $owner),
			FileExpiredEvent::EVENT_NAME
		);
		$this->logger->info(
			$path . ' has been permanently removed from the archive.',
			[ 'app' => Application::APPID]
		);
		$this->removeFromDatabase($fileId);
		$this->deleteEmptyParentFolders($parentPath, $user->getUID());
	}

	/**
	 * Deletes folder if empty after expiring files
	 *
	 * @param string $path
	 * @param string $userId
	 *
	 * @return void
	 *
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 *
	 */
	public function deleteEmptyParentFolders($path, $userId) {
		$folderPath = $path;
		foreach (\array_reverse(\explode('/', $path)) as $branch) {
			if (\in_array($branch, ['archive', $userId, 'files', ''])) {
				continue;
			}
			/**
			 * @var Folder $folder
			 */
			$folder = $this->rootFolder->get($folderPath);
			if ($folder instanceof Folder) {
				$children = $folder->getDirectoryListing();
				if (\count($children) == 0) {
					$folder->delete();
				}
				$searchRegExp = '/\/' . $branch . '$/';
				$folderPath = \preg_replace($searchRegExp, '', $folderPath);
			}
		}
	}

	/**
	 * Remove entries from properties table
	 *
	 * @param int $fileId
	 *
	 * @return void
	 *
	 */
	public function removeFromDatabase($fileId) {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('properties')
			->where(
				$qb->expr()->eq(
					'fileid',
					$qb->expr()->literal($fileId)
				)
			)
			->execute();
	}

	/**
	 * Setup FileSystem for User
	 *
	 * @param IUser $user
	 *
	 * @return void
	 */
	private function setupFS(IUser $user) {
		static $fsUser = null;
		if ($fsUser === null || $fsUser !== $user) {
			\OC_Util::tearDownFS();
			\OC_Util::setupFS($user->getUID());
			$fsUser = $user;
		}
	}
}
