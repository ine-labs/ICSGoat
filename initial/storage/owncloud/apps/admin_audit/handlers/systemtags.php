<?php
/**
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

namespace OCA\Admin_Audit\Handlers;

use OCA\Admin_Audit\Logger;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\IRootFolder;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ManagerEvent;
use OCP\SystemTag\MapperEvent;
use OCP\SystemTag\TagNotFoundException;

class SystemTags {
	/** @var \OCP\SystemTag\ISystemTagManager */
	protected $tagManager;
	/** @var \OCP\Files\Config\IMountProviderCollection */
	protected $mountCollection;
	/** @var \OCP\Files\IRootFolder */
	protected $rootFolder;
	/** @var Logger */
	protected $logger;

	/**
	 * Listener constructor.
	 *
	 * @param ISystemTagManager $tagManager
	 * @param IMountProviderCollection $mountCollection
	 * @param IRootFolder $rootFolder
	 * @param Logger $logger
	 */
	public function __construct(
		ISystemTagManager $tagManager,
		IMountProviderCollection $mountCollection,
		IRootFolder $rootFolder,
		Logger $logger
	) {
		$this->tagManager = $tagManager;
		$this->mountCollection = $mountCollection;
		$this->rootFolder = $rootFolder;
		$this->logger = $logger;
	}

	/**
	 * @param ManagerEvent $event
	 */
	public function managerEvent(ManagerEvent $event) {
		if ($event->getEvent() === ManagerEvent::EVENT_CREATE) {
			list($tagName, $tagPermission) = \json_decode($this->logger->prepareTagAsParameter($event->getTag()), true);
			$this->logger->log('{actor} created system tag "{tagName}" "{tagPermission}"', [
				'tagName' => $tagName,
				'tagPermission' => $tagPermission,
			], [
				'action' => 'tag_created',
				'tagName' => $tagName,
				'tagPermission' => $tagPermission,
			]);
		} elseif ($event->getEvent() === ManagerEvent::EVENT_UPDATE) {
			list($tagName, $tagPermission) = \json_decode($this->logger->prepareTagAsParameter($event->getTag()), true);
			list($oldTag, $oldTagPermission) = \json_decode($this->logger->prepareTagAsParameter($event->getTagBefore()), true);
			$this->logger->log('{actor} updated system tag "{beforeTag}" "{beforeTagPermission}" to "{afterTag}" "{afterTagPermission}"', [
				'afterTag' => $tagName,
				'afterTagPermission' => $tagPermission,
				'beforeTag' => $oldTag,
				'beforeTagPermission' => $oldTagPermission
			], [
				'action' => 'tag_updated',
				'tagName' => $tagName,
				'tagPermission' => $tagPermission,
				'oldTag' => $oldTag,
				'oldTagPermission' => $oldTagPermission,
			]);
		} elseif ($event->getEvent() === ManagerEvent::EVENT_DELETE) {
			list($tagName, $tagPermission) = \json_decode($this->logger->prepareTagAsParameter($event->getTag()), true);
			$this->logger->log('{actor} deleted system tag "{tagName}" "{tagPermission}"', [
				'tagName' => $tagName,
				'tagPermission' => $tagPermission,
			], [
				'action' => 'tag_deleted',
				'tagName' => $tagName,
				'tagPermission' => $tagPermission,
			]);
		}
	}

	/**
	 * @param MapperEvent $event
	 */
	public function mapperEvent(MapperEvent $event) {
		$tagIds = $event->getTags();
		if ($event->getObjectType() !== 'files' ||empty($tagIds)
			|| !\in_array($event->getEvent(), [MapperEvent::EVENT_ASSIGN, MapperEvent::EVENT_UNASSIGN])) {
			// System tags not for files, no tags, not (un-)assigning
			return;
		}

		try {
			$tags = $this->tagManager->getTagsByIds($tagIds);
		} catch (TagNotFoundException $e) {
			// User assigned/unassigned a non-existing tag, ignore...
			return;
		}

		if (empty($tags)) {
			return;
		}

		// Get all mount point owners
		$cache = $this->mountCollection->getMountCache();
		/*
		 * getObjectId claims to return string.
		 * getMountsForFileId claims to accept int
		 * suppress the message about this.
		 */
		/** @phpstan-ignore-next-line @phan-suppress-next-line PhanTypeMismatchArgument */
		$mounts = $cache->getMountsForFileId($event->getObjectId());
		if (!empty($mounts)) {
			/** @var \OCP\Files\Config\ICachedMountInfo $mount */
			$mount = \array_shift($mounts);

			$owner = $mount->getUser()->getUID();
			$ownerFolder = $this->rootFolder->getUserFolder($owner);
			/*
			 * getObjectId claims to return string.
			 * getById claims to accept int
			 * suppress the message about this.
			 */
			/** @phpstan-ignore-next-line @phan-suppress-next-line PhanTypeMismatchArgument */
			$nodes = $ownerFolder->getById($event->getObjectId(), true);

			if (!empty($nodes)) {
				/** @var \OCP\Files\Node $node */
				$node = $nodes[0];

				foreach ($tags as $tag) {
					if ($event->getEvent() === MapperEvent::EVENT_ASSIGN) {
						$logMessage = '{actor} assigned system tag "{tagName}" "{tagPermission}" to "{path}"{tags}';
						/*
						 * $event->getTags() says it returns int[] into $tagIds
						 * but getTagStringForFile takes string[]
						 * suppress the message about this.
						 */
						/* @phpstan-ignore-next-line @phan-suppress-next-line PhanTypeMismatchArgument */
						$tags = \json_decode($this->logger->getTagStringForFile($event->getObjectId(), [], $tagIds), true);
						$action = 'tag_assigned';
					} else {
						$logMessage = '{actor} unassigned system tag "{tagName}" "{tagPermission}" from "{path}"{tags}';
						/*
						 * $event->getTags() says it returns int[] into $tagIds
						 * but getTagStringForFile takes string[]
						 * suppress the message about this.
						 */
						/* @phpstan-ignore-next-line @phan-suppress-next-line PhanTypeMismatchArgument */
						$tags = \json_decode($this->logger->getTagStringForFile($event->getObjectId(), $tagIds), true);
						$action = 'tag_unassigned';
					}
					if ($tags === null) {
						$tags = '';
					}
					list($tagName, $tagPermission) = \json_decode($this->logger->prepareTagAsParameter($tag), true);
					$this->logger->log($logMessage, [
						'actor' => $this->getActor(),
						'path' => $node->getPath(),
						'tagName' => $tagName,
						'tagPermission' => $tagPermission,
						'tags' => $tags,
					], [
						'action' => $action,
						'actor' => $this->getActor(),
						'path' => $node->getPath(),
						'fileId' => $node->getId(),
						'tagName' => $tagName,
						'tagPermission' => $tagPermission,
					]);
				}
			}
		}
	}

	/**
	 * @return string
	 */
	protected function getActor() {
		$exception = new \Exception();

		foreach ($exception->getTrace() as $trace) {
			if (isset($trace['class']) && $trace['class'] === 'OCA\Workflow\AutoTagging\Tagger' &&
				isset($trace['function']) && $trace['function'] === 'executeRules'
			) {
				return 'Workflow Autotagger';
			}
		}

		return $this->logger->getUserOrIp();
	}
}
