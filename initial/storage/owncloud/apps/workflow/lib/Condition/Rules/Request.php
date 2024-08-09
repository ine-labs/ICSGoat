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

class Request extends BaseRule {

	/**
	 * @param mixed $ruleValue
	 * @param string $ruleId
	 * @throws \OutOfBoundsException when the value is not allowed
	 */
	public function validateRuleValue($ruleValue, $ruleId) {
		if (!\is_string($ruleValue)) {
			throw new \OutOfBoundsException('The rule value "' . \json_encode($ruleValue) . '" is not valid for rule "' . $ruleId . '"');
		}

		$validValues = $this->getValidValues();
		if (isset($validValues[$ruleValue])) {
			return;
		}

		throw new \OutOfBoundsException('The rule value "' . $ruleValue . '" is not valid for rule "' . $ruleId . '"');
	}

	/**
	 * Can return an array to fill a select box in the UI
	 *
	 * @return array|null
	 */
	public function getValidValues() {
		return [
			Context::REQUEST_TYPE_PUBLIC => 'Public Share Link',
			Context::REQUEST_TYPE_WEBDAV => 'WebDAV',
			Context::REQUEST_TYPE_OTHER => 'Other',
		];
	}

	/**
	 * Return an array with the allowed operators
	 *
	 * @return string[]
	 */
	protected function getValidOperators() {
		return [Operators::OPERATOR_EQUALS, Operators::OPERATOR_NOT_EQUALS];
	}

	/**
	 * @param string $operator
	 * @param mixed $ruleValue
	 * @return boolean True if the check passes, false if the check fails
	 */
	public function doCheck($operator, $ruleValue) {
		return $this->operators->assert($this->context->getRequestType(), $operator, $ruleValue);
	}
}
