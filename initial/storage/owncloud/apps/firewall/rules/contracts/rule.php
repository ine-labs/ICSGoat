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

namespace OCA\Firewall\Rules\Contracts;

use Hoa\Ruler\Context as HoaContext;
use OCA\Firewall\Context;
use OCA\Firewall\Ruler;

/**
 * Class Rule
 *
 * @package OCA\Firewall\Rules
 * This is the abstract base class for creating new firewall rules.
 */
abstract class Rule {

	/** @var Context */
	protected $context;
	/** @var Ruler */
	protected $ruler;

	/**
	 * Class Constructor
	 *
	 * @param Ruler $ruler
	 * @param Context $context
	 */
	public function __construct(Ruler $ruler, Context $context) {
		$this->ruler = $ruler;
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
	final public function validateOperator($operator, $ruleId) {
		if (\in_array($operator, $this->getValidOperators())) {
			return;
		}

		throw new \OutOfBoundsException('The operator "' . $operator . '" is not allowed for rule "' . $ruleId . '"');
	}

	/**
	 * @param string $ruleValue
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

	/**
	 * Get the result of our rule's
	 *
	 * @param string $operator
	 * @param mixed $ruleValue
	 * @param mixed $concreteValue
	 * @return bool
	 */
	final public function assert($operator, $ruleValue, $concreteValue) {
		$context = new HoaContext([
			'ruleVal' => $ruleValue,
			'value' => $concreteValue
		]);

		return $this->ruler->assert($this->evalRule($operator), $context);
	}

	/**
	 * @param string $operator
	 * @return string
	 */
	private function evalRule($operator) {
		return 'ruleVal ' . $operator . ' value';
	}
}
