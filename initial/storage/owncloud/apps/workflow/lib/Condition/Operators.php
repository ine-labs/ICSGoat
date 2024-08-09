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

namespace OCA\Workflow\Condition;

class Operators {
	public const OPERATOR_EQUALS = 'equals';
	public const OPERATOR_NOT_EQUALS = 'not_equals';

	public const OPERATOR_IN = 'in';
	public const OPERATOR_NOT_IN = 'not_in';
	public const OPERATOR_ARRAY_CONTAINS = 'array_contains';
	public const OPERATOR_ARRAY_NOT_CONTAINS = 'array_not_contains';

	public const OPERATOR_LESS = 'less';
	public const OPERATOR_LESS_OR_EQUALS = 'less_or_equal';
	public const OPERATOR_GREATER = 'greater';
	public const OPERATOR_GREATER_OR_EQUALS = 'greater_or_equal';

	public const OPERATOR_BEGINS_WITH = 'begins_with';
	public const OPERATOR_NOT_BEGINS_WITH = 'not_begins_with';
	public const OPERATOR_CONTAINS = 'contains';
	public const OPERATOR_NOT_CONTAINS = 'not_contains';
	public const OPERATOR_ENDS_WITH = 'ends_with';
	public const OPERATOR_NOT_ENDS_WITH = 'not_ends_with';

	/**
	 * @param mixed $actualValue
	 * @param string $operator
	 * @param mixed $comparisonValue
	 * @return bool
	 */
	public function assert($actualValue, $operator, $comparisonValue) {
		return \call_user_func(
			[$this, $operator],
			$actualValue,
			$comparisonValue
		);
	}

	/**
	 * @param int|string $actualValue
	 * @param int|string $comparisonValue
	 * @return bool
	 */
	protected function equals($actualValue, $comparisonValue) {
		return $actualValue === $comparisonValue;
	}

	/**
	 * @param int|string $actualValue
	 * @param int|string $comparisonValue
	 * @return bool
	 */
	protected function not_equals($actualValue, $comparisonValue) {
		return !$this->equals($actualValue, $comparisonValue);
	}

	/**
	 * @param int|string $actualValue
	 * @param array $comparisonValue
	 * @return bool
	 */
	protected function in($actualValue, array $comparisonValue) {
		return \in_array($actualValue, $comparisonValue);
	}

	/**
	 * @param int|string $actualValue
	 * @param array $comparisonValue
	 * @return bool
	 */
	protected function not_in($actualValue, array $comparisonValue) {
		return !$this->in($actualValue, $comparisonValue);
	}

	/**
	 * @param array $actualValue
	 * @param string $comparisonValue
	 * @return bool
	 */
	protected function array_contains($actualValue, $comparisonValue) {
		return \in_array($comparisonValue, $actualValue);
	}

	/**
	 * @param array $actualValue
	 * @param string $comparisonValue
	 * @return bool
	 */
	protected function array_not_contains($actualValue, $comparisonValue) {
		return !$this->array_contains($actualValue, $comparisonValue);
	}

	/**
	 * @param int $actualValue
	 * @param int $comparisonValue
	 * @return bool
	 */
	protected function less($actualValue, $comparisonValue) {
		return $actualValue < $comparisonValue;
	}

	/**
	 * @param int $actualValue
	 * @param int $comparisonValue
	 * @return bool
	 */
	protected function less_or_equal($actualValue, $comparisonValue) {
		return $actualValue <= $comparisonValue;
	}

	/**
	 * @param int $actualValue
	 * @param int $comparisonValue
	 * @return bool
	 */
	protected function greater($actualValue, $comparisonValue) {
		return !$this->less_or_equal($actualValue, $comparisonValue);
	}

	/**
	 * @param int $actualValue
	 * @param int $comparisonValue
	 * @return bool
	 */
	protected function greater_or_equal($actualValue, $comparisonValue) {
		return !$this->less($actualValue, $comparisonValue);
	}

	/**
	 * @param string $actualValue
	 * @param string $comparisonValue
	 * @return bool
	 */
	protected function begins_with($actualValue, $comparisonValue) {
		return \strpos($actualValue, $comparisonValue) === 0;
	}

	/**
	 * @param string $actualValue
	 * @param string $comparisonValue
	 * @return bool
	 */
	protected function not_begins_with($actualValue, $comparisonValue) {
		return !$this->begins_with($actualValue, $comparisonValue);
	}

	/**
	 * @param string $actualValue
	 * @param string $comparisonValue
	 * @return bool
	 */
	protected function contains($actualValue, $comparisonValue) {
		return \strpos($actualValue, $comparisonValue) !== false;
	}

	/**
	 * @param string $actualValue
	 * @param string $comparisonValue
	 * @return bool
	 */
	protected function not_contains($actualValue, $comparisonValue) {
		return !$this->contains($actualValue, $comparisonValue);
	}

	/**
	 * @param string $actualValue
	 * @param string $comparisonValue
	 * @return bool
	 */
	protected function ends_with($actualValue, $comparisonValue) {
		return \substr($actualValue, -\strlen($comparisonValue)) === $comparisonValue;
	}

	/**
	 * @param string $actualValue
	 * @param string $comparisonValue
	 * @return bool
	 */
	protected function not_ends_with($actualValue, $comparisonValue) {
		return !$this->ends_with($actualValue, $comparisonValue);
	}
}
