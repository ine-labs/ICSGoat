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

use Doctrine\DBAL\DBALException;
use OCA\Files_Lifecycle\Application;
use OCA\Files_Lifecycle\ArchiveQuery;
use OCA\Files_Lifecycle\Dav\ArchivePlugin;
use OCA\Files_Lifecycle\Events\FileArchivedEvent;
use OCA\Files_Lifecycle\IArchiver;
use OCP\Files\File;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class LocalArchiver
 *
 * @package OCA\Files_Lifecycle
 */
class LocalArchiver implements IArchiver {

	/**
	 * @var IRootFolder
	 */
	protected $rootFolder;

	/**
	 * @var IUserManager
	 */
	protected $userManager;

	/**
	 * @var ArchiveQuery $query
	 */
	protected $query;

	/**
	 * @var EventDispatcherInterface
	 */
	protected $eventDispatcher;

	/**
	 * @var IDBConnection $connection
	 */
	protected $connection;

	/**
	 * @var ILogger
	 */
	protected $logger;

	/**
	 * LocalArchiver constructor.
	 *
	 * @param IRootFolder $rootFolder
	 * @param IUserManager $userManager
	 * @param ArchiveQuery $query
	 * @param IDBConnection $connection
	 * @param EventDispatcherInterface $eventDispatcher
	 * @param ILogger $logger
	 */
	public function __construct(
		IRootFolder $rootFolder,
		IUserManager $userManager,
		ArchiveQuery $query,
		IDBConnection $connection,
		EventDispatcherInterface $eventDispatcher,
		ILogger $logger
	) {
		$this->rootFolder = $rootFolder;
		$this->userManager = $userManager;
		$this->query = $query;
		$this->connection = $connection;
		$this->eventDispatcher = $eventDispatcher;
		$this->logger = $logger;
	}

	/**
	 * Archive all Files for a given User
	 * Only execute if not in dry run mode
	 *
	 * @param IUser $user
	 * @param \Closure $closure
	 * @param bool $dryRun
	 *
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 * @throws DBALException
	 * @throws InvalidPathException
	 *
	 * @return void
	 */
	public function archiveForUser($user, $closure, $dryRun = false) {
		if (!$this->userManager->userExists($user->getUID())) {
			return;
		}
		$this->setupFS($user);
		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		foreach ($this->query->getFilesForArchive($user) as $fileRow) {
			$node = $userFolder->getById($fileRow['fileid'], true)[0];
			if (!$node instanceof File) {
				continue;
			}
			$file = $node;
			$fileToArchive = $file;
			if (!$dryRun) {
				$this->moveFile2Archive($fileToArchive, $user);
			}
			$closure->call($this, 'Moving file ' . $file->getPath() . ' for user ' . $user->getUID() . ' to archive');
		}
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

	/**
	 * Move File from User Folder to Archive
	 *
	 * @param File $file
	 * @param IUser $user
	 *
	 * @return void
	 *
	 * @throws \OCP\Files\NotPermittedException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \OCP\Files\InvalidPathException
	 * @throws \OCP\Files\NotFoundException
	 */
	public function moveFile2Archive($file, $user) {
		$originalFile = clone $file;
		// ensure the right fileinfo is loaded in the "originalFile"
		// otherwise, the fileinfo for the "originalFile" will be lost
		// after the move action
		$originalFile->getId();
		$archivePath = $this->getArchivePath($file, $user);
		$this->createTargetFolderIfNotExists($file, $user->getUID());
		try {
			$file->move($archivePath);
		} catch (NotPermittedException $notPermittedException) {
			$this->logger->error(
				'Not permitted to move file ' . $originalFile->getPath() . ' to archive',
				['app' => Application::APPID, 'fileid' => $file->getId()]
			);
			return;
		} catch (NotFoundException $notFoundException) {
			$this->logger->error(
				'File at ' . $originalFile->getPath() . ' was scheduled for archive but not found',
				['app' => Application::APPID, 'fileid' => $file->getId()]
			);
			return;
		}
		// Set the archive time property
		$this->setArchivedTime($file->getId());
		// Declare the file archived
		$this->eventDispatcher->dispatch(
			new FileArchivedEvent($originalFile, $file),
			FileArchivedEvent::EVENT_NAME
		);
		$this->logger->info(
			$originalFile->getPath() . ' has been archived.',
			[ 'app' => Application::APPID]
		);
	}

	/**
	 * Generate Archive Path for file
	 *
	 * @param File $file
	 * @param IUser $user
	 *
	 * @return string
	 */
	public function getArchivePath($file, $user) {
		$archivePath = '/' . $user->getUID()
			. '/archive/' . $file->getInternalPath();
		return $archivePath;
	}

	/**
	 * Checks the target path and creates parent folders if necessary
	 *
	 * @param File $file
	 * @param string $userId
	 *
	 * @return bool|void
	 *
	 * @throws NotPermittedException
	 *
	 */
	public function createTargetFolderIfNotExists(File $file, $userId) {
		$archiveParent = $this->getArchiveParentPath($file, $userId);
		$folder = $userId . '/archive';
		foreach (\explode('/', $archiveParent) as $branch) {
			if (\in_array($branch, ['archive', $userId, ''])) {
				continue;
			}
			// Todo: Error Handling, file locked
			$folder = $folder . '/' . $branch;
			$this->rootFolder->newFolder($folder);
		}
	}

	/**
	 * Generate Archive Path for parent Folder
	 *
	 * @param File $file
	 * @param string $userId
	 *
	 * @return string
	 */
	public function getArchiveParentPath($file, $userId) {
		$archiveParentPath = '/' . $userId . '/archive/'
			. $file->getParent()->getInternalPath();
		return $archiveParentPath;
	}

	/**
	 * Set archived-time property
	 *
	 * @param int $fileId
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 *
	 * @return void
	 */
	public function setArchivedTime($fileId) {
		$now = new \DateTime;
		$this->connection
			->upsert(
				'*PREFIX*properties',
				[
					'propertyname' => ArchivePlugin::ARCHIVED_TIME,
					'propertyvalue' => $now->format(\DateTime::ATOM),
					'fileid' => $fileId,
				],
				[
					'fileid', 'propertyname'
				]
			);
	}
}
