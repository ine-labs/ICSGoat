<?php

namespace OCA\Graph\Service;

use OCA\Graph\Service\Strategy\AdminUserStrategy;
use OCA\Graph\Service\Strategy\RegularUserStrategy;
use OCP\IGroup;
use OCP\IUser;
use OCP\IUserSession;

class UsersService {

	/**
	 * @var IUserSession
	 */
	private $userSession;
	/**
	 * @var AdminUserStrategy
	 */
	private $adminUserStrategy;
	/**
	 * @var RegularUserStrategy
	 */
	private $regularUserStrategy;

	public function __construct(IUserSession $userSession, AdminUserStrategy $adminUserStrategy, RegularUserStrategy $regularUserStrategy) {
		$this->userSession = $userSession;
		$this->adminUserStrategy = $adminUserStrategy;
		$this->regularUserStrategy = $regularUserStrategy;
	}

	public function listUsers($top, $skip): array {
		$strategy = $this->getStrategy();
		return $strategy->listUsers($top, $skip);
	}

	public function getUser($id): ?IUser {
		$strategy = $this->getStrategy();
		return $strategy->getUser($id);
	}

	public function listGroups($top, $skip): array {
		$strategy = $this->getStrategy();
		return $strategy->listGroups($top, $skip);
	}

	public function getGroup(string $id): ?IGroup {
		$strategy = $this->getStrategy();
		return $strategy->getGroup($id);
	}

	private function getStrategy() {
		$currentUser = $this->userSession->getUser();
		if ($currentUser === null) {
			throw new \LogicException('Only authenticated requests are allowed');
		}
		if ($this->adminUserStrategy->isAdmin($currentUser)) {
			return $this->adminUserStrategy;
		}

		return $this->regularUserStrategy;
	}
}
