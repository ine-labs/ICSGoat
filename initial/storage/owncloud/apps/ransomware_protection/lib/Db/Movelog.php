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

use OCP\AppFramework\Db\Entity;

class Movelog extends Entity {
	/**
	 * Id of file that was moved
	 * @var int
	 */
	protected $fileid;

	/**
	 * Timestamp of the file operation
	 * @var int
	 */
	protected $timestamp;

	/**
	 * User Id of the file owner
	 * @var string
	 */
	protected $userId;

	/**
	 * Full source path
	 * @var string
	 */
	protected $source;

	/**
	 * Full target path
	 * @var string
	 */
	protected $target;

	public function __construct() {
		$this->addType('fileid', 'integer');
		$this->addType('timestamp', 'integer');
	}
}
