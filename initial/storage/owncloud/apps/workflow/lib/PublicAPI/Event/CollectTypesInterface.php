<?php
/**
 * ownCloud Workflow
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright 2016 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Workflow\PublicAPI\Event;

interface CollectTypesInterface {
	public const TYPES_COLLECT = 'collectTypes';

	/**
	 * @param string $type
	 * @param string $description
	 * @param string[] $supportedFileActions Array of Plugin constants, if empty all are supported
	 * @throws \InvalidArgumentException when the type is already used
	 */
	public function addType($type, $description, $supportedFileActions = []);
}
