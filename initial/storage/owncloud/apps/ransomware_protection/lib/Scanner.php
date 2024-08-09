<?php
/**
 * ownCloud - Ransomware Protection
 *
 * @author Thomas Heinisch <t.heinisch@bw-tech.de>
 * @copyright 2017 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Ransomware_Protection;

use OC\Files\View;
use OCP\App\IAppManager;
use OCP\Files\IRootFolder;
use OCA\Files_Trashbin\Trashbin;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUserManager;

class Scanner {
	public const STATUS_DELETED = 'del';
	public const STATUS_MODIFIED = 'mod';
	public const STATUS_NEW = 'new';
	public const STATUS_MOVED = 'mov';

	public const MIMETYPE_DIRECTORY = 2;

	/** @var IConfig */
	protected $config;

	/** @var IDBConnection */
	protected $connection;

	/** @var IAppManager */
	protected $appManager;

	/** @var IUserManager */
	protected $userManager;

	/** @var IRootFolder */
	protected $rootFolder;

	/** @var IL10N */
	protected $l10n;

	/**
	 * Constructor.
	 *
	 * @param IConfig $config
	 * @param IL10N $l10n
	 */
	public function __construct(IConfig $config, IDBConnection $connection, IAppManager $appManager, IUserManager $userManager, IRootFolder $rootFolder, IL10N $l10n) {
		$this->config = $config;
		$this->connection = $connection;
		$this->appManager = $appManager;
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->l10n = $l10n;
	}

	/**
	 * Find file system events after provided time stamp
	 *
	 * @param int $timestamp
	 * @param string $userId
	 * @return array
	 */
	public function getItems($timestamp, $userId) {
		// force scan to be sure cache is updated
		$this->scanStorage($userId);

		$items = [];

		$items = \array_merge($items, $this->getNew($timestamp, $userId));
		$items = \array_merge($items, $this->getDeleted($timestamp, $userId));
		$items = \array_merge($items, $this->getVersions($timestamp, $userId));
		$items = \array_merge($items, $this->getMoved($timestamp, $userId));
		$items = $this->stripSecondaryModifications($this->sortByTimestamp($items));

		return $items;
	}

	/**
	 * FIXME: preserve versions
	 * Get files for user ID which were deleted after given timestamp
	 *
	 * @param int $timestamp
	 * @param string $userId
	 * @return array
	 */
	protected function getDeleted($timestamp, $userId) {
		$query = $this->connection->getQueryBuilder();
		$result = $query->select(['id', 'timestamp', 'location'])
			->from('files_trash')
			->where($query->expr()->eq('user', $query->createNamedParameter($userId)))
			->andWhere($query->expr()->gt('timestamp', $query->createNamedParameter($timestamp)))
			->orderBy('id', 'ASC')
			->execute();

		$deleted = [];
		$previous = null;
		while ($row = $result->fetch()) {
			$location = $row['location'] === '.' ? '' : '/' . $row['location'];
			$path = $location . '/' . $row['id'];
			if ($path === $previous) {
				// only take first occurrence
				continue;
			}
			$previous = $path;
			$deleted[] = [
				'fileid' => null,
				'name' => $row['id'],
				'path' => $path,
				'ts' => \date('U', $row['timestamp']),
				'state' => self::STATUS_DELETED
			];

			// find versions, if any
			$versions = self::getVersionsFromTrash($row['id'], $row['timestamp'], $userId);
			foreach ($versions as $versionTimestamp) {
				if ($timestamp > $versionTimestamp) {
					$deleted[] = [
						'fileid' => null,
						'name' => $row['id'] . '.v' . $versionTimestamp,
						'path' => $path,
						'ts' => $versionTimestamp,
						'state' => self::STATUS_MODIFIED
					];
				}
			}
		}

		return $deleted;
	}

	/**
	 * Get files for user ID which were modified after given timestamp
	 *
	 * @param int $timestamp
	 * @param string $userId
	 * @return array
	 */
	protected function getVersions($timestamp, $userId) {
		$query = $this->connection->getQueryBuilder();
		$result = $query->select(['fc.fileid', 'fc.path', 'fc.name', 'fc.mtime'])
			->from('filecache', 'fc')
			->innerJoin('fc', 'storages', 's', 'fc.storage = s.numeric_id')
			->where($query->expr()->eq('s.id', $query->createPositionalParameter("home::$userId")))
			->andWhere($query->expr()->like('fc.path', $query->createPositionalParameter('files_versions/%')))
			->andWhere($query->expr()->neq('fc.mimetype', $query->createPositionalParameter(self::MIMETYPE_DIRECTORY)))
			->andWhere($query->expr()->gt('fc.mtime', $query->createPositionalParameter($timestamp)))
			->orderBy('fc.path', 'ASC')
			->execute();

		$versions = [];
		$previous = null;
		while ($row = $result->fetch()) {
			$path = \pathinfo($row['path'], PATHINFO_DIRNAME) . '/' . \pathinfo($row['path'], PATHINFO_FILENAME);
			$path = \str_replace('files_versions/', '/', $path);

			if ($path === $previous || $row['mtime'] < $timestamp) {
				// skip when older than given ts and only take first occurrence
				continue;
			}

			$previous = $path;
			$versions[] = [
				'fileid' => $row['fileid'],
				'name' => $row['name'],
				'path' => $path,
				'ts' => \date('U', $row['mtime']),
				'state' => self::STATUS_MODIFIED
			];
		}

		return $versions;
	}

	/**
	 * Get files for user ID which were created after given timestamp
	 *
	 * @param int $timestamp
	 * @param string $userId
	 * @return array
	 */
	protected function getNew($timestamp, $userId) {
		$filespath = 'files/%';

		$query = $this->connection->getQueryBuilder();
		$result = $query->select(['fc.fileid', 'fc.name', 'fc.path', 'fc.storage_mtime'])
			->from('filecache', 'fc')
			->innerJoin('fc', 'storages', 's', 'fc.storage = s.numeric_id')
			->where($query->expr()->eq('s.id', $query->createPositionalParameter("home::$userId")))
			->andWhere($query->expr()->gt('fc.storage_mtime', $query->createPositionalParameter($timestamp)))
			->andWhere($query->expr()->like('fc.path', $query->createPositionalParameter($filespath)))
			->andWhere($query->expr()->neq('fc.mimetype', $query->createPositionalParameter(self::MIMETYPE_DIRECTORY)))
			->execute();

		$new = [];
		while ($row = $result->fetch()) {
			$new[] = [
				'fileid' => $row['fileid'],
				'name' => $row['name'],
				'path' => \str_replace('files', '', $row['path']),
				'ts' => \date('U', $row['storage_mtime']),
				'state' => self::STATUS_NEW
			];
		}

		return $new;
	}

	/**
	 * Get files for user ID which were moved after given timestamp
	 *
	 * @param int $timestamp
	 * @param string $userId
	 * @return array
	 */
	protected function getMoved($timestamp, $userId) {
		$query = $this->connection->getQueryBuilder();
		$result = $query->select(['fileid', 'timestamp', 'source', 'target'])
			->from('ransomware_log')
			->where($query->expr()->eq('user_id', $query->createNamedParameter($userId)))
			->andWhere($query->expr()->gt('timestamp', $query->createNamedParameter($timestamp)))
			->orderBy('fileid', 'ASC')
			->execute();

		$moved = [];
		$knownIds = [];
		while ($row = $result->fetch()) {
			$sourcePath = \str_replace("/$userId/files", '', $row['source']);
			$targetPath = \str_replace("/$userId/files", '', $row['target']);

			if (\in_array($row['fileid'], $knownIds)) {
				// only take first occurrence
				continue;
			}

			$knownIds[] = $row['fileid'];
			$moved[] = [
				'fileid' => $row['fileid'],
				'name' => \pathinfo($sourcePath, PATHINFO_BASENAME),
				'path' => $sourcePath,
				'ts' => \date('U', $row['timestamp']),
				'state' => self::STATUS_MOVED,
				'currentpath' => $targetPath
			];
		}

		return $moved;
	}

	/**
	 * Update cache
	 *
	 * @param string $userId
	 */
	private function scanStorage($userId) {
		$view = new View('/' . $userId. '/');
		list($storage, ) = $view->resolvePath('/');
		$storage->getScanner()->scan('/');
	}

	/**
	 * Only keep first file modification (by timestamp) for a path
	 *
	 * @param array $array
	 * @return array
	 */
	private function stripSecondaryModifications($array) {
		$foundPaths = [];
		foreach ($array as $key => $value) {
			// we add a suffix for found paths array, so we can keep
			// multiple actions (new, mod, del, move) for the same path
			// starting with a common suffix for every path to avoid
			// conflicts with actual file names
			$path = $value['path'] . 'CHANGED';

			if (\array_key_exists($path, $foundPaths)) {
				if ($foundPaths[$path]['state'] === self::STATUS_NEW && $value['state'] === self::STATUS_MODIFIED) {
					// special case: mod precedes new, we replace
					$foundPaths[$path] = $value;
				} elseif ($foundPaths[$path]['state'] === self::STATUS_MODIFIED && $value['state'] === self::STATUS_DELETED) {
					// special case: first modified, then deleted -> we add a new path
					$foundPaths[$path . 'MODDEL'] = $value;
				} elseif ($foundPaths[$path]['state'] === self::STATUS_MODIFIED && $value['state'] === self::STATUS_NEW) {
					// special case: first modified, then new -> we add a new path
					$foundPaths[$path . 'MODNEW'] = $value;
				} elseif ($value['state'] === self::STATUS_MODIFIED) {
					// only keep highest version
					$foundVersion = (int)\str_replace('v', '', \pathinfo($foundPaths[$path]['name'], PATHINFO_EXTENSION));
					$currentVersion = (int)\str_replace('v', '', \pathinfo($value['name'], PATHINFO_EXTENSION));
					// replace with higher version
					if ($currentVersion > $foundVersion) {
						$foundPaths[$path] = $value;
					}
				} elseif ($value['state'] === self::STATUS_MOVED) {
					// we never strip moved files: do nothing
				} elseif ($foundPaths[$path]['state'] === self::STATUS_DELETED && $value['state'] === self::STATUS_NEW) {
					// special case: first deleted, then added with same name -> we add a new path
					$foundPaths["$path."] = $value;
				} else {
					// remove duplicate
					unset($array[$key]);
				}
			} else {
				$foundPaths[$path] = $value;
			}
		}

		return $foundPaths;
	}

	/**
	 * Call private Trashbin::getVersionsFromTrash from here
	 *
	 * @param string $filename
	 * @param int $timestamp
	 * @param string $user
	 * @return array
	 */
	public static function getVersionsFromTrash($filename, $timestamp, $user) {
		$class = new \ReflectionClass(Trashbin::class);
		$method = $class->getMethod('getVersionsFromTrash');
		$method->setAccessible(true);

		return $method->invokeArgs(null, [$filename, $timestamp, $user]);
	}

	/**
	 * Sort given array by 'ts' element
	 *
	 * @param array $array
	 * @return array
	 */
	private function sortByTimestamp($array) {
		$sorted = [];
		foreach ($array as $key => $val) {
			$ts = $val['ts'];
			while (isset($sorted[$ts])) {
				$ts .= '.';
			}
			$sorted[$ts] = $val;
		}
		$this->natksort($sorted);
		return $sorted;
	}

	/**
	 * natural ksort
	 *
	 * @param array $array
	 * @return bool
	 */
	private function natksort(&$array) {
		$new_array = [];
		$keys = \array_keys($array);
		\natcasesort($keys);

		foreach ($keys as $k) {
			$new_array[$k] = $array[$k];
		}

		$array = $new_array;
		return true;
	}
}
