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

class Time extends BaseRule {

	/**
	 * @param mixed $ruleValue
	 * @param string $ruleId
	 * @throws \OutOfBoundsException when the value is not allowed
	 */
	public function validateRuleValue($ruleValue, $ruleId) {
		if (!\is_array($ruleValue) || \sizeof($ruleValue) !== 2 || !isset($ruleValue[0]) || !\is_string($ruleValue[0]) || !isset($ruleValue[1]) || !\is_string($ruleValue[1])) {
			throw new \OutOfBoundsException('The rule value "' . \json_encode($ruleValue) . '" is not valid for rule "' . $ruleId . '"');
		}

		$utcTimeZone = new \DateTimeZone('UTC');
		$date0 = \DateTime::createFromFormat('g:i a', $ruleValue[0], $utcTimeZone);
		$date1 = \DateTime::createFromFormat('g:i a', $ruleValue[1], $utcTimeZone);

		if ($date0 === false || $date1 === false) {
			throw new \OutOfBoundsException('The rule value "' . \json_encode($ruleValue) . '" is not valid for rule "' . $ruleId . '"');
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
	 * @param array $ruleValue Array with two entries
	 * @return boolean True if the check passes, false if the check fails
	 */
	public function doCheck($operator, $ruleValue) {
		$utcTimeZone = new \DateTimeZone('UTC');

		$nowTimestamp = $this->context->getRequestTime();
		$now = new \DateTime('now', $utcTimeZone);
		$now->setTimestamp($nowTimestamp);

		/**
		 * Fix the date to the current one and set the timezone
		 */
		// Value format '03:20 pm -0500'
		$begin = \DateTime::createFromFormat('Y-n-j g:i a', $now->format('Y-n-j') . ' ' . $ruleValue[0])
			->setTimezone($utcTimeZone);
		$beginTimestamp = $begin->getTimestamp();

		$end = \DateTime::createFromFormat('Y-n-j g:i a', $now->format('Y-n-j') . ' ' . $ruleValue[1])
			->setTimezone($utcTimeZone);
		$endTimestamp = $end->getTimestamp();

		if ($beginTimestamp < $endTimestamp) {
			// 1pm to 5pm
			$result = $this->operators->assert($beginTimestamp, Operators::OPERATOR_LESS, $nowTimestamp) &&
				$this->operators->assert($nowTimestamp, Operators::OPERATOR_LESS, $endTimestamp);
		} else {
			// 5pm to 1pm (over night)
			$result = $this->operators->assert($beginTimestamp, Operators::OPERATOR_LESS, $nowTimestamp) ||
				$this->operators->assert($nowTimestamp, Operators::OPERATOR_LESS, $endTimestamp);
		}

		return ($operator === Operators::OPERATOR_EQUALS) ? $result : !$result;
	}
}
