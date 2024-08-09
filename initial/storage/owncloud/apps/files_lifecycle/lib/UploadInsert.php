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

use Doctrine\DBAL\Driver\Statement;
use OCA\Files_Lifecycle\Dav\ArchivePlugin;
use OCP\Files\IMimeTypeLoader;
use OCP\IDBConnection;

/**
 * Class UploadInsert
 *
 * @package OCA\Files_Lifecycle
 */
class UploadInsert {
	/**
	 * @var IDBConnection
	 */
	protected $db;

	/**
	 * @var IMimeTypeLoader
	 */
	protected $loader;

	/**
	 * UploadInsert constructor.
	 *
	 * @param IDBConnection $db
	 * @param IMimeTypeLoader $loader
	 */
	public function __construct(IDBConnection $db, IMimeTypeLoader $loader) {
		$this->db = $db;
		$this->loader = $loader;
	}

	/**
	 *
	 * @suppress PhanTypeMismatchArgument
	 *
	 * @return Statement
	 */
	public function selectUploadTimeMissing() {
		$qb = $this->db->getQueryBuilder();
		$mime = $this->loader->getId('httpd/unix-directory');
		return $qb->select('f.*')
			->from('filecache', 'f')
			->leftJoin(
				'f',
				'properties',
				'p',
				$qb->expr()
					->andX(
						$qb->expr()->eq('p.fileid', 'f.fileid')
					)
					->add(
						$qb->expr()->eq(
							'p.propertyname',
							$qb->expr()->literal(ArchivePlugin::UPLOAD_TIME)
						)
					)
			)
			->where($qb->expr()->like('f.path', $qb->expr()->literal('files/%')))
			->andWhere($qb->expr()->neq('f.mimetype', $qb->expr()->literal($mime)))
			->andWhere($qb->expr()->isNull('p.propertyvalue'))
			->andWhere($qb->expr()->isNull('p.fileid'))
			->setMaxResults(10000)
			->execute();
	}
}
