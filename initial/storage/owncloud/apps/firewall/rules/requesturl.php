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

use OCA\Firewall\Ruler;
use OCA\Firewall\Rules\Contracts\Rule;

/**
 * @package OCA\Firewall\Rules
 */
class RequestUrl extends Rule {

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
	 * Return an array with the allowed operators
	 *
	 * @return string[]
	 */
	protected function getValidOperators() {
		return [
			Ruler::OPERATOR_EQUALS, Ruler::OPERATOR_NOT_EQUALS,
			Ruler::OPERATOR_BEGINS_WITH, Ruler::OPERATOR_NOT_BEGINS_WITH,
			Ruler::OPERATOR_CONTAINS, Ruler::OPERATOR_NOT_CONTAINS,
			Ruler::OPERATOR_ENDS_WITH, Ruler::OPERATOR_NOT_ENDS_WITH,
		];
	}

	/**
	 * @param string $operator
	 * @param mixed $ruleValue
	 * @return boolean True if the check passes, false if the check fails
	 */
	public function doCheck($operator, $ruleValue) {
		$ruleValue = \strtolower($ruleValue);
		$url = \strtolower($this->context->getRequestFullUrl());
		return $this->assert($operator, $ruleValue, $url);
	}
}
