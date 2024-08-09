<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH
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

namespace OCA\CustomGroups\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IUser;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCA\CustomGroups\CustomGroupsDatabaseHandler;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\IGroupManager;
use OCP\IUserManager;

class PageController extends Controller {

	/**
	 * @var CustomGroupsDatabaseHandler
	 */
	private $handler;

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var IUserSession
	 */
	private $userSession;

	/**
	 * @var IGroupManager
	 */
	private $groupManager;

	/**
	 * @var IUserManager
	 */
	private $userManager;

	public function __construct(
		$appName,
		IRequest $request,
		IConfig $config,
		IUserSession $userSession,
		IUserManager $userManager,
		IGroupManager $groupManager,
		CustomGroupsDatabaseHandler $handler
	) {
		parent::__construct($appName, $request);
		$this->handler = $handler;
		$this->config = $config;
		$this->userSession = $userSession;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 */
	public function index() {
		// TODO: cache or add to info.xml ?
		$modules = \json_decode(\file_get_contents(__DIR__ . '/../../js/modules.json'));
		return new TemplateResponse($this->appName, 'index', [
			'modules' => $modules
		]);
	}

	/**
	 * Search in all groups the current user is member of
	 *
	 * @param string $customGroupId custom group id
	 * @param string $pattern lowercase pattern
	 * @param int $limit limit
	 * @param bool $exactMatch true to keep only exact matches, false otherwise
	 *
	 * @return IUser[] results
	 */
	private function searchByMembershipGroup($customGroupId, $pattern, $limit, $exactMatch) {
		$results = [];

		$customGroupMemberIds = \array_map(function ($entry) {
			return $entry['user_id'];
		}, $this->handler->getGroupMembers($customGroupId));

		$userGroups = $this->groupManager->getUserGroupIds($this->userSession->getUser());
		foreach ($userGroups as $userGroup) {
			$offset = 0;

			do {
				$usersTmp = $this->groupManager->findUsersInGroup($userGroup, $pattern, $limit, $offset);

				// merge and deduplicate relevant results
				foreach ($usersTmp as $user) {
					if (\count($results) >= $limit) {
						// shortcut
						break;
					}

					// filter out if no exact match, if required
					if ($exactMatch && !$this->isExactMatch($user, $pattern)) {
						continue;
					}

					$uid = $user->getUID();

					// filter out existing members
					if (\in_array($uid, $customGroupMemberIds)) {
						continue;
					}

					// deduplicate results using associative array
					$results[$uid] = $user;
				}
				$offset += \count($usersTmp);
			} while (\count($results) < $limit && \count($usersTmp) >= $limit);
		}

		return \array_values($results);
	}

	/**
	 * Check whether the given user matches the pattern exactly by checking
	 * user id, display name, email address and search term fields.
	 *
	 * @param IUser $user user object
	 * @param string $pattern lowercase pattern
	 *
	 * @return bool true if exact match, false otherwise
	 */
	private function isExactMatch($user, $pattern) {
		return
			// Check if the uid is the same
			\strtolower($user->getUID()) === $pattern
			// Check if exact display name
			|| \strtolower($user->getDisplayName()) === $pattern
			// Check if exact first email
			|| \strtolower($user->getEMailAddress()) === $pattern
			// Check for exact search term matches (when mail attributes configured as search terms + no enumeration)
			|| \in_array($pattern, \array_map('strtolower', $user->getSearchTerms()));
	}

	/**
	 * Return the user ids of the members in the given group matching the given pattern
	 *
	 * @param int $groupId numeric group id
	 * @return array array of user ids as keys and true as value
	 */
	private function getGroupMemberUserIds($groupId) {
		$foundMembers = $this->handler->getGroupMembers($groupId);
		$existingMembers = [];
		foreach ($foundMembers as $foundMember) {
			$existingMembers[$foundMember['user_id']] = true;
		}
		return $existingMembers;
	}

	/**
	 * Search for users that could be added as member of the given group.
	 * This searches the whole user list by display name and excludes the
	 * users that are already members of the given group.
	 *
	 * @param int $groupId numeric group id for which to find new members
	 * @param string $pattern lower case pattern to search for in display names
	 * @param int $limit limit up to which to return results
	 * @param bool $exactMatch true to keep only exact matches, false otherwise
	 * @return IUser[] results
	 */
	private function searchForNewMembers($groupId, $pattern, $limit, $exactMatch) {
		$existingMembers = $this->getGroupMemberUserIds($groupId);

		$totalResults = [];
		$totalResultCount = 0;

		$internalLimit = $limit;
		$internalOffset = 0;
		// loop until the $totalResults reaches $limit size or no more results exist
		do {
			$results = $this->userManager->find($pattern, $internalLimit, $internalOffset);
			foreach ($results as $result) {
				if ($totalResultCount >= $limit) {
					break;
				}

				// filter out if no exact match, if required
				if ($exactMatch && !$this->isExactMatch($result, $pattern)) {
					continue;
				}

				// skip if already in group
				if (isset($existingMembers[$result->getUID()])) {
					continue;
				}

				$totalResults[] = $result;
				$totalResultCount++;
			}
			$resultsCount = \count($results);
			$internalOffset += $resultsCount;
		} while ($totalResultCount < $limit && $resultsCount >= $internalLimit);

		return $totalResults;
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 */
	public function searchUsers($group, $pattern, $limit = 200) {
		$shareWithGroupOnly = $this->config->getAppValue('core', 'shareapi_only_share_with_group_members', 'no') === 'yes';
		$shareeEnumeration = $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes') === 'yes';
		if ($shareeEnumeration) {
			$shareeEnumerationGroupMembers = $this->config->getAppValue('core', 'shareapi_share_dialog_user_enumeration_group_members', 'no') === 'yes';
		} else {
			$shareeEnumerationGroupMembers = false;
		}

		$pattern = \strtolower($pattern);

		$groupInfo = $this->handler->getGroupByUri($group);
		if ($groupInfo === null) {
			return new DataResponse(
				[
					'message' => (string)$this->l10n->t('Group with uri "%s" not found', [$group])
				],
				Http::STATUS_NOT_FOUND
			);
		}

		$results = [];
		if ($shareWithGroupOnly || $shareeEnumerationGroupMembers) {
			$withoutEnumResult = [];
			$results = $this->searchByMembershipGroup($groupInfo['group_id'], $pattern, $limit, !$shareeEnumeration);
			/**
			 * If the results of above is not found then we need to check without enumeration
			 * that way we do not exclude the results of exact match.
			 */
			if (!isset($results[0]) && !$shareWithGroupOnly) {
				$withoutEnumResult = $this->searchForNewMembers($groupInfo['group_id'], $pattern, $limit, true);
			}
			$results = \array_merge($results, $withoutEnumResult);
		} else {
			$results = $this->searchForNewMembers($groupInfo['group_id'], $pattern, $limit, !$shareeEnumeration);
		}

		$results = \array_map(function (IUser $entry) {
			return [
				'userId' => $entry->getUID(),
				'displayName' => $entry->getDisplayName()
			];
		}, $results);

		return new DataResponse(['results' => $results], Http::STATUS_OK);
	}
}
