<?php
/**
 * ownCloud
 *
 * @author Tom Needham <tom@owncloud.com>
 * @copyright (C) 2018 ownCloud Gmbh
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Admin_Audit;

use OC\Files\Filesystem;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;

class Helper {

	/** @var IUserSession  */
	protected $userSession;
	/** @var IGroupManager  */
	protected $groupManager;
	/** @var IConfig  */
	protected $config;
	/** @var ILogger */
	protected $logger;
	/** @var IUserManager  */
	protected $userManager;

	public function __construct(
		IUserSession $userSession,
		IGroupManager $groupManager,
		IConfig $config,
		ILogger $logger,
		IUserManager $userManager
	) {
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
		$this->config = $config;
		$this->logger = $logger;
		$this->userManager = $userManager;
	}

	/**
	 * Gets possible file or share owner given a path
	 * @param string $path
	 * @param string UID
	 */
	public static function getOwner($path) {
		if (Filesystem::getRoot() === null) {
			// Filesystem is not initialized and we can't use it for the owner detection
			return false;
		}

		$owner = false;
		try {
			$owner = Filesystem::getOwner($path);
		} catch (NotFoundException $e) {
			// Ignore and keep trying
		}
		// Catch when FS class can't find owner
		if (!\is_string($owner)) {
			// Now try to get the owner based on the path of the file on the FS
			\OC::$server->getLogger()->debug("Trying to determine owner for path $path", ['app' => __CLASS__]);
			$root = Filesystem::getRoot();
			list(, $user) = \explode('/', $root);
			\OC::$server->getLogger()->debug("Using $user as owner for $path based on path", ['app' => __CLASS__]);
			$owner = $user;
		}
		return $owner;
	}

	/**
	 * Returns the extra fields array enhanced with correct audit context fields
	 * @param $extraFields
	 * @return array
	 */
	public function handleAuditContext($extraFields) {
		// Include the filtered audit groups if we have a user and groups configured
		$extraFields['auditGroups'] = [];
		$extraFields['auditUsers'] = [];

		$auditContextUsers = [];
		if ($this->userSession->getUser() instanceof IUser) {
			$auditContextUsers[] = $this->userSession->getUser()->getUID();
		}

		// If `owner` is present, include
		if (isset($extraFields['owner'])) {
			$auditContextUsers[] = $extraFields['owner'];
		}

		// If targetUser is present, include
		if (isset($extraFields['targetUser'])) {
			$auditContextUsers[] = $extraFields['targetUser'];
		}

		// Assign the users and groups to the message
		$extraFields['auditUsers'] = \array_unique($auditContextUsers);
		if (isset($extraFields['action']) && ($extraFields['action'] === 'user_deleted')) {
			$extraFields['auditGroups'] = [];
		} else {
			$extraFields['auditGroups'] = $this->getAuditGroups(\array_unique($auditContextUsers));
		}
		return $extraFields;
	}

	/**
	 * Returns the filtered list of groups that the specified user is a member of
	 * @param string[] $userID
	 * @return string[] Array of GIDs
	 */
	public function getAuditGroups($users) {
		// Get the configured audit group filter
		$filter = $this->config->getSystemValue('admin_audit.groups', []);
		$groups = [];

		// Try to get the user and groups
		foreach ($users as $uid) {
			$user = $this->userManager->get($uid);
			if ($user === null) {
				$this->logger->error("Cannot get filtered audit group list for user $uid", ['app' => 'admin_audit']);
				continue;
			}
			// Get the groups they are a member of
			$groups = \array_merge(
				$groups,
				$this->groupManager->getUserGroupIds($user)
			);
		}

		$groups = \array_unique($groups);

		if (!\is_array($filter) || empty($filter)) {
			// return unfiltered list.
			return \array_values($groups);
		}

		// Return the filtered list of groups in the context
		return \array_values(\array_intersect($filter, $groups));
	}
}
