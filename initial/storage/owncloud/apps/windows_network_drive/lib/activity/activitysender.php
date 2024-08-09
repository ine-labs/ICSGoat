<?php
/**
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 *
 * @copyright Copyright (c) 2020, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\windows_network_drive\lib\activity;

use OCP\IConfig;
use OCP\ILogger;
use OCP\IUser;
use OCP\Files\NotFoundException;
use OCP\Files\Node;
use OCP\Activity\IManager;
use OCA\windows_network_drive\lib\WND;
use OCA\windows_network_drive\lib\activity\Extension;
use OCA\windows_network_drive\lib\activity\ShareFinder;

class ActivitySender {
	/** @var IManager */
	private $activityManager;
	/** @var ShareFinder */
	private $shareFinder;
	/** @var IConfig */
	private $config;
	/** @var ILogger */
	private $logger;

	public function __construct(IManager $activityManager, ShareFinder $shareFinder, IConfig $config, ILogger $logger) {
		$this->activityManager = $activityManager;
		$this->shareFinder = $shareFinder;
		$this->config = $config;
		$this->logger = $logger;
	}

	/**
	 * Convenient method to get a file / folder node for that path in that storage.
	 * This method relies on the WND storage to have set both the "usingUsers" and
	 * the storage mount. WND storages coming from the StorageFactory should have this
	 * information set and can be used here. If this information is missing, this method
	 * will return false
	 *
	 * In case of multiple users using the storage, and multiple mount points in use,
	 * the first user will be used, as well as the first mount point.
	 *
	 * @param WND $storage the WND storage to check
	 * @param string $path the path within the WND storage
	 * @throws NotFoundException if the target node is missing
	 * @return Node|false the target node for that path, or false if the information is
	 * missing and can't check for a node.
	 */
	public function getNodeFor(WND $storage, string $path) {
		$mountsWithUsers = $storage->getMountsWithUsers();

		\reset($mountsWithUsers);
		$firstMount = \key($mountsWithUsers);
		if ($firstMount === null) {
			return false;
		}
		$firstUserUsingStorage = $mountsWithUsers[$firstMount][0]; // at least one user must exists

		$mountedPath = \ltrim("{$firstMount}/{$path}", '/');
		return $this->shareFinder->getNode($firstUserUsingStorage, $mountedPath);
	}

	/**
	 * Send a "file update" activity to the users who are using that WND storage and
	 * that can access to tha path.
	 *
	 * @param WND $storage the storage to check
	 * @param string $path the path within the storage
	 * @param bool $onlyForSharees send the notification only for the sharees, not for both
	 * the sharees and the users using the storage
	 */
	public function sendFileUpdatedActivity(WND $storage, string $path, $onlyForSharees = false) {
		if (!$this->config->getSystemValue('wnd.activity.registerExtension', false)) {
			$this->logger->debug('Sending activity event rejected: WND activity extension is not registered', ['app' => 'wnd']);
			return;
		}

		$mountsWithUsers = $storage->getMountsWithUsers();

		\reset($mountsWithUsers);
		$firstMount = \key($mountsWithUsers);
		if ($firstMount === null) {
			$this->logger->warning('Cannot send activity for storage ' . $storage->getId() . " and path {$path}. No mount point associated", ['app' => 'wnd']);
			return;
		}

		if (!$onlyForSharees) {
			foreach ($mountsWithUsers as $mount => $userList) {
				$mountedPath = \ltrim("{$mount}/{$path}", '/');
				foreach ($userList as $userObj) {
					$activity = $this->activityManager->generateEvent();
					$activity->setApp(Extension::APP_NAME)
						->setType(Extension::TYPE_FILE_UPDATED)
						->setAffectedUser($userObj->getUID())
						->setSubject(Extension::SUBJECT_FILE_UPDATED, [$mountedPath]);

					$this->activityManager->publish($activity);
				}
			}
		}

		if (!$this->config->getSystemValue('wnd.activity.sendToSharees', false)) {
			$this->logger->debug('WND activity extension will not send activity to sharees', ['app' => 'wnd']);
			return;
		}

		$firstUserUsingStorage = $mountsWithUsers[$firstMount][0]; // at least one user must exists

		// in order to find the sharees we only need to check with the first user using
		// the storage because the share is linked by fileid, which must be the same for
		// all the users because the WND storage is the same for all of the users.
		$mountedPath = \ltrim("{$firstMount}/{$path}", '/');
		$sharees = $this->shareFinder->findSharees($firstUserUsingStorage, $mountedPath);

		foreach ($sharees as $userId => $shareInfo) {
			foreach ($shareInfo as $shareId => $share) {
				if ($share->getNodeType() === 'file') {
					$shareTarget = \ltrim($share->getTarget(), '/');
				} else {
					// check if the folder share refers to the target path or a parent folder
					try {
						$shareNode = $share->getNode();
					} catch (NotFoundException $e) {
						$this->logger->warning("deleting share with id $shareId because it doesn't have a valid node", ['app' => 'wnd']);
						$this->shareFinder->deleteShare($share);
						continue;
					}

					$itemPath = $this->shareFinder->getFullPath($firstUserUsingStorage, $mountedPath);
					$relativePath = \ltrim($shareNode->getRelativePath($itemPath), '/');

					if ($relativePath === '') {
						// shareNode is the same as the path -> choose the share target
						$shareTarget = \ltrim($share->getTarget(), '/');
					} else {
						// point to the relative path, which is what has changed
						$shareTarget = \ltrim($share->getTarget() . "/$relativePath", '/');
					}
				}
				$activity = $this->activityManager->generateEvent();
				$activity->setApp(Extension::APP_NAME)
					->setType(Extension::TYPE_FILE_UPDATED)
					->setAffectedUser($userId)
					->setSubject(Extension::SUBJECT_FILE_UPDATED, [$shareTarget]);

				$this->activityManager->publish($activity);
			}
		}
	}

	/**
	 * Send a "file removed" activity to the users who are using that WND storage and
	 * that can access to tha path.
	 *
	 * @param WND $storage the storage to check
	 * @param string $path the path within the storage
	 * @param Node|null $removedNode the node that has been removed. It must match the
	 * storage and path. If the node is null, we won't be able to send the notification
	 * to the sharees in that node if the node corresponds to a share
	 * @param bool $onlyForSharees send the notification only for the sharees, not for both
	 * the sharees and the users using the storage
	 */
	public function sendFileRemovedActivity(WND $storage, string $path, Node $removedNode = null, $onlyForSharees = false) {
		if (!$this->config->getSystemValue('wnd.activity.registerExtension', false)) {
			$this->logger->debug('Sending activity event rejected: WND activity extension is not registered', ['app' => 'wnd']);
			return;
		}

		$mountsWithUsers = $storage->getMountsWithUsers();

		\reset($mountsWithUsers);
		$firstMount = \key($mountsWithUsers);
		if ($firstMount === null) {
			$this->logger->warning('Cannot send activity for storage ' . $storage->getId() . " and path {$path}. No mount point associated", ['app' => 'wnd']);
			return;
		}

		if (!$onlyForSharees) {
			foreach ($mountsWithUsers as $mount => $userList) {
				$mountedPath = \ltrim("{$mount}/{$path}", '/');
				foreach ($userList as $userObj) {
					$activity = $this->activityManager->generateEvent();
					$activity->setApp(Extension::APP_NAME)
						->setType(Extension::TYPE_FILE_REMOVED)
						->setAffectedUser($userObj->getUID())
						->setSubject(Extension::SUBJECT_FILE_REMOVED, [$mountedPath]);

					$this->activityManager->publish($activity);
				}
			}
		}

		if (!$this->config->getSystemValue('wnd.activity.sendToSharees', false)) {
			$this->logger->debug('WND activity extension will not send activity to sharees', ['app' => 'wnd']);
			return;
		}

		$firstUserUsingStorage = $mountsWithUsers[$firstMount][0]; // at least one user must exists

		// in order to find the sharees we only need to check with the first user using
		// the storage because the share is linked by fileid, which must be the same for
		// all the users because the WND storage is the same for all of the users.
		$mountedPath = \ltrim("{$firstMount}/{$path}", '/');
		$sharees = $this->shareFinder->findSharees($firstUserUsingStorage, $mountedPath);
		if ($removedNode !== null) {
			// search the shares and notify the sharees. This node isn't expected to be returned
			// in the findSharees functions because the node has been deleted already
			$removedNodeSharees = $this->shareFinder->findShareesJustFor($firstUserUsingStorage, $removedNode);
		} else {
			$removedNodeSharees = [];
		}

		// for the removedNodeSharees, the process is a bit different due to the missing node
		foreach ($removedNodeSharees as $userId => $shareInfo) {
			foreach ($shareInfo as $shareId => $share) {
				$shareTarget = \ltrim($share->getTarget(), '/');
				$activity = $this->activityManager->generateEvent();
				$activity->setApp(Extension::APP_NAME)
					->setType(Extension::TYPE_FILE_REMOVED)
					->setAffectedUser($userId)
					->setSubject(Extension::SUBJECT_FILE_REMOVED, [$shareTarget]);

				$this->activityManager->publish($activity);

				// delete the share because the node isn't there
				$this->logger->debug("deleting share $shareId associated with node " . $removedNode->getPath(), ['app' => 'wnd']);
				$this->shareFinder->deleteShare($share);
			}
		}

		foreach ($sharees as $userId => $shareInfo) {
			foreach ($shareInfo as $shareId => $share) {
				if ($share->getNodeType() === 'file') {
					$shareTarget = \ltrim($share->getTarget(), '/');
				} else {
					// check if the folder share refers to the target path or a parent folder
					try {
						$shareNode = $share->getNode();
					} catch (NotFoundException $e) {
						$this->logger->warning("deleting share with id $shareId because it doesn't have a valid node", ['app' => 'wnd']);
						$this->shareFinder->deleteShare($share);
						continue;
					}

					$itemPath = $this->shareFinder->getFullPath($firstUserUsingStorage, $mountedPath);
					$relativePath = \ltrim($shareNode->getRelativePath($itemPath), '/');

					if ($relativePath === '') {
						// shareNode is the same as the path -> choose the share target
						$shareTarget = \ltrim($share->getTarget(), '/');
					} else {
						// point to the relative path, which is what has changed
						$shareTarget = \ltrim($share->getTarget() . "/$relativePath", '/');
					}
				}
				$activity = $this->activityManager->generateEvent();
				$activity->setApp(Extension::APP_NAME)
					->setType(Extension::TYPE_FILE_REMOVED)
					->setAffectedUser($userId)
					->setSubject(Extension::SUBJECT_FILE_REMOVED, [$shareTarget]);

				$this->activityManager->publish($activity);
			}
		}
		// rely on the shareManager cleanup methods to send the notifications to the sharees
		$this->shareFinder->cleanSharesWithInvalidNodes();
	}

	/**
	 * Send a "file renamed" activity to the users who are using that WND storage and
	 * that can access to tha path.
	 *
	 * @param WND $storage the storage to check
	 * @param string $src the source path within the storage
	 * @param string $dst the destination path within the storage
	 * @param bool $onlyForSharees send the notification only for the sharees, not for both
	 * the sharees and the users using the storage
	 */
	public function sendFileRenamedActivity(WND $storage, string $src, string $dst, $onlyForSharees = false) {
		if (!$this->config->getSystemValue('wnd.activity.registerExtension', false)) {
			$this->logger->debug('Sending activity event rejected: WND activity extension is not registered', ['app' => 'wnd']);
			return;
		}

		$mountsWithUsers = $storage->getMountsWithUsers();

		\reset($mountsWithUsers);
		$firstMount = \key($mountsWithUsers);
		if ($firstMount === null) {
			$this->logger->warning('Cannot send activity for storage ' . $storage->getId() . " and path {$path}. No mount point associated", ['app' => 'wnd']);
			return;
		}

		if (!$onlyForSharees) {
			foreach ($mountsWithUsers as $mount => $userList) {
				$mountedPathSrc = \ltrim("{$mount}/{$src}", '/');
				$mountedPathDst = \ltrim("{$mount}/{$dst}", '/');
				foreach ($userList as $userObj) {
					$activity = $this->activityManager->generateEvent();
					$activity->setApp(Extension::APP_NAME)
						->setType(Extension::TYPE_FILE_RENAMED)
						->setAffectedUser($userObj->getUID())
						->setSubject(Extension::SUBJECT_FILE_RENAMED, [$mountedPathSrc, $mountedPathDst]);

					$this->activityManager->publish($activity);
				}
			}
		}

		if (!$this->config->getSystemValue('wnd.activity.sendToSharees', false)) {
			$this->logger->debug('WND activity extension will not send activity to sharees', ['app' => 'wnd']);
			return;
		}

		$firstUserUsingStorage = $mountsWithUsers[$firstMount][0]; // at least one user must exists

		// in order to find the sharees we only need to check with the first user using
		// the storage because the share is linked by fileid, which must be the same for
		// all the users because the WND storage is the same for all of the users.
		$mountedPathSrc = \ltrim("{$firstMount}/{$src}", '/');
		$mountedPathDst = \ltrim("{$firstMount}/{$dst}", '/');
		$sharees = $this->shareFinder->findSharees($firstUserUsingStorage, $mountedPathDst);

		foreach ($sharees as $userId => $shareInfo) {
			foreach ($shareInfo as $shareId => $share) {
				if ($share->getNodeType() === 'file') {
					// skip notification because the sharee won't notice the rename
					// target share will remain with the same name for the sharee
					continue;
				} else {
					// check if the folder share refers to the target path or a parent folder
					try {
						$shareNode = $share->getNode();
					} catch (NotFoundException $e) {
						$this->logger->warning("deleting share with id $shareId because it doesn't have a valid node", ['app' => 'wnd']);
						$this->shareFinder->deleteShare($share);
						continue;
					}

					$itemPathSrc = $this->shareFinder->getFullPath($firstUserUsingStorage, $mountedPathSrc);
					$itemPathDst = $this->shareFinder->getFullPath($firstUserUsingStorage, $mountedPathDst);
					$relativePathSrc = \ltrim($shareNode->getRelativePath($itemPathSrc), '/');
					$relativePathDst = \ltrim($shareNode->getRelativePath($itemPathDst), '/');

					if ($relativePathDst === '') {
						// shareNode is the same as the path
						// sharee won't notice the rename so skip this notification
						continue;
					} else {
						// point to the relative path, which is what has changed
						$shareTargetSrc = \ltrim($share->getTarget() . "/$relativePathSrc", '/');
						$shareTargetDst = \ltrim($share->getTarget() . "/$relativePathDst", '/');
					}
				}
				$activity = $this->activityManager->generateEvent();
				$activity->setApp(Extension::APP_NAME)
					->setType(Extension::TYPE_FILE_RENAMED)
					->setAffectedUser($userId)
					->setSubject(Extension::SUBJECT_FILE_RENAMED, [$shareTargetSrc, $shareTargetDst]);

				$this->activityManager->publish($activity);
			}
		}
	}
}
