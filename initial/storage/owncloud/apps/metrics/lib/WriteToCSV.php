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
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IDateTimeFormatter;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class WriteToCSV {
	/**
	 * @var IUserManager
	 */
	private $userManager;
	/**
	 * @var UserActiveMetrics
	 */
	private $userActiveMetrics;
	/**
	 * @var FilesMetrics
	 */
	private $filesMetrics;
	/**
	 * @var SharesMetrics
	 */
	private $sharesMetrics;
	/**
	 * @var QuotaMetrics
	 */
	private $quotaMetrics;
	/**
	 * @var CsvEncoder
	 */
	private $csvEncoder;
	/**
	 * @var ITimeFactory
	 */
	private $timeFactory;
	/**
	 * @var IDateTimeFormatter
	 */
	private $dateTimeFormatter;

	/**
	 * WriteToCSV constructor.
	 *
	 * @param IUserManager $userManager
	 * @param UserActiveMetrics $userActiveMetrics
	 * @param FilesMetrics $filesMetrics
	 * @param SharesMetrics $sharesMetrics
	 * @param QuotaMetrics $quotaMetrics
	 * @param CsvEncoder $csvEncoder
	 * @param ITimeFactory $timeFactory
	 * @param IDateTimeFormatter $dateTimeFormatter
	 */
	public function __construct(
		IUserManager $userManager,
		UserActiveMetrics $userActiveMetrics,
		FilesMetrics $filesMetrics,
		SharesMetrics $sharesMetrics,
		QuotaMetrics $quotaMetrics,
		CsvEncoder $csvEncoder,
		ITimeFactory $timeFactory,
		IDateTimeFormatter $dateTimeFormatter
	) {
		$this->userManager = $userManager;
		$this->userActiveMetrics = $userActiveMetrics;
		$this->filesMetrics = $filesMetrics;
		$this->sharesMetrics = $sharesMetrics;
		$this->quotaMetrics = $quotaMetrics;
		$this->csvEncoder = $csvEncoder;
		$this->dateTimeFormatter = $dateTimeFormatter;
		$this->timeFactory = $timeFactory;
	}

	/**
	 * Get the user metrics data written in cvs file format
	 * This method as of now get the quota from the user data
	 * metrics.
	 *
	 * @return string, if csv data is retrieved then its returned as string else false.
	 */
	public function getUsersCSVData() {
		$result = [];
		$this->userManager->callForAllUsers(function (IUser $user) use (&$result) {
			$metricsSerializer = new MetricsSerializer();
			// user info
			$metricsSerializer->setUserId($user->getUID());
			$metricsSerializer->setUserDisplayName($user->getDisplayName());
			$metricsSerializer->setLastLogin($this->userActiveMetrics->getLastLoginForUser($user));
			$metricsSerializer->setSessions($this->userActiveMetrics->countActiveSessionsForUser($user));
			// quota
			$quotaResult = $this->quotaMetrics->getTotalQuotaUsage($user);
			$metricsSerializer->setQuotaUsed($quotaResult['used']);
			$metricsSerializer->setQuotaFree($quotaResult['free']);
			$metricsSerializer->setQuotaTotal($quotaResult['total']);
			// files
			$filesResult = $this->filesMetrics->getTotalFilesCount($user);
			$metricsSerializer->setFiles($filesResult['totalFiles']);
			// shares
			$sharesResult = $this->sharesMetrics->getTotalShares($user);
			$metricsSerializer->setSharesUser($sharesResult['userShareCount']);
			$metricsSerializer->setSharesGroup($sharesResult['groupShareCount']);
			$metricsSerializer->setSharesLink($sharesResult['linkShareCount']);
			$metricsSerializer->setSharesGuest($sharesResult['guestShareCount']);
			$metricsSerializer->setSharesFederated($sharesResult['federatedShareCount']);
			// append user row
			$result[] = $metricsSerializer;
		});

		$encoder = [$this->csvEncoder];
		/**
		 * Couldn't inject ObjectNormalizer, an error was thrown. Hence initialized
		 * here.
		 */
		$normalizer = [new ObjectNormalizer()];

		$serializer = new Serializer($normalizer, $encoder);
		return $serializer->serialize($result, 'csv');
	}

	/**
	 * Get the name of the attach file
	 *
	 * @return string returns the name of the file which can be downloaded by user
	 */
	public function getAttachFileName($type) {
		return 'DataMetrics-' . $this->dateTimeFormatter->formatDateTime($this->timeFactory->getTime(), 'short') . '-' . $type . '.csv';
	}

	/**
	 * Get the system metrics data written in cvs file format
	 *
	 * @return string, if csv data is retrieved then its returned as string else false.
	 */
	public function getSystemCSVData() {
		$result = [];

		/** storage */
		$quota = $this->quotaMetrics->getTotalQuotaUsage();
		$result['freeStorage'] = $quota['free'];
		$result['usedStorage'] = $quota['used'];

		/** files */
		$files = $this->filesMetrics->getTotalFilesCount();
		$result['totalFiles'] = $files['totalFiles'];

		/** users */
		$result['registeredUsers'] = $this->userActiveMetrics->getTotalUserCount();
		$result['activeUsers'] = $this->userActiveMetrics->getCurrentActiveUsers();
		$result['concurrentUsers'] = $this->userActiveMetrics->getConcurrentUsers();

		/** shares **/
		$shares = $this->sharesMetrics->getTotalShares();
		$result['userShares'] = $shares['userShareCount'];
		$result['groupShares'] = $shares['groupShareCount'];
		$result['linkShares'] = $shares['linkShareCount'];
		$result['guestShares'] = $shares['guestShareCount'];
		$result['federatedShares'] = $shares['federatedShareCount'];

		$encoder = [$this->csvEncoder];
		/**
		 * Couldn't inject ObjectNormalizer, an error was thrown. Hence initialized
		 * here.
		 */
		$normalizer = [new ObjectNormalizer()];

		$serializer = new Serializer($normalizer, $encoder);
		return $serializer->serialize($result, 'csv');
	}
}
