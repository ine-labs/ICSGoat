<?php
/**
 * ownCloud Workflow
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright 2016 ownCloud, Inc.
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
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTagObjectMapper;

/**
 * Class UserBasedRetention
 *
 * The background job executes the retention for a given user. It first iterates
 * over all files and folders (siblings first), then get's the tags for those
 * items and checks the respective retention periods. If a folder is not
 * deleted the children are added to the queue.
 *
 * @package OCA\Workflow\Retention
 */
class UserBasedRetention extends TimedJob {
	public const BATCH_SIZE = 25;

	/** @var IRootFolder */
	protected $rootFolder;

	/** @var IUserManager */
	protected $userManager;

	/** @var ISystemTagObjectMapper */
	protected $tagObjectMapper;

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

	/** @var IConfig */
	protected $config;

	/** @var RetentionChecker */
	protected $retentionChecker;

	/** @var array Period map: tagid => timestamp */
	protected $retentionPeriods;

	/** @var int */
	protected $folderRetentionPeriod = 0;

	/** @var Node|string[] */
	protected $queueRetention;

	/** @var Folder[] */
	protected $queueListing;

	/** @var Folder */
	protected $userFolder;

	/** @var array - fileid => tagid[] */
	protected $tags;

	/** @var IManager */
	protected $activityManager;

	/**
	 * BackgroundJob constructor.
	 *
	 * @param IRootFolder|null $rootFolder
	 * @param IUserManager|null $userManager
	 * @param ISystemTagObjectMapper|null $tagObjectMapper
	 * @param ITimeFactory|null $timeFactory
	 * @param IUserSession|null $userSession
	 * @param ILogger|null $logger
	 * @param IJobList|null $jobList
	 * @param IConfig|null $config
	 * @param Manager|null $retentionManager
	 * @param RetentionChecker|null $property
	 * @param IManager $activityManager
	 */
	public function __construct(
		IRootFolder $rootFolder = null,
		IUserManager $userManager = null,
		ISystemTagObjectMapper $tagObjectMapper = null,
		ITimeFactory $timeFactory = null,
		IUserSession $userSession = null,
		ILogger $logger = null,
		IJobList $jobList = null,
		IConfig $config = null,
		Manager $retentionManager = null,
		RetentionChecker $property = null,
		IManager $activityManager = null
	) {
		$this->rootFolder = $rootFolder;
		$this->userManager = $userManager;
		$this->tagObjectMapper = $tagObjectMapper;
		$this->timeFactory = $timeFactory;
		$this->userSession = $userSession;
		$this->logger = $logger;
		$this->jobList = $jobList;
		$this->config = $config;
		$this->retentionManager = $retentionManager;
		$this->retentionChecker = $property;
		$this->queueRetention = $this->queueListing = $this->tags = [];
		$this->activityManager = $activityManager;
		$this->setInterval(86400);

		if ($rootFolder === null ||
			$userManager === null ||
			$tagObjectMapper === null ||
			$timeFactory === null ||
			$userSession === null ||
			$logger === null ||
			$jobList === null ||
			$config === null ||
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
		$this->userManager = $container->getServer()->getUserManager();
		$this->tagObjectMapper = $container->getServer()->getSystemTagObjectMapper();
		$this->timeFactory = $container->query(ITimeFactory::class);
		$this->userSession = $container->getServer()->getUserSession();
		$this->logger = $container->getServer()->getLogger();
		$this->jobList = $container->getServer()->getJobList();
		$this->config = $container->getServer()->getConfig();
		$this->retentionManager = $container->query(Manager::class);
		$this->activityManager = $container->query(IManager::class);
	}

	/**
	 * @param array $arguments
	 */
	protected function run($arguments) {
		$uid = $arguments['user'];
		$user = $this->userManager->get($uid);

		if ($user instanceof IUser) {
			$this->getRetentionPeriods();
			$this->userFolder = $this->getUserFolder($user);
			$this->queueListing[] = $this->userFolder;
			$this->tags[$this->userFolder->getId()] = [];

			if ($this->config->getAppValue('workflow', 'folder_retention', false)) {
				try {
					$this->folderRetentionPeriod = $this->getRetentionTimestamp([
						'unit' => 'days',
						'numUnits' => (int) $this->config->getAppValue('workflow', 'folder_retention_period', 7),
					]);
				} catch (\OutOfBoundsException $e) {
					// Invalid folder retention
					$this->logger->error('Invalid folder retention period "{config}".', [
						'config' => $this->config->getAppValue('workflow', 'folder_retention_period', 7),
						'app' => 'workflow',
					]);
				}
			}

			$this->executeRetention($user);
		} else {
			$this->logger->warning('Removing retention for user "{user}" because the user could not be found.', [
				'user' => $uid,
				'app' => 'workflow',
			]);
			$this->jobList->remove('\OCA\Workflow\Retention\UserBasedRetention', ['user' => $uid]);
		}
	}

	/**
	 * @param IUser $user
	 * @return Folder
	 * @codeCoverageIgnore
	 */
	protected function getUserFolder(IUser $user) {
		Filesystem::init($user->getUID(), '/' . $user->getUID() . '/files');
		$this->userSession->setUser($user);
		return $this->rootFolder->getUserFolder($user->getUID());
	}

	/**
	 * Add all files of the user's root to the queue
	 * @param IUser $user
	 * @return bool False in case of an error, true otherwise
	 */
	protected function executeRetention(IUser $user) {
		$return = true;

		while (!empty($this->queueListing)) {
			/** @var Folder $folder */
			$folder = \array_pop($this->queueListing);

			try {
				$children = $folder->getDirectoryListing();
				if (!empty($children)) {
					foreach ($children as $child) {
						$this->addNodeToQueue($child, $folder);
					}
					$this->processQueue();
				} elseif ($folder->getId() !== $this->userFolder->getId()) {
					if ($this->folderRetentionPeriod > 0 && $this->retentionChecker->isRetentionOver($folder, $this->folderRetentionPeriod)) {
						try {
							unset($this->tags[$folder->getId()]);
							$folder->delete();
						} catch (\Exception $e) {
							// Make sure we can try to delete as many files as possible,
							// so we catch exceptions and continue.
							$return = false;
							$this->logger->logException($e, ['app' => 'workflow']);
						}
					}
				}
			} catch (NotFoundException $e) {
				$return = false;
				$this->logger->logException($e, [
					'message' => 'Exception while executing retention for "' . $user->getUID() . '"',
					'app' => 'workflow',
				]);
			}
		}

		return $return;
	}

	/**
	 * Add a Nod to the queue, if we are above the self::BATCH_SIZE, process the queue
	 * @param Node $node
	 * @param Node $parent
	 * @return bool True if the queue was processed
	 */
	protected function addNodeToQueue(Node $node, Node $parent) {
		try {
			if ($node->getStorage()->instanceOfStorage('OC\Files\Storage\Shared')) {
				// Shared storages are only checked for the real owner, because
				// otherwise we can't see the correct parent tags.
				return false;
			}

			$this->queueRetention[$node->getId()] = ['node' => $node, 'parent' => $parent->getId()];
		} catch (\Exception $e) {
			// Make sure we can try to delete as many files as possible,
			// so we catch exceptions and continue.
			$this->logger->logException($e, ['app' => 'workflow']);
			return false;
		}

		if (\sizeof($this->queueRetention) >= self::BATCH_SIZE) {
			$this->processQueue();
			return true;
		}
		return false;
	}

	/**
	 * Check the retention for all queued Nodes.
	 * If we did not delete a Folder, queue it for the directory list
	 */
	protected function processQueue() {
		if (empty($this->queueRetention)) {
			return;
		}

		$fileIds = \array_keys($this->queueRetention);
		$tags = $this->tagObjectMapper->getTagIdsForObjects($fileIds, 'files');

		foreach ($this->queueRetention as $fileId => $data) {
			/** @var Node $node */
			$node = $data['node'];
			$parentId = $data['parent'];

			if (empty($tags[$fileId])) {
				$fileTags = $this->tags[$parentId];
			} else {
				$fileTags = \array_unique(\array_merge($this->tags[$parentId], $tags[$fileId]));
			}

			if (!empty($fileTags)) {
				foreach ($fileTags as $tagId) {
					$retention = $this->getRetentionPeriodForTag($tagId);
					if ($retention !== false && $this->retentionChecker->isRetentionOver($node, $retention)) {
						try {
							$this->activityManager->setAgentAuthor(IEvent::AUTOMATION_AUTHOR);
							$node->delete();
							$this->activityManager->restoreAgentAuthor();
						} catch (\Exception $e) {
							// Make sure we can try to delete as many files as possible,
							// so we catch exceptions and continue.
							$this->logger->logException($e, ['app' => 'workflow']);
						}
						// Deleted, continue with the next queued Node
						continue 2;
					}
				}
			}

			// If we didn't delete the item, add folders the the listing queue
			if ($node instanceof Folder) {
				$this->queueListing[] = $node;
				// We might need the tags for children, so we store them.
				$this->tags[$fileId] = $fileTags;
			}
		}
		$this->queueRetention = [];
	}

	/**
	 * @param string $tagId
	 * @return int|false Timestamp for retention or false if no retention
	 */
	protected function getRetentionPeriodForTag($tagId) {
		if ($this->retentionPeriods === null) {
			$this->retentionPeriods = $this->getRetentionPeriods();
		}
		if (isset($this->retentionPeriods[$tagId])) {
			return $this->retentionPeriods[$tagId];
		}
		return false;
	}

	/**
	 * @return array Period map: tagid => timestamp
	 */
	protected function getRetentionPeriods() {
		if ($this->retentionPeriods !== null) {
			return $this->retentionPeriods;
		}

		$retentions = $this->retentionManager->getAll();

		$periods = [];
		foreach ($retentions as $conditions) {
			try {
				$periods[$conditions['tagId']] = $this->getRetentionTimestamp($conditions);
			} catch (\OutOfBoundsException $e) {
				$this->logger->error($e->getMessage(), [
					'tag' => $conditions['tagId'],
					'app' => 'workflow',
				]);
			}
		}

		$this->retentionPeriods = $periods;

		return $periods;
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
