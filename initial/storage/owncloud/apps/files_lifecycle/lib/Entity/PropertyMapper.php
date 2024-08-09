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

namespace OCA\Files_Lifecycle\Entity;

use OCA\Files_Lifecycle\Application;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\Mapper;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\IDBConnection;
use OCP\ILogger;

/**
 * Class PropertyMapper
 *
 * @package OCA\Files_Lifecycle\Entity
 */
class PropertyMapper extends Mapper {
	/**
	 * @var ILogger $logger
	 */
	private $logger;
	/**
	 * PropertyMapper constructor.
	 *
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db, ILogger $logger) {
		parent::__construct($db, 'properties');
		$this->logger = $logger;
	}

	/**
	 *
	 * @param int $id
	 * @param string $name
	 *
	 * @throws MultipleObjectsReturnedException
	 *
	 * @return Entity | bool
	 */
	public function findByIdAndPropertyName($id, $name) {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from($this->getTableName())
			->where($query->expr()->eq('fileid', $query->createNamedParameter($id)))
			->andWhere(
				$query->expr()->eq(
					'propertyname',
					$query->createNamedParameter($name)
				)
			);
		try {
			return $this->mapRowToEntity(
				$this->findOneQuery(
					$query->getSQL(),
					$query->getParameters()
				)
			);
		} catch (DoesNotExistException $e) {
			return false;
		} catch (MultipleObjectsReturnedException $e) {
			$this->logger->error(
				'Multiple Results for property ' . $name. ' found in the database for fileId ' . $id . ' .',
				[
					'app' => Application::APPID,
					'fileid' => $id
				]
			);
			return false;
		}
	}
}
