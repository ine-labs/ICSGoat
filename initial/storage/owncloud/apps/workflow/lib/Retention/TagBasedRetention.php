<?php
/**
 * ownCloud Workflow
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright 2015 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Workflow\Retention;

use OC\BackgroundJob\TimedJob;
use OC\Files\Filesystem;
use OCA\Workflow\Retention\Exception\TagHasNoRetention;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;

/**
 * Class TagBasedRetention
 *
 * The background job executes the retention for a given tag. It first grabs 10
 * items that have the tag assigned. Then checks (depth-first) the children of
 * the tagged item, before continuing with the other tagged items. When a child
 * was already checked and is tagged itself, it will not be checked a second
 * time.
 *
 * @package OCA\Workflow\Retention
 */
class TagBasedRetention extends TimedJob {
	public const BATCH_SIZE = 10;

	/** @var IRootFolder */
	protected $rootFolder;

	/** @var ISystemTagObjectMapper */
	protected $tagObjectMapper;

	/** @var IMountProviderCollection */
	protected $mountProviderCollection;

	/** @var Manager */
	protected $retentionManager;

	/** @var ITimeFactory */
	protected $timeFactory;

	/** @var IUserSession */
	protected $userSession;

	/** @var ILogger */
	protected $logger;

	/** @var IJobList */
	protected $jobList;

	/** @var \OCP\Files\Folder[] */
	protected $userFolders;

	/** @var array nodeId => bool */
	protected $checkedNodes;

	/** @var string */
	protected $currentFilesystemUser;
	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var RetentionChecker|null
	 */
	private $retentionChecker;
	/**
	 * @var IManager
	 */
	private $activityManager;

	/**
	 * BackgroundJob constructor.
	 *
	 * @param IRootFolder|null $rootFolder
	 * @param ISystemTagObjectMapper|null $tagObjectMapper
	 * @param IMountProviderCollection|null $mountProviderCollection
	 * @param ITimeFactory|null $timeFactory
	 * @param IUserSession|null $userSession
	 * @param ILogger|null $logger
	 * @param IJobList|null $jobList
	 * @param Manager|null $retentionManager
	 * @param IConfig $config
	 * @param RetentionChecker|null $retentionChecker
	 * @param IManager $activityManager
	 */
	public function __construct(
		IRootFolder $rootFolder = null,
		ISystemTagObjectMapper $tagObjectMapper = null,
		IMountProviderCollection $mountProviderCollection = null,
		ITimeFactory $timeFactory = null,
		IUserSession $userSession = null,
		ILogger $logger = null,
		IJobList $jobList = null,
		Manager $retentionManager = null,
		IConfig $config = null,
		RetentionChecker $retentionChecker = null,
		Imanager $activityManager = null
	) {
		$this->rootFolder = $rootFolder;
		$this->tagObjectMapper = $tagObjectMapper;
		$this->mountProviderCollection = $mountProviderCollection;
		$this->timeFactory = $timeFactory;
		$this->userSession = $userSession;
		$this->logger = $logger;
		$this->jobList = $jobList;
		$this->retentionManager = $retentionManager;
		$this->config = $config;
		$this->retentionChecker = $retentionChecker;
		$this->activityManager = $activityManager;
		$this->setInterval(86400);

		if ($rootFolder === null ||
			$tagObjectMapper === null ||
			$mountProviderCollection === null ||
			$timeFactory === null ||
			$userSession === null ||
			$logger === null ||
			$jobList === null ||
			$retentionManager === null ||
			$activityManager === null) {
			$this->fixDependencies();
		}
	}

	/**
	 * Fill the members with the classes we need
	 */
	protected function fixDependencies() {
		$app = new App('workflow');
		$container = $app->getContainer();

		$this->rootFolder = $container->getServer()->getRootFolder();
		$this->tagObjectMapper = $container->getServer()->getSystemTagObjectMapper();
		$this->mountProviderCollection = $container->getServer()->getMountProviderCollection();
		$this->timeFactory = $container->query(ITimeFactory::class);
		$this->userSession = $container->getServer()->getUserSession();
		$this->logger = $container->getServer()->getLogger();
		$this->jobList = $container->getServer()->getJobList();
		$this->retentionManager = $container->query(Manager::class);
		$this->config = $container->getServer()->getConfig();
		$this->retentionChecker = $container->query(RetentionChecker::class);
		$this->activityManager = $container->query(IManager::class);
	}

	/**
	 * @param \OCP\IUser $user
	 * @return Folder
	 */
	protected function getUserFolder(IUser $user) {
		if (!isset($this->userFolders[$user->getUID()])) {
			$userFolder = $this->rootFolder->getUserFolder($user->getUID());
			$this->userFolders[$user->getUID()] = $userFolder;
		}

		return $this->userFolders[$user->getUID()];
	}

	/**
	 * @param array $arguments
	 */
	protected function run($arguments) {
		$this->executeRetention($arguments['tagId']);
	}

	/**
	 * @param string $tagId
	 * @return bool False in case of an error, true otherwise
	 */
	protected function executeRetention($tagId) {
		try {
			$retentionPeriod = $this->retentionManager->getRetention($tagId);
		} catch (TagHasNoRetention $e) {
			$this->logger->error('Tag "{tag}" has no retention period assigned.', [
				'tag' => $tagId,
				'app' => 'workflow',
			]);
			$this->jobList->remove('\OCA\Workflow\Retention\TagBasedRetention', ['tagId' => $tagId]);
			return false;
		}
		$mountCache = $this->mountProviderCollection->getMountCache();

		try {
			$retentionTimestamp = $this->getRetentionTimestamp($retentionPeriod);
		} catch (\OutOfBoundsException $e) {
			// Invalid retention values
			$this->logger->error($e->getMessage(), [
				'tag' => $retentionPeriod['tagId'],
				'app' => 'workflow',
			]);
			return false;
		}

		try {
			$fileIds = $this->tagObjectMapper->getObjectIdsForTags($retentionPeriod['tagId'], 'files', self::BATCH_SIZE);
		} catch (TagNotFoundException $e) {
			// Tag does not exist anymore, woops
			$this->logger->error('Tag "{tag}" used by retention workflow does not exist anymore', [
				'tag' => $retentionPeriod['tagId'],
				'app' => 'workflow',
			]);
			return false;
		}

		$this->checkedNodes = [];
		while (!empty($fileIds)) {
			$nodesToCheck = [];
			foreach ($fileIds as $fileId) {
				$mounts = $mountCache->getMountsForFileId((int)$fileId);
				if (!empty($mounts)) {
					$mount = \array_shift($mounts);
					$user = $mount->getUser();

					if ($user instanceof IUser) {
						$this->initFilesystemForUser($user);
						$userFolder = $this->getUserFolder($user);
						$nodes = $userFolder->getById((int)$fileId);
						if (!empty($nodes)) {
							$nodesToCheck[] = [
								'node' => \array_shift($nodes),
								'owner' => $user
							];
							$this->checkedNodes[$fileId] = false;
						}
					}
				}
			}

			$this->checkItemsForRetention($retentionTimestamp, $nodesToCheck);

			try {
				$fileIds = $this->tagObjectMapper->getObjectIdsForTags($retentionPeriod['tagId'], 'files', self::BATCH_SIZE, \array_pop($fileIds));
			} catch (TagNotFoundException $e) {
				// Tag does not exist anymore, woops
				$this->logger->error('Tag "{tag}" used by retention workflow does not exist anymore', [
					'tag' => $retentionPeriod['tagId'],
					'app' => 'workflow',
				]);
				return false;
			}
		}

		return true;
	}

	/**
	 * @param int $retentionTimestamp
	 * @param array[] $nodesToCheck Each item is [node: \OCP\Files\Node, owner: \OCP\IUser]
	 */
	protected function checkItemsForRetention($retentionTimestamp, array $nodesToCheck) {
		while (!empty($nodesToCheck)) {
			/** @var Node $node */
			$toCheck = \array_pop($nodesToCheck);
			$node = $toCheck['node'];
			$owner = $toCheck['owner'];

			$this->initFilesystemForUser($owner);
			try {
				$fileId = $node->getId();
			} catch (\Exception $e) {
				// Make sure we can try to delete as many files as possible,
				// so we catch exceptions and continue.
				$this->logger->logException($e, ['app' => 'workflow']);
				continue;
			}

			if (!empty($this->checkedNodes[$fileId])) {
				continue;
			}

			$this->checkedNodes[$fileId] = true;
			if ($this->retentionChecker->isRetentionOver($node, $retentionTimestamp)) {
				$this->activityManager->setAgentAuthor(IEvent::AUTOMATION_AUTHOR);
				try {
					$node->delete();
				} catch (\Exception $e) {
					// Make sure we can try to delete as many files as possible,
					// so we catch exceptions and continue.
					$this->logger->logException($e, ['app' => 'workflow']);
					continue;
				} finally {
					$this->activityManager->restoreAgentAuthor();
				}
			} elseif ($node instanceof Folder) {
				/** @var Folder $node */
				try {
					$children = $node->getDirectoryListing();
				} catch (\Exception $e) {
					// Make sure we can try to delete as many files as possible,
					// so we catch exceptions and continue.
					$this->logger->logException($e, ['app' => 'workflow']);
					continue;
				}

				foreach ($children as $child) {
					try {
						$childId = $child->getId();
						if (!isset($this->checkedNodes[$childId])) {
							$nodesToCheck[] = [
								'node' => $child,
								'owner' => $child->getOwner(),
							];
							$this->checkedNodes[$childId] = false;
						}
					} catch (\Exception $e) {
						// Make sure we can try to delete as many files as possible,
						// so we catch exceptions and continue.
						$this->logger->logException($e, ['app' => 'workflow']);
						continue;
					}
				}
			}
		}
	}

	/**
	 * @param IUser $user
	 */
	protected function initFilesystemForUser(IUser $user) {
		if ($this->currentFilesystemUser !== $user->getUID()) {
			if ($this->currentFilesystemUser !== '') {
				Filesystem::tearDown();
			}
			Filesystem::init($user->getUID(), '/' . $user->getUID() . '/files');
			$this->userSession->setUser($user);
			$this->currentFilesystemUser = $user->getUID();
		}
	}

	/**
	 * @param array $conditions
	 * @return int
	 */
	protected function getRetentionTimestamp(array $conditions) {
		if (!\is_int($conditions['numUnits']) || $conditions['numUnits'] <= 0) {
			throw new \OutOfBoundsException('Invalid retention period for tag "{tag}"', 1);
		}

		switch ($conditions['unit']) {
			case 'years':
				$unit = 'Y';
				break;
			case 'months':
				$unit = 'M';
				break;
			case 'weeks':
				$unit = 'W';
				break;
			case 'days':
				$unit = 'D';
				break;

			default:
				throw new \OutOfBoundsException('Invalid retention period unit for tag "{tag}"', 2);
		}

		$retentionDate = new \DateTime();
		$retentionDate->setTimestamp($this->timeFactory->getTime());
		$retentionDate->sub(new \DateInterval('P' . $conditions['numUnits'] . $unit));

		return $retentionDate->getTimestamp();
	}
}
