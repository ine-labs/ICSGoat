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

namespace OCA\Workflow\Engine;

use OCA\Workflow\PublicAPI\Engine\FlowInterface;

class Flow implements FlowInterface {
	/** @var int */
	protected $id = 0;

	/** @var string */
	protected $name = '';
	/** @var string */
	protected $type = '';
	/** @var array */
	protected $conditions = [];
	/** @var array */
	protected $actions = [];

	/**
	 * Flow constructor.
	 *
	 * @param int $id
	 */
	public function __construct($id = 0) {
		$this->id = (int) $id;
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @return array
	 */
	public function getConditions() {
		return $this->conditions;
	}

	/**
	 * @return array
	 */
	public function getActions() {
		return $this->actions;
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function setName($name) {
		$this->name = $name;
		return $this;
	}

	/**
	 * @param string $type
	 * @return $this
	 * @throws \OutOfBoundsException
	 */
	public function setType($type) {
		if (!\is_string($type)) {
			throw new \OutOfBoundsException();
		}

		$this->type = $type;
		return $this;
	}

	/**
	 * @param array $conditions
	 * @return $this
	 */
	public function setConditions(array $conditions) {
		$this->conditions = $conditions;
		return $this;
	}

	/**
	 * @param array $actions
	 * @return $this
	 */
	public function setActions(array $actions) {
		$this->actions = $actions;
		return $this;
	}
}
