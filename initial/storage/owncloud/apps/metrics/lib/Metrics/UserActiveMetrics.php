<?php
/**
 * @author Sujith Haridasan <sharidasan@owncloud.com>
 * @copyright (C) 2019 ownCloud GmbH
 * @license ownCloud Commercial License
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Metrics\Metrics;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;

class UserActiveMetrics {
	/** @var ITimeFactory */
	private $timeFactory;

	/** @var IDBConnection */
	private $connection;

	/** @var IUserManager */
	private $userManager;

	/** @var IConfig */
	private $config;

	/** @var int */
	public const TWO_WEEKS_AS_SECONDS = (14 * 24 * 60 * 60);

	/**
	 * UserActiveMetrics constructor.
	 *
	 * @param ITimeFactory $timeFactory
	 * @param IDBConnection $connection
	 * @param IUserManager $userManager
	 * @param IConfig $config
	 */
	public function __construct(
		ITimeFactory $timeFactory,
		IDBConnection $connection,
		IUserManager $userManager,
		IConfig $config
	) {
		$this->timeFactory = $timeFactory;
		$this->connection = $connection;
		$this->userManager = $userManager;
		$this->config = $config;
	}

	/**
	 * Returns current timesamp
	 *
	 * @return int
	 */
	public function getTimeStamp() {
		return $this->timeFactory->getTime();
	}

	/**
	 * Get the total users in the oC instance
	 *
	 * @return int
	 */
	public function getTotalUserCount() {
		return \count($this->userManager->search(''));
	}

	/**
	 * Get the count of users who have active sessions
	 *
	 * @return int active users count
	 */
	public function getCurrentActiveUsers() {
		$activeUsers = 0;
		$currentTime = $this->timeFactory->getTime();
		// User is considered as active if they logged in within 2 weeks
		$activeTimeLimit = $currentTime - self::TWO_WEEKS_AS_SECONDS;
		$this->userManager->callForAllUsers(function (IUser $user) use (&$activeUsers, $activeTimeLimit) {
			$lastLogin = $user->getLastLogin();
			// if user is logged in within last 2 weeks then its an active user
			if ($lastLogin >= $activeTimeLimit) {
				$activeUsers++;
			}
		});

		return $activeUsers;
	}

	/**
	 * Gets the user agent who have connected to the instance as $user
	 * Provides minor detail about the connection like browser, OS etc
	 *
	 * @param IUser $user
	 * @return array
	 */
	public function getActiveSessionsForUser(IUser $user) {
		$qb = $this->connection->getQueryBuilder();

		$qb->selectAlias('name', 'agentName')
			->from('authtoken')
			->where($qb->expr()->eq('uid', $qb->expr()->literal($user->getUID())));
		$activeSessionStatement = $qb->execute();
		/* @phan-suppress-next-line PhanDeprecatedFunction */
		$activeUserSessions = $activeSessionStatement->fetchAll();
		/* @phan-suppress-next-line PhanDeprecatedFunction */
		$activeSessionStatement->closeCursor();

		return $activeUserSessions;
	}

	/**
	 * Returns the number of active sessions of the provided $user.
	 *
	 * @param IUser $user
	 * @return int
	 */
	public function countActiveSessionsForUser(IUser $user) {
		return \count($this->getActiveSessionsForUser($user));
	}

	/**
	 * Gets the timestamp of the last login of the given $user.
	 *
	 * @param IUser $user
	 * @return int
	 */
	public function getLastLoginForUser(IUser $user) {
		return $user->getLastLogin();
	}

	/**
	 * Gives the total users who have at least one active session
	 *
	 * @return int currently logged in users count
	 */
	public function getConcurrentUsers() {
		$qb = $this->connection->getQueryBuilder();

		/**
		 * Expectation here is authtoken will have the list of users had
		 * connected in the past or still connected. The SQL query used is of
		 * the form:
		 * select DISTINCT uid from oc_authtoken;
		 *
		 */
		$qb->selectDistinct('uid')
			->from('authtoken');

		$concurrentUsersStatement = $qb->execute();
		/* @phan-suppress-next-line PhanDeprecatedFunction */
		$result = $concurrentUsersStatement->fetchAll();
		/* @phan-suppress-next-line PhanDeprecatedFunction */
		$concurrentUsersStatement->closeCursor();

		return \count($result);
	}
}
