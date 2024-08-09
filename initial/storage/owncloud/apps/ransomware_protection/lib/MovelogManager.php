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

use OCA\Ransomware_Protection\Db\Movelog;
use OCA\Ransomware_Protection\Db\MovelogMapper;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;

class MovelogManager {
	private $l10n;
	private $movelogMapper;

	public function __construct(IL10N $l10n, MovelogMapper $movelogMapper) {
		$this->l10n = $l10n;
		$this->movelogMapper = $movelogMapper;
	}

	/**
	 * Add a log entry
	 *
	 * @param int $fileid
	 * @param int $timestamp
	 * @param string $userId
	 * @param string $source
	 * @param string $target
	 *
	 * @return \OCP\AppFramework\Http\JSONResponse
	 */
	public function save($fileid, $timestamp, $userId, $source, $target) {
		$movelog = new Movelog();

		$movelog->setFileid($fileid);
		$movelog->setTimestamp($timestamp);
		$movelog->setUserId($userId);
		$movelog->setSource($source);
		$movelog->setTarget($target);

		$log = $this->movelogMapper->insert($movelog);

		return new JSONResponse($log);
	}

	/**
	 * Delete logs for a file id
	 * @param int $fileid
	 * @return JSONResponse
	 */
	public function delete($fileid) {
		$logs = $this->movelogMapper->find($fileid);
		foreach ($logs as $log) {
			$this->movelogMapper->delete($log);
		}

		return new JSONResponse($logs);
	}
}
