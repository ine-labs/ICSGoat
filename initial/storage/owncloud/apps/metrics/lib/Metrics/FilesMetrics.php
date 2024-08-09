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

use OCA\Metrics\Helper;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;

class FilesMetrics {
	/**
	 * @var IDBConnection
	 */
	private $connection;

	/**
	 * @var IUserManager
	 */
	private $userManager;

	/**
	 * @var Helper
	 */
	private $helper;

	/**
	 * FilesMetrics constructor.
	 *
	 * @param IDBConnection $connection
	 * @param IUserManager $userManager
	 * @param Helper $helper
	 */
	public function __construct(
		IDBConnection $connection,
		IUserManager $userManager,
		Helper $helper
	) {
		$this->connection = $connection;
		$this->userManager = $userManager;
		$this->helper = $helper;
	}

	/**
	 * Provides total file count for the oc instance
	 * and when user argument is null, the total file count of user is retrieved
	 * and the average files count per user when user argument is null
	 *
	 * @param IUser|null $user
	 * @return mixed an array of total files in oC and/or average files count per user
	 */
	public function getTotalFilesCount(IUser $user = null) {
		$numericId = 0;
		if ($user !== null) {
			if ($this->helper->isGuestUser($user->getUID())) {
				return ['totalFiles' => 0];
			}
			$qb = $this->connection->getQueryBuilder();
			$statement = $qb->select('numeric_id')
				->from('storages')
				->where($qb->expr()->like('id', $qb->createPositionalParameter('%::' . $user->getUID())))
				->execute();
			/* @phan-suppress-next-line PhanDeprecatedFunction */
			$numericId = $statement->fetch()['numeric_id'];
			/* @phan-suppress-next-line PhanDeprecatedFunction */
			$statement->closeCursor();
		}

		$qb1 = $this->connection->getQueryBuilder();
		$qb1->selectAlias($qb1->createFunction('COUNT(*)'), 'totalFiles')
			->from('filecache')
			->Where($qb1->expr()->like('path', $qb1->createPositionalParameter('files/%')))
			// mimetype = 2 => its a folder, exclude it.
			->andWhere($qb1->expr()->neq('mimetype', $qb1->expr()->literal(2)));
		if ($user !== null && $numericId > 0) {
			$qb1->andWhere($qb1->expr()->eq('storage', $qb1->expr()->literal($numericId)));
		}
		$statement1 = $qb1->execute();
		/* @phan-suppress-next-line PhanDeprecatedFunction */
		$result = $statement1->fetch();
		/* @phan-suppress-next-line PhanDeprecatedFunction */
		$statement1->closeCursor();

		$result['totalFiles'] = (int)$result['totalFiles'];

		return $result;
	}
}
