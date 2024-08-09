<?php

/**
 * @author Benedikt Kulmann <bkulmann@owncloud.com>
 * @copyright (C) 2020 ownCloud GmbH
 * @license ownCloud Commercial License
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Metrics;

use OCP\IConfig;

class Helper {

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * Helper constructor.
	 *
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * Checks if the given $uid is a guest user.
	 *
	 * @param string $uid
	 * @return bool
	 */
	public function isGuestUser(string $uid) {
		return !!$this->config->getUserValue(
			$uid,
			'owncloud',
			'isGuest',
			false
		);
	}
}
