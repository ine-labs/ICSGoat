<?php
/**
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license OCL
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\FilesClassifier;

use OC\Files\Filesystem;
use OC\Files\Node\Folder;
use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use OCA\FilesClassifier\Model\Rule;
use OCP\Files\File;
use OCP\Files\ForbiddenException;
use OCP\Files\Node;
use OCP\Files\NotPermittedException;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Lock\ILockingProvider;
use OCP\Share;
use OCP\Share\Exceptions\GenericShareException;
use OCP\SystemTag\ISystemTagManager;
use OCP\ILogger;
use OCP\IL10N;
use ZendXml\Security;

class Handler {

	/** @var IUser  */
	private $user;
	/** @var ISystemTagManager */
	private $tagManager;
	/** @var Persistence  */
	private $persistence;
	/** @var IL10N */
	private $l10n;
	/** @var ILogger */
	private $logger;

	private $propertiesCache = [];

	/**
	 * @var Rule[]|null
	 */
	private $classificationRules;

	public function __construct(IUserSession $userSession, ISystemTagManager $manager, Persistence $persistence, IL10N $l10n, ILogger $logger) {
		$this->user = $userSession->getUser();
		$this->tagManager = $manager;
		$this->persistence = $persistence;
		$this->l10n = $l10n;
		$this->logger = $logger;
	}

	/**
	 * Prevents creation of classified file after it is uploaded
	 *
	 * @param $params
	 *
	 * @throws ForbiddenException
	 * @throws \OCP\Files\InvalidPathException
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Lock\LockedException
	 */
	public function postWrite($params) {
		if (!isset($params['path']) || $params['path'] === null) {
			return;
		}
		$file = \OC::$server->getRootFolder()->get($params['path']);
		if ($file instanceof File) {
			$this->classify($file);
		}
	}

	/**
	 * @param $path
	 * @return Node
	 * @throws \OCP\Files\NotFoundException
	 */
	private function getNodeByPath($path) {
		if ($this->user === null) {
			// public share ... get the node the old way ...
			$view = Filesystem::getView();
			$absolutePath = $view->getAbsolutePath($path);
			$node = \OC::$server->getRootFolder()->get($absolutePath);
		} else {
			$node = \OC::$server->getUserFolder($this->user->getUID())->get($path);
		}
		return $node;
	}

	/**
	 * @param int $fileId
	 * @return Node
	 */
	private function getNodeById($fileId) {
		if ($this->user === null) {
			// public share ...
			$nodes = \OC::$server->getRootFolder()->getById($fileId, true);
		} else {
			$nodes = \OC::$server->getUserFolder($this->user->getUID())->getById($fileId, true);
		}
		if (!empty($nodes)) {
			return $nodes[0]; // we only need one node, path does not matter
		}
		return null;
	}

	/**
	 * Given an arbitrary node this method returns all parent
	 * and all child node Ids recursively.
	 *
	 * Set parentOnly to only get parent nodeIds
	 *
	 * @param Node $node
	 * @param bool $parentOnly
	 * @return array
	 * @throws \OCP\Files\InvalidPathException
	 * @throws \OCP\Files\NotFoundException
	 */
	private function getAllNodeIds(Node $node, $parentOnly = false) {
		$home = \OC::$server->getUserFolder();
		$nodes = [];

		if ($home !== null) {
			if (!$parentOnly && $node instanceof Folder) {
				foreach ($this->getRecursiveIterator($node) as $n) {
					$nodes[] = $n->getId();
				}
			}

			// Check parents
			do {
				$nodes[] = $node->getId();
				$node = $node->getParent();
				$node->getId();
			} while ($node->getId() !== $home->getId() && $node->getPath() !== '/');
		}

		return \array_unique($nodes);
	}

	/**
	 * Given an arbitrary fileId this method returns all parent
	 * and all child classification tags recursively.
	 *
	 * Set parentOnly to only get parent tags
	 *
	 * @param $fileId
	 * @param bool $parentOnly
	 * @return ClassificationTag[]
	 * @throws \OCP\Files\InvalidPathException
	 * @throws \OCP\Files\NotFoundException
	 */
	private function getTags($fileId, $parentOnly = false) {
		$node = $this->getNodeById($fileId);
		$allNodes = $this->getAllNodeIds($node, $parentOnly);

		$objectsTags = \OC::$server->getSystemTagObjectMapper()
			->getTagIdsForObjects($allNodes, 'files');

		$rules = $this->getClassificationRules();
		/** @var ClassificationTag[] $tags */
		$tags = [];
		foreach ($objectsTags as $fileId => $objectTags) {
			foreach ($objectTags as $tagId) {
				if (isset($rules[$tagId])) {
					if (!isset($tags[$tagId])) {
						$tags[$tagId] = new ClassificationTag($rules[$tagId]);
					}
					// Save each tagged fileId with tag to show error message later
					$tags[$tagId]->addFileId($fileId);
				}
			}
		}

		return $tags;
	}

	/**
	 * preShare listener, check if shared directory contains
	 * classified files.
	 *
	 * @param $params
	 * @throws GenericShareException
	 * @throws \OCP\Files\InvalidPathException
	 * @throws \OCP\Files\NotFoundException
	 */
	public function policyDisallowLinkShares($params) {
		if ($params['shareType'] === Share::SHARE_TYPE_LINK) {
			if ($this->isLinkShareDisallowed($params['fileSource'], $disallowedIds, $disallowedTag)) {
				$fileIds = \array_slice($disallowedIds, 0, 3);
				$fileNames = \implode(", ", $this->formatFileIdsToPaths($fileIds));

				$msg = $this->l10n->n(
					'The file \'%1$s\' can\'t be shared via public link (classified as \'%2$s\')',
					'The files \'%1$s\' can\'t be shared via public link (classified as \'%2$s\')',
					\count($fileIds),
					[$fileNames, $disallowedTag]
				);

				throw new GenericShareException($msg);
			}
		}
	}

	/**
	 * Recursively scans all parent and child nodes for classification tags
	 * which disallow link-shares.
	 *
	 * @param $fileSource
	 * @param array $disallowedFileIds Returns which fileIds are disallowed
	 * @param string $disallowedTag Returns which tag makes the file disallowed
	 * @return bool is linkshare disallowed
	 * @throws \OCP\Files\InvalidPathException
	 * @throws \OCP\Files\NotFoundException
	 */
	private function isLinkShareDisallowed($fileSource, &$disallowedFileIds = [], &$disallowedTag = null) {
		$tags = $this->getTags($fileSource);
		foreach ($tags as $tagId => $tag) {
			if (!$tag->getRule()->getIsLinkShareAllowed()) {
				$disallowedFileIds = $tags[$tagId]->getFileIds();
				$disallowedTag = $this->tagManager->getTagsByIds($tagId)[$tagId]->getName();
				return true;
			}
		}

		return false;
	}

	/**
	 * Used for display only, if there are more than 3 fileIds
	 * only the first 3 are shown (file1, file2, file3, ...)
	 *
	 * @param array $fileIds
	 * @return array|string
	 */
	private function formatFileIdsToPaths(array $fileIds) {
		$paths = [];
		foreach ($fileIds as $fileId) {
			$node = $this->getNodeById($fileId);
			if ($node) {
				$paths[] = $node->getName();
			}
		}

		if (\count($fileIds) > 3) {
			return $paths . ', ...';
		}

		return $paths;
	}

	/**
	 * @param Node|Folder $folder
	 * @return \RecursiveIteratorIterator
	 * @throws \OCP\Files\NotFoundException
	 */
	public function getRecursiveIterator(Folder $folder) {
		return new \RecursiveIteratorIterator(
			new RecursiveNodeIterator($folder->getDirectoryListing()),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
	}

	/**
	 * @param $params
	 * @throws Share\Exceptions\ShareNotFound
	 * @throws \Exception
	 * @throws \OCP\Files\InvalidPathException
	 * @throws \OCP\Files\NotFoundException
	 */
	public function policyExpireLinkNoPassword($params) {
		if ($params['passwordSet'] === true) {
			return;
		}

		$urlPath = \OC::$server->getRequest()->getPathInfo();
		// get id from url on updates
		if (\preg_match('#/apps/files_sharing/api/v1/shares/(.*)$#', $urlPath, $matches)) {
			$shareId = $matches[1];
			$providerId = $params['shareType'] === Share::SHARE_TYPE_REMOTE ? 'ocFederatedSharing' : 'ocinternal';
			$share = \OC::$server->getShareManager()->getShareById("$providerId:$shareId");
			$nodeId = $share->getNodeId();
		} else {
			//get path from POST when creating a link
			$path = \OC::$server->getRequest()->getParam('path');
			$node = $this->getNodeByPath($path);
			$nodeId = $node->getId();
		}

		$minExpireAfterDays = null;

		foreach ($this->getTags($nodeId) as $tagId => $tag) {
			$rule = $tag->getRule();
			$expireAfterDays = $rule->getDaysUntilPasswordlessLinkSharesExpire();
			if ($expireAfterDays !== null && $expireAfterDays > 0) {
				if ($minExpireAfterDays === null || $expireAfterDays < $minExpireAfterDays) {
					$minExpireAfterDays = $expireAfterDays;
					$minExpiryTagName = $this->tagManager->getTagsByIds($tagId)[$tagId]->getName();
				}
			}
		}

		if ($minExpireAfterDays !== null) {
			$ruleExpiryDate = new \DateTime();
			$ruleExpiryDate->setTime(0, 0, 0);
			$ruleExpiryDate->add(new \DateInterval("P{$minExpireAfterDays}D"));
			if ($params['expirationDate'] === null) {
				$params['expirationDate'] = $ruleExpiryDate;
			}

			if ($params['expirationDate'] > $ruleExpiryDate) {
				$params['accepted'] = false;
				$params['message'] = $this->l10n->t(
					'The expiration date cannot exceed %1$s days (classified as \'%2$s\').',
					[$minExpireAfterDays, $minExpiryTagName]
				);
			}
		}
	}

	/**
	 * @param $params
	 * @throws \OCP\Files\InvalidPathException
	 * @throws \OCP\Files\NotFoundException
	 */
	public function policyExpireLinkNoPasswordOnPasswordChange($params) {
		if ($params['disabled'] === true) {
			$maxDays = null;
			foreach ($this->getTags($params['itemSource']) as $tagId => $tag) {
				if ($tag['policies']['expireLinkNoPassword'] > 0 &&
					($maxDays === null || $tag['policies']['expireLinkNoPassword'] < $maxDays)) {
					$maxDays = $tag['policies']['expireLinkNoPassword'];
				}
			}
			if ($maxDays) {
				$date = \date('d-m-Y', \strtotime("+$maxDays days"));
				Share::setExpirationDate($params['itemType'], $params['itemSource'], $date);
			}
		}
	}

	/**
	 * @param string $fileName
	 * @return bool
	 */
	public function isOffice($fileName) {
		$extension = \pathinfo($fileName, PATHINFO_EXTENSION);

		return \in_array($extension, ['docx','dotx','xlsx','xltx','pptx','ppsx','potx']);
	}

	/**
	 * Prevents move or copy of classified files to directories which
	 * are shared by public link.
	 *
	 * @param $args
	 * @throws ForbiddenException
	 * @throws \OCP\Files\InvalidPathException
	 * @throws \OCP\Files\NotFoundException
	 */
	public function moveAndCopyListener($args) {
		// Skip if just file rename without dir move
		if (\dirname($args['oldpath']) === \dirname($args['newpath'])) {
			return;
		};

		// Get rename target dir path
		$targetPath = \dirname($args['newpath']);
		$userFolder = \OC::$server->getRootFolder();
		$targetDir = $userFolder->get($targetPath);

		$parentNodes = $this->getAllNodeIds($targetDir, true);
		$shareManager = \OC::$server->getShareManager();

		foreach ($parentNodes as $nodeId) {
			if (!$this->getNodeById($nodeId)) {
				continue;
			}

			$ownerId = $this->getNodeById($nodeId)->getOwner()->getUID();
			$shares = $shareManager->getAllSharesBy(
				$ownerId,
				[Share::SHARE_TYPE_LINK],
				[$nodeId],
				true
			);

			$oldPath = $userFolder->get($args['oldpath']);
			if (!empty($shares) && $this->isLinkShareDisallowed($oldPath->getId(), $disallowedFileIds, $disallowedTag)) {
				$msg = $this->l10n->t(
					"A policy prohibits moving files classified as '%s' into publicly shared folders.", [$disallowedTag]
				);

				throw new ForbiddenException($msg, false);
			}
		}
	}

	/**
	 * @return Rule[]
	 */
	public function getClassificationRules() {
		if ($this->classificationRules === null) {
			$this->classificationRules = $this->persistence->loadRulesIndexedByTagId();
		}
		return $this->classificationRules;
	}

	/**
	 * @param string $path
	 *
	 * @return array |\SimpleXMLElement
	 */
	public function getDocumentProperties($path) {
		$zip = new \ZipArchive();
		if ($zip->open($path) === true) {
			// get the custom.xml file from the office document
			$customXml = $zip->getFromName('docProps/custom.xml');
			$zip->close();
		} else {
			// Not a valid zip file
			$customXml = '';
		}

		$customXml = \str_replace('xmlns="', 'ns="', $customXml);

		/** @var \SimpleXMLElement $properties */
		$properties = Security::scan($customXml);

		if (!$properties) {
			return [];
		}
		return $properties;
	}

	/**
	 * @param File $file
	 * @return array
	 *
	 * @throws Forbidden
	 * @throws \OCP\Files\InvalidPathException
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Lock\LockedException
	 */
	public function classify(File $file) {
		if (!$this->isOffice($file->getName())) {
			return [];
		}

		// zip archive needs a temp file
		$tempFile = \OC::$server->getTempManager()->getTemporaryFile();

		try {
			\file_put_contents($tempFile, $file->fopen('rb'));
			$properties = $this->getDocumentProperties($tempFile);
		} catch (NotPermittedException $e) {
			$originalPath = $file->getInternalPath();
			if ($originalPath !== '' && isset($this->propertiesCache[$originalPath])) {
				$properties = $this->propertiesCache[$originalPath];
			} else {
				throw $e;
			}
		}

		if (!$properties) {
			return [];
		}

		$tagObjectMapper = \OC::$server->getSystemTagObjectMapper();
		$assignedTags = [];

		foreach ($this->getClassificationRules() as $tagId => $rule) {
			$tag = $this->tagManager->getTagsByIds($tagId)[$tagId];
			if ($rule->hasDocumentIdQuery()) {
				$docIds = $properties->xpath($rule->getDocumentIdXpath());
				$this->logger->info("Checking classified file '{$file->getName()}' with document id '{$docIds[0]}'", ['app' => 'files_classifier']);
			}

			// check if file matches classification
			foreach ($properties->xpath($rule->getXpath()) as $property) {
				$this->logger->info($this->getUserId()." uploaded a classified file '{$file->getName()}' with document class '$property'", ['app' => 'files_classifier']);
				if ((string) $property === $rule->getValue()) {
					$this->logger->debug("Assigning tag '{$tag->getName()}' to '{$file->getName()}'", ['app' => 'files_classifier']);
					$tagObjectMapper->assignTags($file->getId(), 'files', $tagId);

					if (!$rule->getIsUploadAllowed()) {
						$file->unlock(ILockingProvider::LOCK_SHARED);
						$file->delete();
						$msg = $this->l10n->t(
							"A policy prohibits uploading files classified as '%s'.", [$tag->getName()]
						);

						throw new Forbidden($msg, false);
					}

					$parentNodes = $this->getAllNodeIds($file, true);
					$shareManager = \OC::$server->getShareManager();

					foreach ($parentNodes as $nodeId) {
						$ownerId = $this->getNodeById($nodeId)->getOwner()->getUID();
						$shares = $shareManager->getAllSharesBy(
							$ownerId,
							[Share::SHARE_TYPE_LINK],
							[$nodeId],
							true
						);

						if (!empty($shares) && $this->isLinkShareDisallowed($file->getId(), $disallowedFileIds, $disallowedTag)) {
							$file->unlock(ILockingProvider::LOCK_SHARED);
							$file->delete();
							$msg = $this->l10n->t(
								"A policy prohibits uploading files classified as '%s' into publicly shared folders.", [$disallowedTag]
							);

							throw new Forbidden($msg, false);
						}
					}
					$assignedTags[] = $tag->getName();
				}
			}
		}
		return $assignedTags;
	}

	/**
	 * @param string $filePath
	 * @param string $originalPath
	 *
	 * @throws Forbidden
	 */
	public function classifyByPath($filePath, $originalPath) {
		$fileName = \basename($filePath);
		$properties = $this->getDocumentProperties($filePath);
		$this->propertiesCache[$originalPath] = $properties;

		if (!$properties) {
			return;
		}

		foreach ($this->getClassificationRules() as $tagId => $rule) {
			$tag = $this->tagManager->getTagsByIds($tagId)[$tagId];
			if ($rule->hasDocumentIdQuery()) {
				$docIds = $properties->xpath($rule->getDocumentIdXpath());
				$this->logger->info("Checking classified file '{$fileName}' with document id '{$docIds[0]}'", ['app' => 'files_classifier']);
			}

			// check if file matches classification
			foreach ($properties->xpath($rule->getXpath()) as $property) {
				if ((string) $property === $rule->getValue()) {
					if (!$rule->getIsUploadAllowed()) {
						$msg = $this->l10n->t(
							"A policy prohibits uploading files classified as '%s'.", [$tag->getName()]
						);
						throw new Forbidden($msg, false);
					}
				}
				$this->logger->info($this->getUserId()." uploaded a classified file '{$fileName}' with document class '$property'", ['app' => 'files_classifier']);
			}
		}
	}

	/**
	 * @return string
	 */
	private function getUserId() {
		if ($this->user === null) {
			$uid = 'Unauthenticated user';
		} else {
			$uid = $this->user->getUID();
		}
		return $uid;
	}
}
