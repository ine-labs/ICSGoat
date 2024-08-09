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
class FileSize extends Rule {

	/**
	 * @param string $ruleValue
	 * @param string $ruleId
	 * @throws \OutOfBoundsException when the value is not allowed
	 */
	public function validateRuleValue($ruleValue, $ruleId) {
		if (\is_int($ruleValue)) {
			return;
		}

		throw new \OutOfBoundsException('The rule value "' . \json_encode($ruleValue) . '" is not valid for rule "' . $ruleId . '"');
	}

	/**
	 * Return an array with the allowed operators
	 *
	 * @return string[]
	 */
	protected function getValidOperators() {
		return [Ruler::OPERATOR_LESS, Ruler::OPERATOR_LESS_OR_EQUALS, Ruler::OPERATOR_GREATER, Ruler::OPERATOR_GREATER_OR_EQUALS];
	}

	/**
	 * @param string $operator
	 * @param mixed $ruleValue
	 * @return boolean True if the check passes, false if the check fails
	 */
	public function doCheck($operator, $ruleValue, \Sabre\DAV\Server $sabreServer = null) {

		/*
		 * Since we cant easily one off change the wording of the rule
		 * in the UI they make this very unclear for the end user so
		 * we have to flip them here
		 * todo: xxx HAS to be a better way to resolve this
		 */
		switch ($operator) {
			case 'less':
				$operator = 'greater';
				break;

			case 'less_or_equal':
				$operator = 'greater_or_equal';
				break;

			case 'greater':
				$operator = 'less';
				break;

			case 'greater_or_equal':
				$operator = 'less_or_equal';
				break;
		}

		$fileSize = $this->context->getUploadSize($sabreServer);
		if ($fileSize === null) {
			// If we cant find a size pass the rule by failing the condition
			return false;
		}

		return $this->assert($operator, $ruleValue, $fileSize);
	}
}
