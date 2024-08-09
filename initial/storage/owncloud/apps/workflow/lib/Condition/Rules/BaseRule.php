<?php
/**
 * ownCloud Workflow
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright 2015 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Workflow\Condition\Rules;

use OCA\Workflow\Condition\Context;
use OCA\Workflow\Condition\Operators;

/**
 * Class BaseCondition
 *
 * @package OCA\Workflow\Condition
 * This is the abstract base class for creating new conditions.
 */
abstract class BaseRule {

	/** @var Context */
	protected $context;
	/** @var Operators */
	protected $operators;

	/**
	 * Class Constructor
	 *
	 * @param Operators $operators
	 * @param Context $context
	 */
	public function __construct(Operators $operators, Context $context) {
		$this->operators = $operators;
		$this->context = $context;
	}

	/**
	 * Return an array with the allowed operators
	 *
	 * @abstract
	 * @return string[]
	 */
	abstract protected function getValidOperators();

	/**
	 * @param string $operator
	 * @param string $ruleId
	 * @throws \OutOfBoundsException when the operator is not allowed
	 */
	public function validateOperator($operator, $ruleId) {
		if (\in_array($operator, $this->getValidOperators())) {
			return;
		}

		throw new \OutOfBoundsException('The operator "' . $operator . '" is not allowed for rule "' . $ruleId . '"');
	}

	/**
	 * @param mixed $ruleValue
	 * @param string $ruleId
	 * @throws \OutOfBoundsException when the value is not allowed
	 */
	abstract public function validateRuleValue($ruleValue, $ruleId);

	/**
	 * Can return an array to fill a select box in the UI
	 *
	 * @return array|null
	 */
	public function getValidValues() {
		return null;
	}

	/**
	 * Return whether the request passes the check or not
	 *
	 * @abstract
	 * @param string $operator
	 * @param mixed $ruleValue
	 * @return boolean True if the check passes, false if the check fails
	 */
	abstract public function doCheck($operator, $ruleValue);
}
