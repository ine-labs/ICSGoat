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

namespace OCA\Ransomware_Protection\Db;

use OCP\AppFramework\Db\Mapper;
use OCP\IDBConnection;

class MovelogMapper extends Mapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'ransomware_log', Movelog::class);
	}

	public function find($fileid) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('id', 'fileid', 'timestamp', 'user_id', 'source', 'target')
			->from($this->getTableName())
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter($fileid)));

		return $this->findEntities($qb->getSQL(), $qb->getParameters());
	}
}
