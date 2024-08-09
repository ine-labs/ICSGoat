<?php
/**
 * ownCloud
 *
 * @author Tom Needham <tom@owncloud.com>
 * @copyright (C) 2019 ownCloud GmbH
 * @license ownCloud Commercial License
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see
 * <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Files_Lifecycle\Policy;

use OCA\Files_Lifecycle\Application;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IUser;

/**
 * Class SoftPolicy
 *
 * @package OCA\Files_Lifecycle\Policy
 */
class SoftPolicy implements IPolicy {
	public const POLICY_NAME = 'soft';

	/**
	 * @var IConfig
	 */
	protected $config;

	/**
	 * @var int
	 */
	protected $archivePeriod;

	/**
	 * @var int
	 */
	protected $expirePeriod;

	/**
	 * @var bool
	 */
	protected $userCanRestore;

	/**
	 * @var IGroupManager
	 */
	protected $groupManager;

	/**
	 * @var boolean
	 */
	protected $loadedGroups;

	/**
	 * @var IGroup[]
	 */
	protected $excludedGroups;

	/**
	 * @var ILogger
	 */
	protected $logger;

	/**
	 * HardPolicy constructor.
	 *
	 * @param IConfig $config
	 * @param IGroupManager $groupManager
	 * @param ILogger $logger
	 *
	 */
	public function __construct(
		IConfig $config,
		IGroupManager $groupManager,
		ILogger $logger
	) {
		$this->config = $config;
		$this->groupManager = $groupManager;
		$this->logger = $logger;
		$this->archivePeriod = (int) $this->config->getAppValue(
			'files_lifecycle',
			'archive_period',
			'100'
		);
		$this->expirePeriod = (int) $this->config->getAppValue(
			'files_lifecycle',
			'expire_period',
			'100'
		);
	}

	/**
	 * In soft policy mode all users can restore their own files
	 *
	 * @param IUser $user
	 *
	 * @return true
	 */
	public function userCanRestore(IUser $user) {
		return true;
	}

	/**
	 * Impersonators can restore files for other users if they wish
	 *
	 * @return bool
	 */
	public function impersonatorCanRestore() {
		return true;
	}
	/**
	 * Get Archive Period
	 *
	 * @return int days
	 */
	public function getArchivePeriod() {
		return $this->archivePeriod;
	}

	/**
	 * Get Archive Period
	 *
	 * @return int days
	 */
	public function getExpirePeriod() {
		return $this->expirePeriod;
	}

	/**
	 * @param IUser $user
	 *
	 * @return bool
	 */
	public function userExemptFromArchiving(IUser $user) {
		$this->loadExcludedGroups();
		if (empty($this->excludedGroups)) {
			return false;
		}
		$usersGroups = $this->groupManager->getUserGroupIds($user);
		$excludedGroupIds = \array_map(
			function (IGroup $group) {
				return $group->getGID();
			},
			$this->excludedGroups
		);
		$matches = \array_intersect($usersGroups, $excludedGroupIds);
		return \count($matches) > 0;
	}

	/**
	 * @return void
	 */
	protected function loadExcludedGroups() {
		if ($this->loadedGroups) {
			return;
		}
		$excludedGroupIds = $this->config->getAppValue(
			Application::APPID,
			'excluded_groups',
			''
		);
		if (!empty($excludedGroupIds)) {
			$excludedGroupIds = \explode(',', $excludedGroupIds); // TODO handle comma in group name
		} else {
			$excludedGroupIds = [];
		}
		$excludedGroups = [];
		foreach ($excludedGroupIds as $group) {
			if (\trim($group) === '') {
				continue;
			}
			$g = $this->groupManager->get($group);
			if ($g !== null) {
				$excludedGroups[] = $g;
			} else {
				$this->logger->warning("Lifecycle excluded_groups config parameter contains a group that is not found: $group", ['app' => Application::APPID]);
			}
		}
		$this->excludedGroups = $excludedGroups;
		$this->loadedGroups = true;
	}
}
