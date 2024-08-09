<?php
/**
 * ownCloud
 *
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 * @copyright Copyright (c) 2016, ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\windows_network_drive\lib\notification_queue;

class NotificationQueueProcessor {
	public function __construct() {
	}
	/**
	 * The $notificationList is an array of actions, with the following structure
	 * [
	 *  ['action' => 'add', 'parameters' => ['smb://server/share/path/to/file']],
	 *  ['action' => 'modify', 'parameters' => ['smb://server/share/path/dir']],
	 *  ['action' => 'modify', 'parameters' => ['smb://server/share/path/file4']],
	 *  ['action' => 'rename', 'parameters' => ['smb://server/share/file4', 'smb://server/share/a']],
	 *  ['action' => 'modify', 'parameters' => ['smb://server/share/path/to/file']],
	 *  ['action' => 'modify', 'parameters' => ['smb://server/share/path/dir']],
	 *  ['action' => 'remove', 'parameters' => ['smb://server/share/path/file4']],
	 * ]
	 *
	 * That list of notifications will be squashed according to several rules:
	 * 1. List will be splitted with "rename" operation acting as separator
	 * 2. Foreach of the pieces:
	 *   1. If there is a "remove" action, any previous "add" or "modify" action targeting the same file will be excluded from the list
	 *   2. Any "add" or "remove" action will be converted to a "modify" action targeting the parent folder (it's expected the parent folder will be scanned reveling those changes)
	 *   3. Duplicated notifications will be removed taking the first one found and removing the rest (duplication might happen with step 2.2)
	 * 3. Remove modifications of files (or folders) that will be renamed. Modifications after the rename will be kept.
	 * 4. Flatten the lists so we get a list of notifications similar to the input
	 * 5. Deduplicate again the list taking the last one and removing the rest (step 2.3 squashed the splitted lists but there might be duplicated notifications among them)
	 *
	 * NOTE: Remove step 2.3 and rely on 5?
	 * NOTE2: Neither performance nor memory usage have been considered yet. There might be problems with long lists
	 *
	 * The "rename" action will act as separator. "add" and "remove" actions will change to "modify"
	 * actions to the parent folder. Duplicated actions targeting the same path will be removed in
	 * each array, but not globally (the same file might be modified before and after a rename)
	 *
	 * As a reason to use use the rename operation as a splitter, the rename operation will involve
	 * a move operation in the cache, so the source file must be in the cache. An action sequence such
	 * as "add" -> "rename" -> "add" -> "remove" could be problematic otherwise.
	 *
	 * .part file uploads will be considered here as another normal file to avoid a dependency with
	 * the scanner.
	 */
	public function extractFilesToBeScanned($notificationList) {
		if (empty($notificationList)) {
			// if the notification list is empty, return an empty result directly.
			return [];
		}

		$result = [];
		$splittedList = $this->splitListRenamedBased($notificationList);
		foreach ($splittedList as $splittedItem) {
			// check the first element of the list to determine of it's a rename action or not
			if ($splittedItem[0]['action'] !== 'rename') {
				$list = $this->processRemoveAction($splittedItem);
				$list = $this->changeAddAndRemoveToModify($list);
				$list = $this->squashFileBased($list);
				$result[] = $list;
			} else {
				$result[] = $splittedItem;
			}
		}
		$result = $this->removedRenamedModifications($result);
		$result = $this->flatten($result);
		$result = $this->reversedSquashFileBased($result);
		return $result;
	}

	/**
	 * notificationList should contain only add, remove and modify operations for this method.
	 * Rename operations shouldn't be in the list
	 */
	private function processRemoveAction($notificationList) {
		// process the list backwards to ignore notifications affected by the remove action
		$reversedList = \array_reverse($notificationList);
		$deletedFiles = [];
		$reversedResult = [];
		foreach ($reversedList as $notification) {
			if ($notification['action'] !== 'remove') {
				if (!isset($deletedFiles[$notification['parameters'][0]])) {
					// add the notification to the list if the file hasn't been marked
					$reversedResult[] = $notification;
				}
			} else {
				// add the notification and mark the file
				$reversedResult[] = $notification;
				$deletedFiles[$notification['parameters'][0]] = true;
			}
		}
		// return the final list in the correct order
		return \array_reverse($reversedResult);
	}

	/**
	 * Change the "add" notifications to "modify" ones targeting the parent folder
	 * The "remove" actions be changed to "modify", but the target will remain the same
	 */
	private function changeAddAndRemoveToModify($notificationList) {
		return \array_map(function ($value) {
			if ($value['action'] === 'add') {
				$value['action'] = 'modify';
				$parentDir = \dirname($value['parameters'][0]);
				if ($parentDir === '.') {
					// special case for dirname('file') -> it must be '' instead of '.'
					$parentDir = '';
				}
				$value['parameters'] = [$parentDir];
				return $value;
			} elseif ($value['action'] === 'remove') {
				$value['action'] = 'modify';
				return $value;
			} else {
				return $value;
			}
		}, $notificationList);
	}

	private function squashFileBased($notificationList) {
		$result = [];
		foreach ($notificationList as $notification) {
			// expect array equality comparison here
			if (!\in_array($notification, $result)) {
				$result[] = $notification;
			}
		}
		return $result;
	}

	private function removedRenamedModifications($groupedNotificationList) {
		// process the list backwards to ignore notifications affected by the remove action
		$groupedReversedList = \array_reverse($groupedNotificationList);
		$renamedFiles = [];
		$reversedResult = [];
		foreach ($groupedReversedList as $notificationGroup) {
			if ($notificationGroup[0]['action'] === 'rename') {
				// if the first action in the group is a rename, extract the info
				$renamedFiles[$notificationGroup[0]['parameters'][0]] = true;
				$reversedResult[] = $notificationGroup;
			} else {
				// need to remove any "modify" action targeting the renameSrc file
				if (!empty($renamedFiles)) {
					$cleanedGroup = [];
					foreach ($notificationGroup as $notification) {
						if ($notification['action'] === 'modify' || $notification['action'] === 'forced_modify') {
							if (!isset($renamedFiles[$notification['parameters'][0]])) {
								// add the notification if the modified file hasn't been renamed
								$cleanedGroup[] = $notification;
							}
						} else {
							$cleanedGroup[] = $notification;
						}
					}
					$reversedResult[] = $cleanedGroup;
				} else {
					// no rename action known => don't touch the group
					$reversedResult[] = $notificationGroup;
				}
			}
		}
		// return the final list in the correct order
		return \array_reverse($reversedResult);
	}

	private function flatten($groupedNotificationList) {
		return \call_user_func_array('array_merge', $groupedNotificationList);
	}

	/**
	 * Same as squashFileBased but we choose the last notification in the list instead of the first
	 */
	private function reversedSquashFileBased($notificationList) {
		$result = [];
		$needToReindex = false;
		foreach ($notificationList as $notification) {
			// look for the notification in the result
			$resultIndex = \array_search($notification, $result);
			if ($resultIndex !== false) {
				unset($result[$resultIndex]);
				$needToReindex = true;
			}
			$result[] = $notification;
		}
		// reindex the array
		if ($needToReindex) {
			$result = \array_values($result);
		}

		return $result;
	}

	/**
	 * Split the list into several list using a rename action as separator. The rename action will be
	 * included in the result: [[actions_before_rename],[rename],[actions_after_rename]]
	 * Some possiblities:
	 * - [[actions_before_rename],[rename],[actions_after_rename]]
	 * - [[actions_before_rename],[rename],[actions_between_renames],[rename],[actions_after_rename]]
	 * - [[actions_before_rename],[rename]]
	 * - [[rename],[actions_after_rename]]
	 * - []
	 */
	private function splitListRenamedBased($notificationList) {
		$result = [];
		$bag = [];
		foreach ($notificationList as $notification) {
			if ($notification['action'] !== 'rename') {
				$bag[] = $notification;
			} else {
				// store the bag in the result, store the rename and create a new bag
				if (!empty($bag)) {
					$result[] = $bag;
				}
				$result[] = [$notification];
				$bag = [];
			}
		}
		// store the last bag in the result
		if (!empty($bag)) {
			$result[] = $bag;
		}
		return $result;
	}
}
