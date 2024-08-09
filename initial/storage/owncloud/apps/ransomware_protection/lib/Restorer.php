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
use OCA\Files_Trashbin\Trashbin;
use OCP\App\IAppManager;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUserManager;

class Restorer {

	/** @var Scanner */
	protected $scanner;

	/** @var MovelogManager */
	protected $movelog;

	/** @var array */
	protected $new = [];

	/** @var array */
	protected $del = [];

	/** @var array */
	protected $mod = [];

	/** @var array */
	protected $mov = [];

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
	 * @param Scanner $scanner
	 * @param MovelogManager $movelogManager
	 * @param IConfig $config
	 * @param IDBConnection $connection
	 * @param IAppManager $appManager
	 * @param IUserManager $userManager
	 * @param IRootFolder $rootFolder
	 * @param IL10N $l10n
	 */
	public function __construct(Scanner $scanner, MovelogManager $movelogManager, IConfig $config, IDBConnection $connection, IAppManager $appManager, IUserManager $userManager, IRootFolder $rootFolder, IL10N $l10n) {
		$this->scanner = $scanner;
		$this->movelog = $movelogManager;
		$this->config = $config;
		$this->connection = $connection;
		$this->appManager = $appManager;
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->l10n = $l10n;
	}

	/**
	 * Restore files to given timestamp
	 *
	 * @param int $timestamp
	 * @param string $userId
	 * @return array
	 */
	public function restore($timestamp, $userId) {
		$result = [
			'errors' => [],
			'msg' => [],
			'restored' => []
		];
		$fileList = $this->scanner->getItems($timestamp, $userId);

		if (empty($fileList)) {
			$result['msg'][] = 'No files to restore for this timestamp.';
			return $result;
		}

		$this->splitByState($fileList);

		$result = \array_merge($result, $this->restoreNew($userId));
		$result = \array_merge($result, $this->restoreDeleted($userId));
		$result = \array_merge($result, $this->restoreMoved($userId));
		$result = \array_merge($result, $this->restoreModified($userId));

		return $result;
	}

	/**
	 * Delete all files, which are marked as new
	 *
	 * @param string $userId
	 * @return array
	 */
	private function restoreNew($userId) {
		$result = [
			'errors' => [],
			'msg' => [],
			'restored' => []
		];
		self::loginAsUser($userId);

		foreach ($this->new as $item) {
			$view = new View('/' . $userId . '/files');
			$view->unlink($item['path']);
			/**
			 * @var \OC\Files\Storage\Storage $storage
			 * @var string $internalPath
			 */
			list($storage, $internalPath) = $view->resolvePath($item['path']);
			$cache = $storage->getCache($internalPath);
			$cache->remove($internalPath);

			$result['restored'][] = $item['path'];
		}
		return $result;
	}

	/**
	 * Delete all versions after ts, restore first version before ts
	 *
	 * @param string $userId
	 * @return array
	 */
	private function restoreModified($userId) {
		$result = [
			'errors' => [],
			'msg' => [],
			'restored' => []
		];
		self::loginAsUser($userId);

		foreach ($this->mod as $item) {
			$revision = \substr(\pathinfo($item['name'], PATHINFO_EXTENSION), 1);
			$file = \str_replace(".v$revision", '', $item['path']);

			// first delete all newer versions
			$versions = \OCA\Files_Versions\Storage::getVersions($userId, $file);

			foreach ($versions as $v) {
				if ($v['version'] > $revision) {
					$view = new View('/' . $userId . '/files_versions');
					$delPath = $file . '.v' . $v['version'];
					$view->unlink($delPath);
					/**
					 * @var \OC\Files\Storage\Storage $storage
					 * @var string $internalPath
					 */
					list($storage, $internalPath) = $view->resolvePath($delPath);
					$cache = $storage->getCache($internalPath);
					$cache->remove($internalPath);
				}
			}

			// now restore our revision
			// ATTN: older core 10.0.* have method rollback which is altered to restoreVersion in newer core versions
			// see https://github.com/owncloud/core/pull/29257/files#diff-efc44835a2c59b3707c965b80a3d669e
			if (\method_exists(\OCA\Files_Versions\Storage::class, 'rollback')) {
				$status = \OCA\Files_Versions\Storage::rollback($file, $revision);
			} else {
				$file = \str_replace('files_versions', '', $file);
				$status = \OCA\Files_Versions\Storage::restoreVersion($userId, $file, '/files_versions/' . $item['path'] . '.v' . $revision, $revision);
			}
			if (!$status) {
				$result['errors'][] = "Restore modified $file";
			} else {
				$result['restored'][] = $file;
			}
		}

		return $result;
	}

	/**
	 * Delete all occurrences after ts, restore first deleted after ts
	 *
	 * @param string $userId
	 * @return array
	 */
	private function restoreDeleted($userId) {
		$result = [
			'errors' => [],
			'msg' => [],
			'restored' => []
		];

		self::loginAsUser($userId);

		foreach ($this->del as $item) {
			$file = '/' . \ltrim(\pathinfo($item['path'], PATHINFO_BASENAME) . '.d' . $item['ts'], '/');
			$filename = \pathinfo($item['path'], PATHINFO_BASENAME);

			if (!Trashbin::restore($file, $filename, $item['ts'])) {
				$result['errors'][] = $file;
			} else {
				// FIXME: update file cache
				$result['restored'][] = $file;
			}
		}

		return $result;
	}

	/**
	 *
	 * Bring back moved files to their position at timestamp time
	 *
	 * @param string $userId
	 * @return array
	 */
	private function restoreMoved($userId) {
		$result = [
				'errors' => [],
				'msg' => [],
				'restored' => []
		];

		self::loginAsUser($userId);

		foreach ($this->mov as $item) {
			$view = new View('/' . $userId . '/files/');
			$mount = $view->getMount(\pathinfo($item['currentpath'], PATHINFO_DIRNAME));
			$storage = $mount->getStorage();

			$source = \substr($storage->getCache()->getPathById($item['fileid']), \strlen('files'));
			$destination = $item['path'];

			// FIXME: restore, if deleted

			if (!$view->rename($source, $destination)) {
				$result['errors'][] = $item['path'];
			} else {
				$result['restored'][] = $item['path'];

				// delete ransomware_log entries
				$this->movelog->delete($item['fileid']);
			}
		}

		return $result;
	}

	/**
	 * Populate class members by status of given Scanner file list
	 *
	 * @param array $fileList
	 */
	private function splitByState($fileList) {
		foreach ($fileList as $key => $value) {
			switch ($value['state']) {
				case Scanner::STATUS_NEW:
					$this->new[$key] = $value;
					break;
				case Scanner::STATUS_MODIFIED:
					$this->mod[$key] = $value;
					break;
				case Scanner::STATUS_MOVED:
					$this->mov[$key] = $value;
					break;
				case Scanner::STATUS_DELETED:
					$this->del[$key] = $value;
					break;
			}
		}
	}

	/**
	 * Login and setup FS as a given user,
	 * sets the given user as the current user.
	 *
	 * @param string $user user id or empty for a generic FS
	 */
	protected static function loginAsUser($user = '') {
		self::logout();
		\OC\Files\Filesystem::tearDown();
		\OC_User::setUserId($user);
		$userObject = \OC::$server->getUserManager()->get($user);
		if ($userObject !== null) {
			$userObject->updateLastLoginTimestamp();
		}
		\OC_Util::setupFS($user);
		if (\OC_User::userExists($user)) {
			\OC::$server->getUserFolder($user);
		}
	}

	/**
	 * Logout the current user and tear down the filesystem.
	 */
	protected static function logout() {
		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		// needed for full logout
		\OC::$server->getUserSession()->setUser(null);
	}
}
