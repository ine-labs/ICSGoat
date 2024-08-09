<?php
/**
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
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

namespace OCA\windows_network_drive\lib\listener;

use OCA\windows_network_drive\lib\Helper;
use OCA\windows_network_drive\lib\notification_queue\NotificationQueueDBHandler;
use OCP\ILogger;

class ListenerCallbacks {
	// https://msdn.microsoft.com/en-us/library/dn392331.aspx
	public const NOTIFY_ADDED = 1;
	public const NOTIFY_REMOVED = 2;
	public const NOTIFY_MODIFIED = 3;
	public const NOTIFY_RENAME_OLD = 4;
	public const NOTIFY_RENAME_NEW = 5;
	public const NOTIFY_ADDED_STREAM = 6;
	public const NOTIFY_REMOVED_STREAM = 7;
	public const NOTIFY_MODIFIED_STREAM = 8;
	public const NOTIFY_REMOVED_BY_DELETE = 9;

	/**
	 * Map the notify code (constants above) to text
	 */
	private static $notifyMap = [
		self::NOTIFY_ADDED => "File added",
		self::NOTIFY_REMOVED => "File removed",
		self::NOTIFY_MODIFIED => "File modified",
		self::NOTIFY_RENAME_OLD => "File renamed : old name",
		self::NOTIFY_RENAME_NEW => "File renamed : new name",
		self::NOTIFY_ADDED_STREAM => "File added (stream)",
		self::NOTIFY_REMOVED_STREAM => "File removed (stream)",
		self::NOTIFY_MODIFIED_STREAM => "File modified (stream)",
		self::NOTIFY_REMOVED_BY_DELETE => "Object ID removed"
	];

	private $idleSleepTime = [
		'secs' => 0,
		'nanosecs' => 100 * 1000 * 1000,
	];  // 0.1 secs
	private $logger;
	private $queueHandler;
	private $helper;

	/** delete data that can be used for possible move operations */
	private $deleteDataInfo = null;

	public function __construct(NotificationQueueDBHandler $queueHandler, ILogger $logger, Helper $helper = null) {
		$this->logger = $logger;
		$this->queueHandler = $queueHandler;

		if ($helper === null) {
			$helper = new Helper();
		}
		$this->helper = $helper;
	}

	public function notifyCallback($changeType, $host, $share, $path) {
		static $oldRenamePath = null;
		static $newRenamePath = null;

		$globalLogger = $this->logger;

		if (!$this->queueHandler->conditionalDBReconnect()) {
			$globalLogger->critical('attempt to reconnect to the DB failed');
			return false;
		}

		// ignore first modify notifications if it matches the $newRenamePath
		if ($this->checkIfIgnore($changeType, $path, $newRenamePath)) {
			$globalLogger->info('[' . \time() . '] ' . self::$notifyMap[$changeType] . ' : ' . $path . ' (ignored)');
			$newRenamePath = null;
			return;
		} else {
			$newRenamePath = null;
		}

		switch ($changeType) {
			case self::NOTIFY_ADDED:
			case self::NOTIFY_ADDED_STREAM:
				if (($delayedDeleteInfo = $this->getDelayedDeleteInfo()) &&
						$delayedDeleteInfo['path'] !== $path &&
						\basename($delayedDeleteInfo['path']) === \basename($path)) {
					// if there is delete information consider this a move operation instead of
					// an add + delete one
					$this->queueHandler->insertRenameNotification($host, $share, $delayedDeleteInfo['path'], $path);
					$globalLogger->info('[' . \time() . '] File moved : ' . $delayedDeleteInfo['path'] . ' -> ' . $path . ' (delete + add)');
					$this->unsetDelayedDeleteInfo();
				} else {
					$this->insertDelayedDeleteNotification();
					$this->queueHandler->insertAddNotification($host, $share, $path);
					$globalLogger->info('[' . \time() . '] ' . self::$notifyMap[$changeType] . ' : ' . $path);
				}
				break;
			case self::NOTIFY_REMOVED:
			case self::NOTIFY_REMOVED_STREAM:
			case self::NOTIFY_REMOVED_BY_DELETE:
				$this->insertDelayedDeleteNotification();
				$this->delayDeleteNotification($changeType, $host, $share, $path);
				break;
			case self::NOTIFY_MODIFIED:
			case self::NOTIFY_MODIFIED_STREAM:
				$this->insertDelayedDeleteNotification();
				$this->queueHandler->insertModifyNotification($host, $share, $path);
				$globalLogger->info('[' . \time() . '] ' . self::$notifyMap[$changeType] . ' : ' . $path);
				break;
			case self::NOTIFY_RENAME_OLD:
				$this->insertDelayedDeleteNotification();
				$oldRenamePath = $path;
				$globalLogger->info('[' . \time() . '] ' . self::$notifyMap[$changeType] . ' : ' . $path);
				break;
			case self::NOTIFY_RENAME_NEW:
				$this->insertDelayedDeleteNotification();
				if ($oldRenamePath !== null) {
					$this->queueHandler->insertRenameNotification($host, $share, $oldRenamePath, $path);
					$globalLogger->info('[' . \time() . '] ' . self::$notifyMap[$changeType] . ' : ' . $path);
					if (!$this->isPartFile($oldRenamePath)) {
						// if the path isn't a part file...
						// insert an additional modify notification for the target path
						$this->queueHandler->insertForcedModifyNotification($host, $share, $path);
						$globalLogger->info('[' . \time() . '] ' . self::$notifyMap[self::NOTIFY_MODIFIED] . ' : ' . $path . ' (fake forced)');
						// forced modify fix issue with move to folder + rename folder (all in backend)
					}
					$oldRenamePath = null;
				} else {
					$globalLogger->debug('ignoring rename to ' . $path . ' : unknown original file');
				}
				$newRenamePath = $path;
				break;
		}
	}

	public function errorCallback($host, $share, $line) {
		// check if it has the NT_STATUS in the string, otherwise log it as debug just in case
		if (\strpos($line, 'NT_STATUS') !== false) {
			$this->logger->error($line);
		} else {
			$this->logger->debug($line);
		}
	}

	public function idleCallback() {
		if (($delayedDeleteInfo = $this->getDelayedDeleteInfo()) &&
				$this->helper->timeDifferenceGreaterThan($delayedDeleteInfo['timestamp'], 2)) {
			$this->insertDelayedDeleteNotification();
		} else {
			// sleep a bit to reduce the CPU load
			$this->helper->nanosleep($this->idleSleepTime['secs'], $this->idleSleepTime['nanosecs']);
		}
	}

	/**
	 * Return true if the path matches a part file, false otherwise
	 */
	private function isPartFile($path) {
		return \preg_match("#\.ocTransferId[^/]+\.part$#", $path) === 1;
	}

	/**
	 * Check if we should ignore the notification
	 * @param int $changeType one of the self::NOTIFY_* constants
	 * @param string $path the target path
	 * @param string $newRenamePath the new rename path from previous calls, or null if it doesn't apply
	 */
	private function checkIfIgnore($changeType, $path, $newRenamePath = null) {
		if ($path === $newRenamePath && ($changeType === self::NOTIFY_MODIFIED || $changeType === self::NOTIFY_MODIFIED_STREAM)) {
			return true;
		}

		$ignorePartFileNotificationList = [
			self::NOTIFY_ADDED,
			self::NOTIFY_ADDED_STREAM,
			self::NOTIFY_REMOVED,
			self::NOTIFY_REMOVED_STREAM,
			self::NOTIFY_REMOVED_BY_DELETE,
			self::NOTIFY_MODIFIED,
			self::NOTIFY_MODIFIED_STREAM,
		];
		// renames might need special treatment
		if ($this->isPartFile($path) && \in_array($changeType, $ignorePartFileNotificationList, true)) {
			return true;
		}

		return false;
	}

	private function delayDeleteNotification($changeType, $host, $share, $path) {
		$this->deleteDataInfo = [
			'changeType' => $changeType,
			'host' => $host,
			'share' => $share,
			'path' => $path,
			'timestamp' => \microtime(true),
		];
	}

	private function insertDelayedDeleteNotification() {
		if (isset($this->deleteDataInfo)) {
			$path = $this->deleteDataInfo['path'];
			$this->queueHandler->insertRemoveNotification(
				$this->deleteDataInfo['host'],
				$this->deleteDataInfo['share'],
				$path
			);
			$changeType = $this->deleteDataInfo['changeType'];
			$this->logger->info('[' . \time() . '] ' . self::$notifyMap[$changeType] . ' : ' . $path . ' (received at ' . $this->deleteDataInfo['timestamp'] . ')');
			$this->deleteDataInfo = null;
		}
	}

	private function getDelayedDeleteInfo() {
		return $this->deleteDataInfo;
	}

	private function unsetDelayedDeleteInfo() {
		$this->deleteDataInfo = null;
	}
}
