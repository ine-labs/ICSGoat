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
use OC\Files\Cache\Storage;
use OCA\Files_Lifecycle\Dav\ArchivePlugin;
use OCA\Files_Lifecycle\Policy\IPolicy;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IUser;

/**
 * Class ArchiveQuery
 *
 * @package OCA\Files_Lifecycle
 */
class ArchiveQuery {
	public const MAIN_SQL = <<<EOS
SELECT f.*, p.`propertyvalue` FROM `*PREFIX*filecache` f
  LEFT JOIN `*PREFIX*properties` p ON p.`fileid` = f.`fileid`
  WHERE p.`propertyvalue` IN (
    SELECT MAX(`propertyvalue`) FROM `*PREFIX*properties` WHERE `propertyname` IN (?, ?) AND `fileid` = f.`fileid`
  ) AND p.`propertyvalue` IS NOT NULL AND f.`path` LIKE 'files/%' AND f.`storage` = ?
EOS;

	public const SQL_UTIME = ' AND CAST(p.`propertyvalue` AS DATETIME) < CAST(? AS DATETIME)';

	public const PGSQL_UTIME = ' AND CAST(p.`propertyvalue` AS timestamp) < CAST(? AS timestamp)';

	public const OCLSQL_UTIME = ' AND p.`propertyvalue` < ?';

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
	protected $archivePeriod;

	/**
	 * ArchiveQuery constructor.
	 *
	 * @param IRootFolder $rootFolder
	 * @param IDBConnection $connection
	 * @param IPolicy $policy
	 */
	public function __construct(
		IRootFolder $rootFolder,
		IDBConnection $connection,
		IPolicy $policy
	) {
		$this->rootFolder = $rootFolder;
		$this->db = $connection;
		$this->policy = $policy;
		$this->archivePeriod = $this->policy->getArchivePeriod();
	}

	/**
	 * Get Files From Users archive
	 *
	 * @param IUser $user
	 *
	 * @return \Generator
	 * @throws \OCP\Files\NotFoundException
	 * @throws \Exception
	 */
	public function getFilesForArchive(IUser $user) {
		$folder = $this->rootFolder->getUserFolder($user->getUID());
		$storageId = Storage::getNumericStorageId($folder->getStorage()->getId());
		$now = new \DateTime();
		$archiveTime = $now->sub(
			\DateInterval::createFromDateString(
				$this->archivePeriod . ' days'
			)
		);

		$params = [
			ArchivePlugin::UPLOAD_TIME,
			ArchivePlugin::RESTORED_TIME,
			(string) $storageId,
			$archiveTime->format(\DateTime::ATOM)
		];
		$query = self::MAIN_SQL;
		if ($this->db->getDatabasePlatform() instanceof PostgreSqlPlatform) {
			$query .= self::PGSQL_UTIME;
		} elseif ($this->db->getDatabasePlatform() instanceof MySqlPlatform) {
			$query .= self::SQL_UTIME;
		} elseif ($this->db->getDatabasePlatform() instanceof OraclePlatform) {
			$query .= self::OCLSQL_UTIME;
		} else {
			$query .= self::OCLSQL_UTIME;
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
