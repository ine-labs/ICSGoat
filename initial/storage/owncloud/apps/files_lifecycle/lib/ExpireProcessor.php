<?php
/**
 * ownCloud
 *
 * @author Tom Needham <tom@owncloud.com>
 * @author Michael Barz <mbarz@owncloud.com>
 * @copyright (C) 2018 ownCloud GmbH
 * @license ownCloud Commercial License
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see
 * <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Files_Lifecycle;

use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;

/**
 * Class ExpireProcessor
 *
 * @package OCA\Files_Lifecycle
 */
class ExpireProcessor {
	/**
	 * @var  IExpirer
	 */
	protected $expirer;

	/**
	 * @var ExpireQuery
	 */
	protected $query;

	/**
	 * @var  IUserManager
	 */
	protected $userManager;

	/**
	 * @var ILogger
	 */
	protected $logger;

	/**
	 * Expire Processor constructor.
	 *
	 * @param IExpirer $expirer
	 * @param ExpireQuery $expireQuery
	 * @param IUserManager $userManager
	 * @param ILogger $logger
	 */
	public function __construct(
		IExpirer $expirer,
		ExpireQuery $expireQuery,
		IUserManager $userManager,
		ILogger $logger
	) {
		$this->expirer = $expirer;
		$this->query = $expireQuery;
		$this->userManager = $userManager;
		$this->logger = $logger;
	}

	/**
	 * Expire Archive for all Users
	 *
	 * @param \Closure $closure
	 * @param bool $dryRun
	 *
	 * @return void
	 */
	public function expireAllUsers($closure, $dryRun = false) {
		$this->userManager->callForSeenUsers(
			function (IUser $user) use ($closure, $dryRun) {
				$this->expireForUser($user, $closure, $dryRun);
			}
		);
	}

	/**
	 * Expire All Files for a given USer
	 *
	 * @param IUser $user
	 * @param \Closure $closure
	 * @param bool $dryRun
	 *
	 * @return void
	 */
	public function expireForUser($user, $closure, $dryRun = false) {
		try {
			$this->expirer->expireForUser($user, $closure, $dryRun);
		} catch (\Exception $e) {
			// Something went wrong
			$this->logger->logException($e, ['app' => Application::APPID]);
			$closure->call($this);
		}
	}

	/**
	 * Expire a single file
	 *
	 * @param int $fileId
	 * @param IUser $user
	 *
	 * @return void
	 */
	public function expireFile($fileId, IUser $user) {
		$this->expirer->expireFile($fileId, $user);
	}
}
