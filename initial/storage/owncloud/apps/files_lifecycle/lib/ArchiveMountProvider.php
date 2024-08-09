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

use OC\Files\Mount\MountPoint;
use OCA\Files_Lifecycle\Storage\Archive;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Storage\IStorageFactory;
use OCP\IConfig;
use OCP\IUser;

/**
 * Class ArchiveMountProvider
 *
 * @package OCA\Files_Lifecycle
 */
class ArchiveMountProvider implements IMountProvider {
	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * ObjectStoreHomeMountProvider constructor.
	 *
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * Get the archive mount for a user
	 *
	 * @param IUser $user
	 * @param IStorageFactory $loader
	 *
	 * @return \OCP\Files\Mount\IMountPoint[]
	 */
	public function getMountsForUser(IUser $user, IStorageFactory $loader) {
		$default = $user->getHome();
		$archiveBaseDir = $this->config->getSystemValue(
			'archive_path',
			$default . '/archive'
		);

		if (\strpos($default, $archiveBaseDir) == 0) {
			$archiveDir = \rtrim($archiveBaseDir, '/');
		} else {
			$archiveDir = \rtrim($archiveBaseDir, '/')
				. '/' . $user->getUID();
		}

		if ($archiveBaseDir !== '') {
			return [
				new MountPoint(
					Archive::class,
					$user->getUID() . '/archive/',
					[
						'datadir' => $archiveDir,
						'user' => $user,
						$loader
					]
				)
			];
		} else {
			return [];
		}
	}
}
