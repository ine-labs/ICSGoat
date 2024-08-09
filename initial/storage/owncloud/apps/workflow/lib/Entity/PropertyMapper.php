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

namespace OCA\Workflow\Entity;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\Mapper;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\IDBConnection;

/**
 * Class PropertyMapper
 *
 * @package OCA\Workflow\Entity
 */
class PropertyMapper extends Mapper {
	/**
	 * PropertyMapper constructor.
	 *
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'properties');
	}

	/**
	 *
	 * @param int $id
	 * @param string $name
	 *
	 * @throws MultipleObjectsReturnedException
	 *
	 * @return Property|Entity|bool
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
		}
	}
}
