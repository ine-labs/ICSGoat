<?php
/**
 * ownCloud Firewall
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright Copyright (c) 2018, ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Firewall\Rules;

use OCA\Firewall\Context;
use OCA\Firewall\Ruler;
use OCA\Firewall\Rules\Contracts\Rule;
use OCP\IGroupManager;

/**
 * @package OCA\Firewall\Rules
 */
class UserGroup extends Rule {

	/** @var IGroupManager */
	protected $groupManager;

	/** @var array */
	protected $groups;

	/**
	 * Class Constructor
	 *
	 * @param Ruler $ruler
	 * @param Context $context
	 * @param IGroupManager $groupManager
	 */
	public function __construct(Ruler $ruler, Context $context, IGroupManager $groupManager) {
		parent::__construct($ruler, $context);
		$this->groupManager = $groupManager;
	}

	/**
	 * @param string $ruleValue
	 * @param string $ruleId
	 * @throws \OutOfBoundsException when the value is not allowed
	 */
	public function validateRuleValue($ruleValue, $ruleId) {
		if (!\is_string($ruleValue)) {
			throw new \OutOfBoundsException('The rule value "' . \json_encode($ruleValue) . '" is not valid for rule "' . $ruleId . '"');
		}
	}

	/**
	 * Can return an array to fill a select box in the UI
	 *
	 * @return array|null
	 */
	public function getValidValues() {
		if ($this->groups !== null) {
			return $this->groups;
		}

		$groups = $this->groupManager->search('');

		$data = [];
		foreach ($groups as $group) {
			$data[$group->getGID()] = $group->getGID();
		}
		\ksort($data);

		$this->groups = $data;
		return $data;
	}

	/**
	 * Return an array with the allowed operators
	 *
	 * @return string[]
	 */
	protected function getValidOperators() {
		return [Ruler::OPERATOR_EQUALS, Ruler::OPERATOR_NOT_EQUALS];
	}

	/**
	 * @param string $operator
	 * @param mixed $ruleValue
	 * @return boolean True if the check passes, false if the check fails
	 */
	public function doCheck($operator, $ruleValue) {

		// change the operator since groups are stored in an array
		if ($operator === Ruler::OPERATOR_EQUALS) {
			$operator = Ruler::OPERATOR_IN;
		} else {
			$operator = Ruler::OPERATOR_NOT_IN;
		}

		return $this->assert($operator, $ruleValue, $this->context->getUserGroups());
	}
}
