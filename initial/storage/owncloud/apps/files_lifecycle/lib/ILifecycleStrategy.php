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

/**
 * Interface ILifecycleStrategy
 *
 * @package OCA\Files_Lifecycle
 */
interface ILifecycleStrategy {
	/**
	 * Get the archiver for this Strategy
	 *
	 * @return IArchiver
	 */
	public function getArchiver();

	/**
	 * Get the expirer for this Strategy
	 *
	 * @return IExpirer
	 */
	public function getExpirer();

	/**
	 * Get the Restorer for this Strategy
	 *
	 * @return IRestorer
	 */
	public function getRestorer();
}
