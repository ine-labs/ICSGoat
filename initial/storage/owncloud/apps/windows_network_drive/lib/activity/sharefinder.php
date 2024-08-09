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

use OCP\IUser;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Share\IManager;
use OCP\Share\IShare;

class ShareFinder {
	/** @var IManager */
	private $shareManager;
	/** @var IRootFolder */
	private $rootFolder;
	/** @var IUserManager */
	private $userManager;
	/** @var IGroupManager */
	private $groupManager;

	/**
	 * @param IManager $shareManager
	 * @param IRootFolder $rootFolder
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 */
	public function __construct(
		IManager $shareManager,
		IRootFolder $rootFolder,
		IUserManager $userManager,
		IGroupManager $groupManager
	) {
		$this->shareManager = $shareManager;
		$this->rootFolder = $rootFolder;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
	}

	/**
	 * Convenient method to get the full path for the $ocPath (which is relative
	 * to the user's directory)
	 * The method will return something like "/<user>/files/$ocPath", and won't check
	 * whether the file exists there.
	 *
	 * @param IUser $user the user whose user's directory will be used
	 * @param string $ocPath the ownCloud's path as shown to the user
	 * @return string the full path as described
	 */
	public function getFullPath(IUser $user, string $ocPath) {
		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		return $userFolder->getFullPath($ocPath);
	}

	/**
	 * Get the node (file or folder) in the $ocPath within the $user's directory.
	 * The node must exists or a FileNotFound will be thrown
	 *
	 * @param IUser $user the user whose directory we'll search
	 * @param string $ocPath the ownCloud's path within the user's directory
	 * @throws NotFoundException if the node doesn't exist
	 * @return Node the found node
	 */
	public function getNode(IUser $user, string $ocPath) {
		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		return $userFolder->get($ocPath);
	}

	/**
	 * Convenient method to delete a share
	 *
	 * @param IShare $share
	 */
	public function deleteShare(IShare $share) {
		$this->shareManager->deleteShare($share);
	}

	/**
	 * Convenient method to clean the shares
	 */
	public function cleanSharesWithInvalidNodes() {
		$this->shareManager->cleanSharesWithInvalidNodes();
	}

	/**
	 * Find the sharees that are able to access to that ownCloud path inside the
	 * userId's storage space
	 * This method will return a structure containing the userIds of the sharees as well
	 * as the shares, from the path in $ocPath to the root folder
	 * The structure is as follows:
	 * $data = [
	 *   'sharedWithUserId1' => [
	 *     'fullShareId1' => $share1,
	 *     'fullShareId2' => $share2,
	 *     ...
	 *   ],
	 *   'sharedWithUserId2' => [
	 *     'fullShareId1' => $share1,
	 *     'fullShareId4' => $share3,
	 *     ...
	 *   ],
	 *   ...
	 * ];
	 *
	 * Note that if the path doesn't exists within the user's space, this method will still
	 * traverse backwards up to the root folder looking for shares.
	 * Returned shares will be limited to local user and group shares
	 *
	 * @param IUser $user the owner of the storage space where the file / folder is located
	 * @param string $ocPath the ownCloud's path inside that user's storage space (including
	 * the mount point), such as "/WND/path/to/file.txt"
	 * @throws \OCP\Files\NotPermittedException
	 * @return array an array structure as described
	 */
	public function findSharees(IUser $user, string $ocPath) {
		$userFolder = $this->rootFolder->getUserFolder($user->getUID());

		$targetPath = $ocPath;
		$rootNodeReached = false;
		$node = null;
		$nodeList = [];
		do {
			try {
				if ($node) {
					$node = $node->getParent();
				} else {
					$node = $userFolder->get($targetPath);
				}
				$nodeList[] = $node;
			} catch (NotFoundException $e) {
				// if the node isn't found, try getting the parent folder by changing
				// the path for the next iteration. This is handled in the finally block
			} finally {
				$targetPath = \dirname($targetPath);
			}

			if ($targetPath === '.' || $targetPath === '' || $targetPath === '/') {
				$rootNodeReached = true;
			}
		} while (!$rootNodeReached);

		$userList = [];
		foreach ($nodeList as $node) {
			// shares should contain only user and group shares
			$shares = $this->shareManager->getSharesByPath($node);
			foreach ($shares as $share) {
				$sharedWithTarget = $share->getSharedWith();
				if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER) {
					$userObj = $this->userManager->get($sharedWithTarget);
					if ($userObj !== null) {
						$userId = $userObj->getUID();
						if (!isset($userList[$userId])) {
							$userList[$userId] = [];
						}
						$userList[$userId][$share->getFullId()] = $share;  // avoid duplicate targets
					}
				} else {
					$group = $this->groupManager->get($sharedWithTarget);
					if ($group !== null) {
						foreach ($group->getUsers() as $groupUser) {
							$userId = $groupUser->getUID();
							if (!isset($userList[$userId])) {
								$userList[$userId] = [];
							}
							$userList[$userId][$share->getFullId()] = $share;
						}
					}
				}
			}
		}
		return $userList;
	}

	/**
	 * Find the sharees that are able to access to that $node inside the
	 * userId's storage space
	 * This method will return a structure containing the userIds of the sharees as well
	 * as the shares
	 * The structure is as follows:
	 * $data = [
	 *   'userId1' => [
	 *     'fullShareId1' => $share1,
	 *     'fullShareId2' => $share2,
	 *     ...
	 *   ],
	 *   'userId2' => [
	 *     'fullShareId1' => $share1,
	 *     'fullShareId4' => $share3,
	 *     ...
	 *   ],
	 *   ...
	 * ];
	 *
	 * The difference with the "findSharees" method is that this method will just check
	 * the node, but it won't traverse up to the root.
	 *
	 * @param IUser $user the owner of the storage space where the file / folder is located
	 * @param Node $node the node to check. You might get the node, remove it and then reuse
	 * the same node to check for the shares.
	 * @throws \OCP\Files\NotPermittedException
	 * @return array an array structure as described
	 */
	public function findShareesJustFor(IUser $user, Node $node) {
		$shares = $this->shareManager->getSharesByPath($node);

		$userList = [];
		foreach ($shares as $share) {
			$sharedWithTarget = $share->getSharedWith();
			if ($share->getShareType() === \OCP\Share::SHARE_TYPE_USER) {
				$userObj = $this->userManager->get($sharedWithTarget);
				if ($userObj !== null) {
					$userId = $userObj->getUID();
					if (!isset($userList[$userId])) {
						$userList[$userId] = [];
					}
					$userList[$userId][$share->getFullId()] = $share;  // avoid duplicate targets
				}
			} else {
				$group = $this->groupManager->get($sharedWithTarget);
				if ($group !== null) {
					foreach ($group->getUsers() as $groupUser) {
						$userId = $groupUser->getUID();
						if (!isset($userList[$userId])) {
							$userList[$userId] = [];
						}
						$userList[$userId][$share->getFullId()] = $share;
					}
				}
			}
		}
		return $userList;
	}
}
