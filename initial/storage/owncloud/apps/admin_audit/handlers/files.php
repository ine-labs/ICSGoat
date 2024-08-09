<?php
/**
 * ownCloud
 *
 * @author Jörn Friedrich Dreyer <jfd@owncloud.com>
 * @author Tom Needham <tom@owncloud.com>
 * @author Frank Karlitscheck <frank@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright (C) 2016 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Admin_Audit\Handlers;

use OC\Files\Filesystem;
use OC\Files\View;
use OCP\Files\FileInfo;
use OCP\IUser;

class Files extends Base {
	public static $beforeDeleteFileInfo = [];

	public static function excludeLog($path, $oldPath = '') {
		$uid = "";
		$path = \ltrim($path, "/");
		/**
		 * Making assumption that path has to be $uid/files/
		 * So if the path when exploded doesn't make up to this
		 * pattern, then this method will return true. Else return
		 * false.
		 */
		$splitPath = \explode("/", $path);
		if (\count($splitPath) < 2) {
			//For example if the path has /avatars
			return true;
		} else {
			//For example if the path has /avatars/12
			if ($splitPath[1] !==  'files') {
				return true;
			} else {
				//After doing ltrim chances are bit low for empty. Still who knows
				if ($splitPath[0] === "") {
					return true;
				}
				$uid = $splitPath[0];
			}
		}
		$userPathStarts = $uid . "/files/";

		// If the old path is specified, see if it was an archive move
		if ($oldPath !== '') {
			$oldPath = \ltrim($oldPath, "/");
			$oldSplitPath = \explode("/", $oldPath);
			if ($oldSplitPath[1] === "archive" && $oldSplitPath[2] === "files") {
				return true;
			}
		}

		/**
		 * Final check if the path has $uid/files/ in it
		 * if so then get the data logged. Else exclude
		 * them from the logger
		 */
		if (\strpos($path, $userPathStarts) !== false) {
			return false;
		}

		return true;
	}

	// Normal files actions
	public static function rename($params) {
		$result = self::excludeLog($params['newpath'], $params['oldpath']);
		if ($result === true) {
			return;
		}
		$logger = self::getLogger();
		$fileInfo = \OC::$server->getRootFolder()->get($params['newpath']);

		$fileId = $fileInfo->getId();
		/*
		 * getId claims to always return an int or throw an exception,
		 * so in theory $fileId cannot ever be null.
		 * suppress the message from phpstan about this.
		 */
		/** @phpstan-ignore-next-line */
		if ($fileId === null) {
			return;
		}

		// Exclude trashbin restore renames as these have thier own event
		if (\strpos($params['oldpath'], '/'.$fileInfo->getOwner()->getUID().'/files_trashbin/files/')=== 0) {
			return;
		}

		$tags = $logger->getTagStringForFile($fileId);

		if ($tags !== '') {
			$message = 'Rename "{oldpath}" with tags: {tags} to "{newpath}" by {actor}, owner: {owner}';
		} else {
			$message = 'Rename "{oldpath}" to "{newpath}" by {actor}, owner: {owner}';
		}

		self::getLogger()->log(
			$message,
			[
				'owner' => $fileInfo->getOwner()->getUID(),
				'oldpath' => $params['oldpath'],
				'newpath' => $params['newpath'],
				'tags' => $tags,
			],
			[
				'action' => 'file_rename',
				'oldPath' => $params['oldpath'],
				'path' => $params['newpath'],
				'tags' => $tags,
				'fileId' => (string) $fileInfo->getId(),
			]
		);
	}
	public static function copy($params) {
		self::logCopy($params);
	}

	public static function copyUsingDAV($params) {
		$user = \OC::$server->getUserSession()->getUser();
		self::logCopy($params, false, $user);
	}

	protected static function logCopy($params, $excludeLog = true, $userObj = null) {
		if ($excludeLog === true) {
			$result = self::excludeLog($params['oldpath']);
			if ($result === true) {
				return;
			}
		}

		$logger = self::getLogger();
		if ($userObj === null) {
			$oldFileInfo = \OC::$server->getRootFolder()->get($params['oldpath']);
			$newFileInfo = \OC::$server->getRootFolder()->get($params['newpath']);
		} else {
			$oldFileInfo = \OC::$server->getUserFolder($userObj->getUID())->get($params['oldpath']);
			$newFileInfo = \OC::$server->getUserFolder($userObj->getUID())->get($params['newpath']);
		}

		$tags = \json_decode($logger->getTagStringForFile($oldFileInfo->getId()), true);

		if ($tags !== null) {
			$message = 'Copy "{oldpath}" with tags: {tags} to "{newpath}" by {actor}, oldOwner: {oldOwner}, newOwner: {owner}';
		} else {
			$message = 'Copy "{oldpath}" to "{newpath}" by {actor}, oldOwner: {oldOwner}, newOwner: {owner}';
		}
		self::getLogger()->log(
			$message,
			[
				'oldOwner' => $oldFileInfo->getOwner()->getUID(),
				'owner' => $newFileInfo->getOwner()->getUID(),
				'oldpath' => $oldFileInfo->getPath(),
				'newpath' => $newFileInfo->getPath(),
				'tags' => $tags,
			],
			[
				'action' => 'file_copy',
				'sourceOwner' => $oldFileInfo->getOwner()->getUID(),
				'owner' => $oldFileInfo->getOwner()->getUID(),
				'sourcePath' => $oldFileInfo->getPath(),
				'path' => $newFileInfo->getPath(),
				'sourceFileId' => (string) $oldFileInfo->getId(),
				'fileId' => (string) $newFileInfo->getId(),
				'tags' => $tags,
			]
		);
	}

	public static function create($params) {
		$result = self::excludeLog($params['path']);
		if ($result === true) {
			return;
		}
		$fileInfo = \OC::$server->getRootFolder()->get($params['path']);
		self::getLogger()->log(
			'Create "{path}"{tags} by {actor}, owner: {owner}',
			[
				'owner' => $fileInfo->getOwner()->getUID(),
				'path' => $fileInfo->getPath(),
				'tags' => '',// Since we only show the tags of this file, this makes no sense yet.
			],
			[
				'action' => 'file_create',
				'path' => $fileInfo->getPath(),
				'owner' => $fileInfo->getOwner()->getUID(),
				'fileId' => (string) $fileInfo->getId(),
				'tags' => '',//Since the tags are empty, this makes no sense yet. ( same as parameters array )
			]
		);
	}
	public static function update($params) {
		$fileInfo = \OC::$server->getRootFolder()->get($params['path']);
		$result = self::excludeLog($fileInfo->getPath());
		if ($result === true) {
			return;
		}
		$logger = self::getLogger();

		$tags = $logger->getTagStringForFile($fileInfo->getId());
		if ($tags !== '') {
			$message = 'Update "{path}"{tags} by {actor}, owner: {owner}';
		} else {
			$message = 'Update "{path}" by {actor}, owner: {owner}';
		}

		$logger->log(
			$message,
			[
				'owner' => $fileInfo->getOwner()->getUID(),
				'path' => $params['path'],
				'tags' => $logger->getTagStringForFile($fileInfo->getId()),
			],
			[
				'action' => 'file_update',
				'path' => $fileInfo->getPath(),
				'owner' => $fileInfo->getOwner()->getUID(),
				'fileId' => (string) $fileInfo->getId(),
				'tags' => $tags,
			]
		);
	}
	public static function read($params) {
		$result = self::excludeLog(self::getAbsolutePath($params[Filesystem::signal_param_path]));
		if ($result === true) {
			return;
		}

		$fileInfo = self::getFileInfo($params[Filesystem::signal_param_path]);
		if ($fileInfo === null) {
			return;
		}

		$logger = self::getLogger();
		$fileId = $fileInfo->getId();

		$tags = $logger->getTagStringForFile($fileId);
		if ($tags !== '') {
			$message = 'Read "{path}"{tags} by {actor}, owner: {owner}';
		} else {
			$message = 'Read "{path}" by {actor}, owner: {owner}';
		}

		$logger->log(
			$message,
			[
				'owner' => self::getHelper()->getOwner($params[Filesystem::signal_param_path]),
				'path' => $fileInfo->getPath(),
				'tags' => $tags,
			],
			[
				'action' => 'file_read',
				'path' => $fileInfo->getPath(),
				'fileId' => (string) $fileId,
				'owner' => self::getHelper()->getOwner($params[Filesystem::signal_param_path]),
				'tags' => $tags,
			]
		);
	}

	public static function beforeDelete($params) {
		//This function helps to preserve any delete operations pending
		try {
			$fileInfo = \OC::$server->getRootFolder()->get($params['path']);
			$result = self::excludeLog($fileInfo->getPath());
			if ($result === true) {
				return;
			}
			// ensure the fileinfo is loaded because it will be used later, when the actual file
			// has been deleted
			$fileInfo->getId();
		} catch (\Exception $e) {
			/*
			 * There are cases where the thumbnails folders when tried to delete
			 * they don't exist and it causes lot of noise in the log file with
			 * exception. This is just to prevent that noise.
			 */

			return;
		}

		\array_push(self::$beforeDeleteFileInfo, $fileInfo);
	}

	public static function delete($params) {
		$logger = self::getLogger();
		//This is to make sure that delete is successfully done.
		while ($fileInfo = \array_shift(self::$beforeDeleteFileInfo)) {
			$tags = $logger->getTagStringForFile($fileInfo->getId());
			if ($tags !== '') {
				$message = "Delete {path} with tags: {tags} by {actor}, owner: {owner}";
			} else {
				$message = "Delete {path} by {actor}, owner: {owner}";
			}
			$logger->log(
				$message,
				[
					'owner' => $fileInfo->getOwner()->getUID(),
					'path' => $fileInfo->getPath(),
					'tags' => $tags,
				],
				[
					'action' => 'file_delete',
					'path' => $fileInfo->getPath(),
					'owner' => $fileInfo->getOwner()->getUID(),
					'fileId' => (string) $fileInfo->getId(),
					'tags' => $tags,
				]
			);
		}
	}

	// Trashbin
	public static function trash_post_restore($params) {
		$logger = self::getLogger();
		$user = $logger->getSessionUser();
		if ($user instanceof IUser) {
			$uid = $user->getUID();
		} else {
			$view = Filesystem::getView();
			$uid = $view->getOwner('');
		}
		$view = new \OC\Files\View('/' . $uid);
		$fileInfo = self::getFileInfo($params['filePath']);
		if ($fileInfo === null) {
			return;
		}

		$fileId = $fileInfo->getId();
		$tags = $logger->getTagStringForFile($fileId);
		if ($tags !== '') {
			$message = "Restore {oldpath}{tags} to {newpath} by {actor}, owner: {owner}";
		} else {
			$message = "Restore {oldpath} to {newpath} by {actor}, owner: {owner}";
		}

		$logger->log(
			$message,
			[
				'owner' => $view->getOwner('files/' . $params['filePath']),
				'oldpath' => $view->getAbsolutePath("files_trashbin/files" . $params['trashPath']),
				'newpath' => self::getAbsolutePath($params['filePath']),
				'tags' => $tags,
			],
			[
				'action' => 'file_trash_restore',
				'fileId' => (string) $fileId,
				'owner' => $view->getOwner('files/' . $params['filePath']),
				'oldPath' => $view->getAbsolutePath("files_trashbin/files" . $params['trashPath']),
				'newPath' => self::getAbsolutePath($params['filePath']),
				'tags' => $tags,
			]
		);
	}
	public static function trash_delete($params) {
		$logger = self::getLogger();
		$user = $logger->getSessionUser();
		if ($user instanceof IUser) {
			$uid = $user->getUID();
		} else {
			$view = Filesystem::getView();
			$uid = $view->getOwner('');
		}
		$view = new \OC\Files\View('/' . $uid);
		$fileInfo = $view->getFileInfo($params['path']);
		if ($fileInfo === false) {
			return;
		}
		$fileId = $fileInfo->getId();
		/*
		 * getId claims to always return an int or throw an exception,
		 * so in theory $fileId cannot ever be null.
		 * suppress the message from phpstan about this.
		 */
		/** @phpstan-ignore-next-line */
		if ($fileId === null) {
			return;
		}

		$tags = \json_decode($logger->getTagStringForFile($fileId), true);
		if ($tags !== null) {
			$message = "{path} was deleted from trash with tags {tags} by {actor}, owner: {owner}";
		} else {
			$message = "{path} was deleted from trash by {actor}, owner: {owner}";
		}

		$logger->log(
			$message,
			[
				'owner' => $view->getOwner($params['path']),
				'path' => $view->getAbsolutePath($params['path']),
				'tags' => $tags,
			],
			[
				'action' => 'file_trash_delete',
				'owner' => $view->getOwner($params['path']),
				'path' => $view->getAbsolutePath($params['path']),
				'tags' => $tags,
			]
		);
	}

	protected static function getAbsolutePath($path) {
		return Filesystem::getView()->getAbsolutePath($path);
	}

	protected static function getFileInfo($path) {
		if (Filesystem::getView() instanceof View) {
			if (Filesystem::getView()->getFileInfo($path) instanceof FileInfo) {
				return Filesystem::getView()->getFileInfo($path);
			}
		}
		return null;
	}

	// Versions
	public static function version_delete($params) {
		$logger = self::getLogger();

		switch ($params['trigger']) {
			case \OCA\Files_Versions\Storage::DELETE_TRIGGER_MASTER_REMOVED:
				$triggerMsg = 'deleted master file';
				break;
			case \OCA\Files_Versions\Storage::DELETE_TRIGGER_RETENTION_CONSTRAINT:
				$triggerMsg = 'retention constraint';
				break;
			case \OCA\Files_Versions\Storage::DELETE_TRIGGER_QUOTA_EXCEEDED:
				$triggerMsg = 'versions quota exceeded';
				break;
			default:
				$triggerMsg = 'unknown';
		}

		$logger->log(
			'Version delete "{path}", trigger: {trigger}',
			[
				'path' => self::getAbsolutePath($params[Filesystem::signal_param_path]),
				'trigger' => $triggerMsg
			],
			[
				'action' => 'file_version_delete',
				'path' => self::getAbsolutePath($params[Filesystem::signal_param_path]),
				'trigger' => $triggerMsg
			]
		);
	}

	public static function version_rollback($params) {
		$logger = self::getLogger();

		$logger->log(
			'Version of "{path}" restored to revision "{revision}"',
			[
				'path' => self::getAbsolutePath($params[Filesystem::signal_param_path]),
				'revision' => $params['revision']
			],
			[
				'action' => 'file_version_restore',
				'path' => self::getAbsolutePath($params[Filesystem::signal_param_path]),
				'revision' => $params['revision']
			]
		);
	}
}
