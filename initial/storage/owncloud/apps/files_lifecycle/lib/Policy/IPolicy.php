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

namespace OCA\Files_Lifecycle\Policy;

use OCP\IUser;

/**
 * Class IPolicy
 *
 * @package OCA\Files_Lifecycle\Policy
 */
interface IPolicy {
	/**
	 * Check the Users permission to restore
	 *
	 * @param IUser $user
	 *
	 * @return bool
	 */
	public function userCanRestore(IUser $user);

	/**
	 * Check the Impersonators permission to restore
	 *
	 * @return bool
	 */
	public function impersonatorCanRestore();

	/**
	 * Get the Archive Period in days
	 *
	 * @return int
	 */
	public function getArchivePeriod();

	/**
	 * Get the Expire Period in days
	 *
	 * @return int
	 */
	public function getExpirePeriod();

	/**
	 * If the user should be exempt from archiving of their files
	 *
	 * @param IUser $user
	 *
	 * @return boolean
	 */
	public function userExemptFromArchiving(IUser $user);
}
