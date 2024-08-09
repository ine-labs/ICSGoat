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
use OC\Files\Node\File;
use OC\Files\Node\Folder;
use OCA\Files_Lifecycle\Application;
use OCA\Files_Lifecycle\Dav\ArchivePlugin;
use OCA\Files_Lifecycle\Events\FileRestoredEvent;
use OCA\Files_Lifecycle\IRestorer;
use OCA\Files_Lifecycle\RecursiveNodeIterator;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class LocalRestorer
 *
 * @package OCA\Files_Lifecycle
 */
class LocalRestorer implements IRestorer {
	/**
	 * @var IRootFolder
	 */
	protected $rootFolder;

	/**
	 * @var Filesystem
	 */
	protected $filesystem;
	/**
	 * @var EventDispatcherInterface
	 */
	protected $eventDispatcher;

	/**
	 * @var IDBConnection
	 */
	protected $connection;
	/**
	 * @var IUserManager
	 */
	protected $userManager;
	/**
	 * @var ILogger
	 */
	protected $logger;

	/**
	 * LocalRestorer constructor.
	 *
	 * @param IRootFolder $rootFolder
	 * @param IDBConnection $connection
	 * @param EventDispatcherInterface $eventDispatcher
	 * @param IUserManager $userManager
	 * @param ILogger $logger
	 */
	public function __construct(
		IRootFolder $rootFolder,
		IDBConnection $connection,
		EventDispatcherInterface $eventDispatcher,
		IUserManager $userManager,
		ILogger $logger
	) {
		$this->rootFolder = $rootFolder;
		$this->connection = $connection;
		$this->eventDispatcher = $eventDispatcher;
		$this->userManager = $userManager;
		$this->logger = $logger;
	}

	/**
	 * Restore a path from Archive
	 *
	 * It could be a path to a file or a folder
	 * Don't care about SetupFS
	 *
	 * @see restorePathFromArchiveCli
	 *
	 * @param string $path
	 * @param IUser $user
	 *
	 * @return string|null to users home folder where file/folder restored, null
	 * if nothing was restored
	 *
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 * @throws DBALException
	 * @throws InvalidPathException
	 *
	 */
	public function restorePathFromArchive($path, IUser $user) {
		$this->setupFS($user);
		$pathNode = $this->rootFolder->get($path);

		if ($pathNode instanceof Folder) {
			$topFolder = null;
			foreach (RecursiveNodeIterator::create($pathNode) as $node) {
				$newPath = $this->restoreFileFromArchive($node, $user);
				// Save the return path for the top level folder
				if ($topFolder === null) {
					$topFolder = $this->getRestorePath($path);
				}
			}
			return $topFolder;
		}
		return $this->restoreFileFromArchive($pathNode, $user);
	}

	/**
	 * @param Node $node
	 * @param IUser $user
	 *
	 * @return string|null destination path in users home folder, null
	 * if nothing was restored
	 *
	 * @throws DBALException
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function restoreFileFromArchive(Node $node, IUser $user) {
		$archivedFile = clone $node;
		if ($node instanceof File) {
			$this->createTargetFolderIfNotExists($node, $user->getUID());
			/**
			 * @var Folder $parent
			 */
			$parent = $node->getParent();
			$parentTargetPath = $this->getRestorePath($parent->getPath());
			$parentTarget = $this->rootFolder->get($parentTargetPath);
			if ($parentTarget instanceof Folder) {
				$uniqueTargetPath = $parentTargetPath . '/' . $parentTarget
					->getNonExistingName($node->getName());
				$node->move($uniqueTargetPath);
				$this->setRestoredTime($node->getId());
				$this->deleteEmptyParentFolders($parent->getPath(), $user->getUID());
				// Declare the file restored
				$this->eventDispatcher->dispatch(
					new FileRestoredEvent($archivedFile, $node),
					FileRestoredEvent::EVENT_NAME
				);
				$this->logger->info(
					$node->getPath() . ' has been restored.',
					[ 'app' => Application::APPID]
				);
				return $uniqueTargetPath;
			}
		}
		return null;
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
		$Parent = $this->getRestorePath($file->getParent()->getPath());
		$folder = '/' . $userId . '/files';
		foreach (\explode('/', $Parent) as $branch) {
			if (\in_array($branch, [$userId, 'files', ''])) {
				continue;
			}
			// Todo: Error Handling, file locked
			$folder = $folder . '/' . $branch;
			$this->rootFolder->newFolder($folder);
		}
	}

	/**
	 * Creates the original path from the archived path
	 *
	 * @param string $path
	 *
	 * @return mixed
	 */
	public function getRestorePath($path) {
		$targetPath = \str_replace('archive/', '', $path);
		return $targetPath;
	}

	/**
	 * Set restored-time property
	 *
	 * @param int $fileId
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 *
	 * @return void
	 */
	public function setRestoredTime($fileId) {
		$now = new \DateTime;
		$this->connection
			->upsert(
				'*PREFIX*properties',
				[
					'propertyname' => ArchivePlugin::RESTORED_TIME,
					'propertyvalue' => $now->format(\DateTime::ATOM),
					'fileid' => $fileId,
				],
				[
					'fileid', 'propertyname'
				]
			);
	}

	/**
	 * Deletes folder if empty after restoring files
	 *
	 * @param string $path
	 * @param string $userId
	 *
	 * @return void
	 *
	 * @throws NotFoundException
	 * @throws NotPermittedException
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
				// Todo: Error Handling, file locked
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
	 * Restores all known archived files
	 *
	 * @param callable $progressCallback
	 *
	 * @return void
	 */
	public function restoreAllFiles(callable $progressCallback) {
		// Loop through users
		$numSeenUsers = $this->userManager->countSeenUsers();
		$count = 1;
		$this->userManager->callForSeenUsers(
			function (IUser $user) use ($progressCallback, $numSeenUsers, &$count) {
				// Inform of progress
				\call_user_func($progressCallback, $numSeenUsers, $count, $user);
				try {
					$this->restoreAllFilesForUser($user);
				} catch (\Exception $e) {
					// Skip to the next user, but log the error
					$this->logger->logException($e, ['app' => Application::APPID]);
				}
				$count++;
			}
		);
	}
	
	/**
	 * Given a single user, restore all their files in the archive
	 *
	 * @param IUser $user
	 *
	 * @return void
	 *
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 * @throws DBALException
	 * @throws InvalidPathException
	 */
	protected function restoreAllFilesForUser(IUser $user) {
		// Find all files in archive for this user
		$this->restorePathFromArchive($user->getUID() . '/archive/files', $user);
	}
}
