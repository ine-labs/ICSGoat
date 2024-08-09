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

namespace OCA\Workflow\Engine\Event;

use OCA\Workflow\PublicAPI\Event\CollectTypesInterface;
use Symfony\Component\EventDispatcher\Event;

class CollectTypes extends Event implements CollectTypesInterface {

	/** @var array */
	protected $types;

	/**
	 * CollectTypes constructor.
	 */
	public function __construct() {
		$this->types = [];
	}

	/**
	 * @param string $type
	 * @param string $description
	 * @param string[] $supportedFileActions
	 * @throws \InvalidArgumentException when the type is already used
	 */
	public function addType($type, $description, $supportedFileActions = []) {
		if (isset($this->types[$type])) {
			throw new \InvalidArgumentException('Type already used');
		}

		$this->types[$type] = [
			'description' => $description,
			'supportedFileActions' => $supportedFileActions,
		];
	}

	/**
	 * @return array Entries: type => [description, supportedFileActions]
	 */
	public function getTypes() {
		return $this->types;
	}

	/**
	 * Stops the propagation of the event to further event listeners.
	 *
	 * If multiple event listeners are connected to the same event, no
	 * further event listener will be triggered once any trigger calls
	 * stopPropagation().
	 */
	public function stopPropagation() {
		throw new \BadMethodCallException('Not allowed to call stopPropagation()');
	}
}
