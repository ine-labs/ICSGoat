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

namespace OCA\Files_Lifecycle\Local;

use OCA\Files_Lifecycle\ArchiveQuery;
use OCA\Files_Lifecycle\ExpireQuery;
use OCA\Files_Lifecycle\IArchiver;
use OCA\Files_Lifecycle\IExpirer;
use OCA\Files_Lifecycle\ILifecycleStrategy;
use OCA\Files_Lifecycle\IRestorer;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\IUserManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class LocalStrategy
 *
 * @package OCA\Files_Lifecycle
 */
class LocalStrategy implements ILifecycleStrategy {

	/**
	 * @var IRootFolder
	 */
	protected $rootFolder;

	/**
	 * @var IUserManager
	 */
	protected $userManager;

	/**
	 * @var ArchiveQuery
	 */
	protected $archiveQuery;

	/**
	 * @var ExpireQuery
	 */
	protected $expireQuery;

	/**
	 * @var EventDispatcherInterface
	 */
	protected $eventDispatcher;

	/**
	 * @var IDBConnection
	 */
	protected $connection;

	/**
	 * @var ILogger
	 */
	protected $logger;

	/**
	 * LocalStrategy constructor.
	 *
	 * @param IRootFolder $rootFolder
	 * @param IUserManager $userManager
	 * @param ArchiveQuery $archiveQuery
	 * @param IDBConnection $connection
	 * @param EventDispatcherInterface $eventDispatcher
	 * @param ExpireQuery $expireQuery
	 * @param ILogger $logger
	 */
	public function __construct(
		IRootFolder $rootFolder,
		IUserManager $userManager,
		ArchiveQuery $archiveQuery,
		IDBConnection $connection,
		EventDispatcherInterface $eventDispatcher,
		ExpireQuery $expireQuery,
		ILogger $logger
	) {
		$this->rootFolder = $rootFolder;
		$this->userManager = $userManager;
		$this->archiveQuery = $archiveQuery;
		$this->connection = $connection;
		$this->eventDispatcher = $eventDispatcher;
		$this->expireQuery = $expireQuery;
		$this->logger = $logger;
	}

	/**
	 * get the local Archiver
	 *
	 * @return IArchiver|LocalArchiver
	 */
	public function getArchiver() {
		return new LocalArchiver(
			$this->rootFolder,
			$this->userManager,
			$this->archiveQuery,
			$this->connection,
			$this->eventDispatcher,
			$this->logger
		);
	}

	/**
	 * Get the local Expirer
	 *
	 * @return IExpirer|LocalExpirer
	 */
	public function getExpirer() {
		return new LocalExpirer(
			$this->rootFolder,
			$this->connection,
			$this->eventDispatcher,
			$this->expireQuery,
			$this->logger
		);
	}

	/**
	 * Get the local Restorer
	 *
	 * @return IRestorer|LocalRestorer
	 */
	public function getRestorer() {
		return new LocalRestorer(
			$this->rootFolder,
			$this->connection,
			$this->eventDispatcher,
			$this->userManager,
			$this->logger
		);
	}
}
