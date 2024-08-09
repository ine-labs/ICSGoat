<?php

namespace OCA\Graph\Service\Strategy;

use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;

class AdminUserStrategy {

	/**
	 * @var IUserManager
	 */
	private $userManager;
	/**
	 * @var IGroupManager
	 */
	private $groupManager;

	public function __construct(IUserManager $userManager, IGroupManager $groupManager) {
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
	}

	public function isAdmin(IUser $user): bool {
		return $this->groupManager->isAdmin($user->getUID());
	}

	public function listUsers($top, $skip): array {
		return $this->userManager->find('', $top, $skip);
	}

	public function getUser($id): ?IUser {
		return $this->userManager->get($id);
	}
	public function listGroups($top, $skip): array {
		return $this->groupManager->search('', $top, $skip);
	}

	public function getGroup(string $id): ?IGroup {
		return $this->groupManager->get($id);
	}
}
