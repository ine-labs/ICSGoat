<?php
/**
 * @author Sujith Haridasan <sharidasan@owncloud.com>
 * @copyright (C) 2018 ownCloud, Inc.
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

class Comments extends Base {
	public static function createComment($event) {
		self::logCreateComment('{actor} created comment with comment id {commentId} for {type} "{file}"', $event);
	}

	public static function updateComment($event) {
		self::logUpdateComment('{actor} updated comment with comment id {commentId} for {type} "{file}"', $event);
	}

	public static function deleteComment($event) {
		self::logDeleteComment('{actor} delete comment with comment id {commentId} for {type} "{file}"', $event);
	}

	protected static function logDeleteComment($message, $arguments) {
		$view = Filesystem::getView();
		if ($view->filetype($view->getPath($arguments['objectId'])) === 'dir') {
			$type = 'folder';
		} else {
			$type = 'file';
		}
		$path = self::getAbsolutePath($arguments['objectId'], $view);
		self::getLogger()->log($message, [
			'file' => $path,
			'type' => $type,
			'commentId' => $arguments['commentId']
		], [
			'action' => 'comment_deleted',
			'fileId' => (string ) $arguments['objectId'],
			'path' => $path,
			'commentId' => $arguments['commentId'],
		]);
	}

	protected static function logCreateComment($message, $arguments) {
		$view = Filesystem::getView();
		if ($view->filetype($view->getPath($arguments['objectId'])) === 'dir') {
			$type = "folder";
		} else {
			$type = "file";
		}
		$path = self::getAbsolutePath($arguments['objectId'], $view);
		$extraFields = [
			'action' => 'comment_created',
			'fileId' => (string) $arguments['objectId'],
			'path' => $path,
			'commentId' => (string) $arguments['commentId'],
		];
		if (isset($arguments['commentId'])) {
			$extraFields['commentId'] = $arguments['commentId'];
		}
		self::getLogger()->log($message, [
			'file' => $path,
			'type' => $type,
			'commentId' => $arguments['commentId'],
		], $extraFields);
	}

	protected static function logUpdateComment($message, $arguments) {
		$view = Filesystem::getView();
		if ($view->filetype($view->getPath($arguments['objectId'])) === 'dir') {
			$type = "folder";
		} else {
			$type = "file";
		}
		$path = self::getAbsolutePath($arguments['objectId'], $view);
		self::getLogger()->log(
			$message,
			[
			'file' => $path,
			'type' => $type,
			'commentId' => $arguments['commentId']
			],
			[
				'action' => 'comment_updated',
				'fileId' => (string) $arguments['objectId'],
				'path' => $path,
				'commentId' => $arguments['commentId'],
			]
		);
	}

	/**
	 * @param int $id id of the comment
	 * @param View $view
	 * @return string|null the absolute path
	 */
	protected static function getAbsolutePath($id, View $view) {
		return $view->getAbsolutePath($view->getPath($id));
	}
}
