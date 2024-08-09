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

use OCA\Files_Lifecycle\Policy\IPolicy;
use OCP\Files\File;
use OCP\Files\NotFoundException;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;

/**
 * Class ArchiveProcessor
 *
 * @package OCA\Files_Lifecycle
 */
class ArchiveProcessor {

	/**
	 * @var  IArchiver
	 */
	protected $archiver;

	/**
	 * @var ArchiveQuery
	 */
	protected $query;

	/**
	 * @var  IUserManager
	 */
	protected $userManager;

	/**
	 * @var  ILogger
	 */
	protected $logger;

	/**
	 * @var IPolicy
	 */
	protected $policy;

	/**
	 * ArchiveProcessor constructor.
	 *
	 * @param IArchiver $archiver
	 * @param IUserManager $userManager
	 * @param ILogger $logger
	 * @param IPolicy $policy
	 */
	public function __construct(
		IArchiver $archiver,
		IUserManager $userManager,
		ILogger $logger,
		IPolicy $policy
	) {
		$this->archiver = $archiver;
		$this->logger = $logger;
		$this->userManager = $userManager;
		$this->policy = $policy;
	}

	/**
	 * Archive files for all users
	 *
	 * @param \Closure $closure
	 * @param bool $dryRun
	 *
	 * @return void
	 */
	public function archiveAllUsers(\Closure $closure, $dryRun = false) {
		// Call for all seen users
		$this->userManager->callForSeenUsers(
			function (IUser $user) use ($closure, $dryRun) {
				if (!$this->policy->userExemptFromArchiving($user)) {
					$this->archiveForUser($user, $closure, $dryRun);
				} else {
					// Trigger the callback at least
					\call_user_func($closure, 'Skipping user '.$user->getUID() . ' as they are except due to the policy.');
					$id = $user->getUID();
					$this->logger->info("Skipping user $id as they are a member of an excluded group", ['app' => Application::APPID]);
				}
			}
		);
	}

	/**
	 * Archive files for a single user
	 *
	 * @param IUser $user
	 * @param \Closure $closure
	 * @param bool $dryRun
	 *
	 * @return void
	 */
	public function archiveForUser(IUser $user, \Closure $closure, $dryRun = false) {
		if ($this->policy->userExemptFromArchiving($user)) {
			$id = $user->getUID();
			$this->logger->info("Not archiving file as owner $id is a member of an excluded group", ['app' => Application::APPID]);
			return;
		}

		try {
			$this->archiver->archiveForUser($user, $closure, $dryRun);
		} catch (NotFoundException $e) {
			$this->logger->logException($e, ['app' => Application::APPID]);
			$closure->call($this);
		} catch (\Exception $e) {
			// Something went wrong
			$this->logger->logException($e, ['app' => Application::APPID]);
			$closure->call($this);
		}
	}

	/**
	 * Archive single file
	 *
	 * @param File $file
	 * @param IUser $user
	 *
	 * @return void
	 */
	public function archiveFile(File $file, IUser $user) {
		// Check if the user is excluded
		if ($this->policy->userExemptFromArchiving($user)) {
			$id = $user->getUID();
			$this->logger->info("Not archiving file as owner $id is a member of an excluded group", ['app' => Application::APPID]);
			return;
		}
		$this->archiver->moveFile2Archive($file, $user);
	}
}
