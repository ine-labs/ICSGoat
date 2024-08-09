<?php

namespace OCA\Graph\Service\Strategy;

use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;

class RegularUserStrategy {

	/**
	 * @var IUserManager
	 */
	private $userManager;
	/**
	 * @var IGroupManager
	 */
	private $groupManager;
	/**
	 * @var IUserSession
	 */
	private $userSession;

	public function __construct(IUserManager $userManager, IGroupManager $groupManager, IUserSession $userSession) {
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
	}

	public function listUsers($top, $skip): array {
		if ($skip !== 0) {
			return [];
		}
		return [$this->userSession->getUser()];
	}

	public function getUser($id): ?IUser {
		$user = $this->userManager->get($id);

		$groupsOfCurrentUser = \array_map(static function (IGroup $g) {
			return $g->getGID();
		}, $this->groupManager->getUserGroups($this->userSession->getUser()));
		$groupsOfQueriedUser = \array_map(static function (IGroup $g) {
			return $g->getGID();
		}, $this->groupManager->getUserGroups($user));

		$sharedGroups = \array_intersect($groupsOfCurrentUser, $groupsOfQueriedUser);
		if (\count($sharedGroups) > 0) {
			return $user;
		}
		return null;
	}
	public function listGroups($top, $skip): array {
		$groups = $this->groupManager->getUserGroups($this->userSession->getUser());
		return \array_splice($groups, $skip, $top);
	}

	public function getGroup(string $id): ?IGroup {
		$groups = $this->groupManager->getUserGroups($this->userSession->getUser());
		foreach ($groups as $group) {
			if ($group->getGID() === $id) {
				return $group;
			}
		}

		return null;
	}
}
