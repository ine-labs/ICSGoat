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

use Doctrine\DBAL\Platforms\OraclePlatform;
use OCA\Metrics\Helper;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IUser;

class QuotaMetrics {

	/**
	 * @var IRootFolder
	 */
	private $rootFolder;

	/**
	 * @var IDBConnection
	 */
	private $dbConnection;

	/**
	 * @var Helper
	 */
	private $helper;

	/**
	 * QuotaMetrics constructor.
	 *
	 * @param IRootFolder $rootFolder
	 * @param IDBConnection $dbConnection
	 * @param Helper $helper
	 */
	public function __construct(
		IRootFolder $rootFolder,
		IDBConnection $dbConnection,
		Helper $helper
	) {
		$this->rootFolder = $rootFolder;
		$this->dbConnection = $dbConnection;
		$this->helper = $helper;
	}

	/**
	 * Provides total quota used and available
	 *
	 * @param IUser|null $user
	 * @return array
	 */
	public function getTotalQuotaUsage(IUser $user = null) {
		$result = [
			'free' => 0,
			'total' => 0,
			'used' => 0,
			'relative' => 0,
		];

		if ($user !== null) {
			if (!$this->helper->isGuestUser($user->getUID())) {
				try {
					$this->setupFS($user);
					$storageInfo = \OC_Helper::getStorageInfo('/');
					$result['free'] = $storageInfo['free'];
					$result['used'] = $storageInfo['used'];
					$result['total'] = $storageInfo['total'];
					$result['relative'] = $storageInfo['relative'];
				} catch (\Exception $ex) {
					$result['message'] = $ex->getMessage();
				}
			}
		} else {
			$result['free'] = $this->rootFolder->getFreeSpace();
			$result['used'] = $this->getUsedSpace();
			$result['total'] = $result['free'] + $result['used'];
		}

		return $result;
	}

	/**
	 * Queries the filecache for all root level folders and returns the sum of their sizes.
	 * This also includes e.g. thumbnails and avatars.
	 *
	 * @return int
	 */
	private function getUsedSpace() {
		$statement = null;
		try {
			$qb = $this->dbConnection->getQueryBuilder();
			if ($this->dbConnection->getDatabasePlatform() instanceof OraclePlatform) {
				// `size` is a reserved word in oracle db. need to escape it oracle-style.
				$qb->selectAlias($qb->createFunction('SUM("size")'), 'total_size');
			} else {
				$qb->selectAlias($qb->createFunction('SUM(size)'), 'total_size');
			}
			$qb->from('filecache')
				->where($qb->expr()->eq('parent', $qb->expr()->literal(-1)))
				->andWhere($qb->expr()->gt('size', $qb->expr()->literal(0)));
			$statement = $qb->execute();
			/* @phan-suppress-next-line PhanDeprecatedFunction */
			return (int)$statement->fetch()['total_size'];
		} finally {
			if ($statement) {
				/* @phan-suppress-next-line PhanDeprecatedFunction */
				$statement->closeCursor();
			}
		}
	}

	/**
	 * @param IUser $user
	 *
	 * @return void
	 */
	private function setupFS(IUser $user) {
		static $fsUser = null;
		if ($fsUser === null || $fsUser !== $user) {
			\OC_Util::tearDownFS();
			\OC_Util::setupFS($user->getUID());
			$fsUser = $user;
		}
	}
}
