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
class Regex extends Rule {

	/**
	 * @param string $ruleValue
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

		if (!\in_array($context, ['cidr', 'cidr6', 'request-url', 'userAgent', 'userGroup', 'filetype'])) {
			throw new \OutOfBoundsException('The rule value "' . $ruleValue . '" is not valid for rule "' . $ruleId . '"');
		}

		if (@\preg_match('/' . $value . '/', null) === false) {
			// When preg_match on value null returns false, the regex is invalid:
			// http://stackoverflow.com/questions/4440626/how-can-i-validate-regex
			throw new \OutOfBoundsException('The rule value "' . $ruleValue . '" is not valid for rule "' . $ruleId . '"');
		}
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
		list($context, $ruleValue) = \explode('||', $ruleValue, 2);
		$contextValues = $this->contextValues();

		if ($context === 'cidr6') {
			$contextValue = $contextValues['cidr'];
		} else {
			$contextValue = $contextValues[$context];
		}

		if ($context === 'cidr') {
			if (\filter_var($contextValue, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
				// rule is a regex to match an IPv4 address,
				// the access is not from IPv4 so it does not match
				return false;
			}
		}

		if ($context === 'cidr6') {
			if (\filter_var($contextValue, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
				// rule is a regex to match an IPv6 address,
				// the access is not from IPv6 so it does not match
				return false;
			}
		}

		$matches = $operator === Ruler::OPERATOR_EQUALS;

		// For user groups we have to check each of the user values
		if ($context === 'userGroup') {
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
		} elseif (!$contextValue && $context === 'filetype') {
			// Always allow if $contextValue is missing which is the case for
			// GET requests like download, view. The filetype context is limited
			// for uploads only.
			return false;
		} else {
			if ($context === 'filetype') {
				// Mime types are always lowercase
				$ruleValue = \strtolower($ruleValue);
			}

			$result = (bool)\preg_match('/' . $ruleValue . '/', $contextValue);
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
			'request-url' => $this->context->getRequestFullUrl(),
			'userAgent' => $this->context->getUserAgent(),
			'userGroup' => $this->context->getUserGroups(),
			'filetype' => $this->context->getUploadType(),
		];
	}
}
