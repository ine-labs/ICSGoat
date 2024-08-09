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

namespace OCA\Metrics;

use OCA\Metrics\Metrics\FilesMetrics;
use OCA\Metrics\Metrics\QuotaMetrics;
use OCA\Metrics\Metrics\SharesMetrics;
use OCA\Metrics\Metrics\UserActiveMetrics;
use OCP\IUser;
use OCP\IUserManager;

class UserDataMetrics {
	/** @var FilesMetrics */
	private $filesMetrics;

	/** @var SharesMetrics */
	private $sharesMetrics;

	/** @var QuotaMetrics */
	private $quotaMetrics;

	/** @var IUserManager */
	private $userManager;

	/** @var UserActiveMetrics */
	private $userActiveMetrics;

	/** @var Helper */
	private $helper;

	/**
	 * UserDataMetrics constructor.
	 *
	 * @param FilesMetrics $filesMetrics
	 * @param SharesMetrics $sharesMetrics
	 * @param QuotaMetrics $quotaMetrics
	 * @param IUserManager $userManager
	 * @param UserActiveMetrics $userActiveMetrics
	 * @param Helper $helper
	 */
	public function __construct(
		FilesMetrics $filesMetrics,
		SharesMetrics $sharesMetrics,
		QuotaMetrics $quotaMetrics,
		IUserManager $userManager,
		UserActiveMetrics $userActiveMetrics,
		Helper $helper
	) {
		$this->userManager = $userManager;
		$this->filesMetrics = $filesMetrics;
		$this->sharesMetrics = $sharesMetrics;
		$this->quotaMetrics = $quotaMetrics;
		$this->userActiveMetrics = $userActiveMetrics;
		$this->helper = $helper;
	}

	/**
	 * Gets the data of shares, files, quota of each user
	 *
	 * @param bool $includeFiles If files information should be included in the response
	 * @param bool $includeShares If shares information should be included in the response
	 * @param bool $includeQuota If quota information should be included in the response
	 * @param bool $includeUserInfo If user metadata should be included in the response
	 * @return array the array of user data
	 */
	public function getUserData($includeFiles, $includeShares, $includeQuota, $includeUserInfo) {
		$result = [];

		$this->userManager->callForAllUsers(function (IUser $user) use (&$result, $includeFiles, $includeShares, $includeQuota, $includeUserInfo) {
			//Get the display name of user
			$result[$user->getUID()]['displayName'] = $user->getDisplayName();
			$result[$user->getUID()]['backend'] = $user->getBackendClassName();

			if ($this->helper->isGuestUser($user->getUID())) {
				$result[$user->getUID()]['backend'] .= ' (Guest)';
			}

			//Get the total files count for the user
			if ($includeFiles) {
				$result[$user->getUID()]['files'] = $this->filesMetrics->getTotalFilesCount($user);
			}

			//Get the shares count for the user
			if ($includeShares) {
				$result[$user->getUID()]['shares'] = $this->sharesMetrics->getTotalShares($user);
			}

			//Get the quota for the user
			if ($includeQuota) {
				$result[$user->getUID()]['quota'] = $this->quotaMetrics->getTotalQuotaUsage($user);
			}

			//Get the agents connected with the instance.
			if ($includeUserInfo) {
				$result[$user->getUID()]['activeSessions'] = $this->userActiveMetrics->getActiveSessionsForUser($user);
				$result[$user->getUID()]['lastLogin'] = $this->userActiveMetrics->getLastLoginForUser($user);
			}
		});

		return $result;
	}
}
