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

namespace OCA\Ransomware_Protection\Hooks;

use OCA\Ransomware_Protection\MovelogManager;

class RootHooks {
	private $rootFolder;
	private $appName;
	private $movelog;

	public function __construct($rootFolder, $appName, MovelogManager $movelog) {
		$this->rootFolder = $rootFolder;
		$this->appName = $appName;
		$this->movelog = $movelog;
	}

	public function register() {
		$callback = function (\OCP\Files\Node $source, \OCP\Files\Node $target) {
			$sourcePath = $source->getPath();
			$targetPath = $target->getPath();
			$fileId = $target->getId();
			$userId = null;
			if (\OC::$server->getUserSession()->getUser() !== null) {
				$userId = \OC::$server->getUserSession()->getUser()->getUID();
			}

			$this->movelog->save($fileId, \time(), $userId, $sourcePath, $targetPath);
		};
		$this->rootFolder->listen('\OC\Files', 'postRename', $callback);
	}
}
