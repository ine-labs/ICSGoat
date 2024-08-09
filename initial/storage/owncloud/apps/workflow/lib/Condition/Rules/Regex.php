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

use OCA\Workflow\Condition\Operators;

class Regex extends BaseRule {

	/**
	 * @param mixed $ruleValue
	 * @param string $ruleId
	 * @throws \OutOfBoundsException when the value is not allowed
	 */
	public function validateRuleValue($ruleValue, $ruleId) {
		if (!\is_string($ruleValue)) {
			throw new \OutOfBoundsException('The rule value "' . \json_encode($ruleValue) . '" is not valid for rule "' . $ruleId . '"');
		}

		if (\strpos($ruleValue, '||') === false) {
			throw new \OutOfBoundsException('The rule value "' . $ruleValue . '" is not valid for rule "' . $ruleId . '"');
		}

		list($context, $value) = \explode('||', $ruleValue, 2);

		if (!\in_array($context, ['cidr', 'subnet', 'requesturl', 'useragent', 'usergroup'])) {
			throw new \OutOfBoundsException('The rule value "' . $ruleValue . '" is not valid for rule "' . $ruleId . '"');
		}

		if (@\preg_match('/' . $value . '/', '') === false) {
			// When preg_match returns false, the regex is invalid.
			throw new \OutOfBoundsException('The rule value "' . $ruleValue . '" is not valid for rule "' . $ruleId . '"');
		}
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
		list($context, $ruleValue) = \explode('||', $ruleValue, 2);
		$contextValues = $this->contextValues();
		$contextValue = $contextValues[$context];

		$matches = $operator === Operators::OPERATOR_EQUALS;

		// For user groups we have to check each of the user values
		if ($context === 'usergroup') {
			foreach ($contextValue as $value) {
				$result = (bool) \preg_match('/' . $ruleValue . '/', $value);

				if ($matches && $result) {
					// If we need to find the group, one hit is enough to be positive
					return true;
				} elseif (!$matches && $result) {
					// If we must not find the group, one hit is enough to be negative
					return false;
				}
			}

			// If we didn't find the group, the result is negative for the "match",
			// and positive for the "not match"
			return ($matches) ? false : true;
		} else {
			$result = (bool) \preg_match('/' . $ruleValue . '/', $contextValue);
			if ($matches) {
				return $result;
			}
			return !$result;
		}
	}

	/**
	 * Map rule values to criteria names
	 *
	 * @return array
	 */
	private function contextValues() {
		return [
			'cidr' => $this->context->getRemoteAddress(),
			'subnet' => $this->context->getServerAddress(),
			'requesturl' => $this->context->getRequestFullUrl(),
			'useragent' => $this->context->getUserAgent(),
			'usergroup' => $this->context->getUserGroups(),
		];
	}
}
