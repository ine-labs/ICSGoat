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

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use OCA\Files_Lifecycle\Dav\ArchivePlugin;
use OCA\Files_Lifecycle\Policy\IPolicy;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\StorageNotAvailableException;
use OCP\IDBConnection;
use OCP\IUser;

/**
 * Class ExpireQuery
 *
 * @package OCA\Files_Lifecycle
 */
class ExpireQuery {
	public const MAIN_SQL = <<<EOS
SELECT f.*, p.`propertyvalue` FROM `*PREFIX*filecache` f
  LEFT JOIN `*PREFIX*properties` p ON p.`fileid` = f.`fileid` AND p.`propertyname` = ?
  WHERE p.`propertyvalue` IS NOT NULL AND f.`storage` = ?
EOS;

	public const SQL_UTIME = ' AND CAST(p.`propertyvalue` AS DATETIME) < CAST(? AS DATETIME)';

	public const PGSQL_UTIME = ' AND CAST(p.`propertyvalue` AS timestamp) < CAST(? AS timestamp)';

	public const OCI_SQL_UTIME = ' AND p.`propertyvalue` < ?';

	/**
	 * @var IRootFolder
	 */
	protected $rootFolder;

	/**
	 * @var IDBConnection
	 */
	protected $db;

	/**
	 * @var IPolicy
	 */
	protected $policy;

	/**
	 * @var int
	 */
	protected $expirePeriod;

	/**
	 * ExpireQuery constructor.
	 *
	 * @param IRootFolder $rootFolder
	 * @param IDBConnection $connection
	 * @param IPolicy $policy
	 *
	 */
	public function __construct(
		IRootFolder $rootFolder,
		IDBConnection $connection,
		IPolicy $policy
	) {
		$this->rootFolder = $rootFolder;
		$this->db = $connection;
		$this->policy = $policy;
		$this->expirePeriod = $this->policy->getExpirePeriod();
	}

	/**
	 * Get Files to expire for User
	 *
	 * @param IUser $user
	 *
	 * @return \Generator
	 *
	 * @throws NotFoundException
	 * @throws StorageNotAvailableException
	 * @throws \Exception
	 *
	 */
	public function getUserFilesForExpiry(IUser $user) {
		try {
			$storage = $this->rootFolder->get(
				$user->getUID() . '/archive'
			)->getStorage();
		} catch (NotFoundException $e) {
			return;
		}
		$storageId = $storage->getCache()->getNumericStorageId();
		$now = new \DateTime();
		$expirationTime = $now->sub(
			\DateInterval::createFromDateString(
				$this->expirePeriod . ' days'
			)
		);

		$params = [
			ArchivePlugin::ARCHIVED_TIME,
			(string) $storageId,
			$expirationTime->format(\DateTime::ATOM)
		];
		$query = self::MAIN_SQL;
		if ($this->db->getDatabasePlatform() instanceof PostgreSqlPlatform) {
			$query .= self::PGSQL_UTIME;
		} elseif ($this->db->getDatabasePlatform() instanceof MySqlPlatform) {
			$query .= self::SQL_UTIME;
		} elseif ($this->db->getDatabasePlatform() instanceof OraclePlatform) {
			$query .= self::OCI_SQL_UTIME;
		} else {
			$query .= self::OCI_SQL_UTIME;
		}

		$statement = $this->db->executeQuery($query, $params);
		/* @phan-suppress-next-line PhanDeprecatedFunction */
		while ($row = $statement->fetch()) {
			yield $row;
		}
		/* @phan-suppress-next-line PhanDeprecatedFunction */
		$statement->closeCursor();
	}
}
