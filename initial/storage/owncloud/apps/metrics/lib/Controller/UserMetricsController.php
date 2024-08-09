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

namespace OCA\Metrics\Controller;

use OC\OCS\Result;
use OCA\Metrics\Metrics\FilesMetrics;
use OCA\Metrics\Metrics\QuotaMetrics;
use OCA\Metrics\Metrics\SharesMetrics;
use OCA\Metrics\Metrics\UserActiveMetrics;
use OCA\Metrics\UserDataMetrics;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class UserMetricsController extends OCSController {
	/** @var UserActiveMetrics */
	private $userActiveMetrics;

	/** @var FilesMetrics */
	private $files;

	/** @var SharesMetrics */
	private $metricShares;

	/** @var QuotaMetrics */
	private $quotaMetrics;

	/** @var UserDataMetrics */
	private $userDataMetrics;

	/**
	 * UserMetricsController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param UserActiveMetrics $userActiveMetrics
	 * @param FilesMetrics $files
	 * @param SharesMetrics $metricShares
	 * @param QuotaMetrics $quotaMetrics
	 * @param UserDataMetrics $userDataMetrics
	 */
	public function __construct(
		$appName,
		IRequest $request,
		UserActiveMetrics $userActiveMetrics,
		FilesMetrics $files,
		SharesMetrics $metricShares,
		QuotaMetrics $quotaMetrics,
		UserDataMetrics $userDataMetrics
	) {
		parent::__construct($appName, $request);
		$this->request = $request;
		$this->userActiveMetrics = $userActiveMetrics;
		$this->files = $files;
		$this->metricShares = $metricShares;
		$this->quotaMetrics = $quotaMetrics;
		$this->userDataMetrics = $userDataMetrics;
	}

	/**
	 * Provides metrics of oC instance
	 *
	 * @param string $files If files information should be included in the response
	 * @param string $shares If shares information should be included in the response
	 * @param string $quota If quota information should be included in the response
	 * @param string $users If session information should be included in the response
	 * @param string $userData If actual user data should be included in the response
	 *
	 * @PublicPage
	 * @NoCSRFRequired
	 * @SharedSecretRequired
	 *
	 * @return array|Result
	 */
	public function getMetrics($files, $shares, $quota, $users, $userData) {
		$includeFilesInformation = $files === 'true';
		$includeSharesInformation = $shares === 'true';
		$includeQuotaInformation = $quota === 'true';
		$includeUserInformation = $users === 'true';
		$includePerUserMetrics = $userData === 'true';

		$result = [];

		/**
		 * Fetch the current timestamp
		 */
		$result['timeStamp'] = $this->userActiveMetrics->getTimeStamp();

		/**
		 * Fetches the total users and the users with active sessions.
		 */
		if ($includeUserInformation) {
			$result['users']['totalCount'] = $this->userActiveMetrics->getTotalUserCount();
			$result['users']['activeUsersCount'] = $this->userActiveMetrics->getCurrentActiveUsers();
			$result['users']['concurrentUsersCount'] = $this->userActiveMetrics->getConcurrentUsers();
		}

		/**
		 * Fetches the total files from the filecache
		 */
		if ($includeFilesInformation) {
			$fileCountDetails = $this->files->getTotalFilesCount();
			$result['files']['totalFilesCount'] = (int)$fileCountDetails['totalFiles'];
			$result['files']['storage'] = $this->quotaMetrics->getTotalQuotaUsage(null);
		}

		/**
		 * Fetches the shares, like total shares, total user shares, total group
		 * shares, total link shares, total guest share count
		 */
		if ($includeSharesInformation) {
			$result['shares'] = $this->metricShares->getTotalShares();
		}

		/**
		 * Fetches the user data from the user
		 * - shares, which include user, group, link and guest shares
		 * - quota, total usage and available quota.
		 * - files, total files which user have from the filecache.
		 */
		if ($includePerUserMetrics) {
			$result['userData'] = $this->userDataMetrics->getUserData(
				$includeFilesInformation,
				$includeSharesInformation,
				$includeQuotaInformation,
				$includeUserInformation
			);
		}

		return ['data' => $result];
	}
}
