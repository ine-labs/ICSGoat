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

namespace OCA\Firewall;

use Hoa\Ruler\Ruler as HoaRuler;

class Ruler extends HoaRuler {
	/**
	 * DO NOT CHANGE without updating the config and docs
	 */
	public const OPERATOR_EQUALS = 'equal';
	public const OPERATOR_NOT_EQUALS = 'not_equal';

	public const OPERATOR_IN = 'in'; // defined by HOA
	public const OPERATOR_NOT_IN = 'not_in';

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

	public function __construct() {
		$this->addOperators();
	}

	/**
	 * Add custom operator to the parsing engine
	 */
	private function addOperators() {
		/** @var \Hoa\Ruler\Visitor\Asserter $asserter */
		$asserter = $this->getAsserter();
		$asserter->setOperator(self::OPERATOR_EQUALS, $asserter->getOperator('='));
		$asserter->setOperator(self::OPERATOR_NOT_EQUALS, $asserter->getOperator('!='));

		$asserter->setOperator(self::OPERATOR_NOT_IN, function ($a, array $b) {
			return !\in_array($a, $b);
		});

		$asserter->setOperator(self::OPERATOR_LESS, $asserter->getOperator('<'));
		$asserter->setOperator(self::OPERATOR_LESS_OR_EQUALS, $asserter->getOperator('<='));
		$asserter->setOperator(self::OPERATOR_GREATER, $asserter->getOperator('>'));
		$asserter->setOperator(self::OPERATOR_GREATER_OR_EQUALS, $asserter->getOperator('>='));

		$asserter->setOperator(self::OPERATOR_BEGINS_WITH, function ($a, $b) {
			return $a === "" || \strpos($b, $a) === 0;
		});
		$asserter->setOperator(self::OPERATOR_NOT_BEGINS_WITH, function ($a, $b) {
			return $a === "" || \strpos($b, $a) !== 0;
		});
		$asserter->setOperator(self::OPERATOR_CONTAINS, function ($a, $b) {
			return \stripos($b, $a) !== false;
		});
		$asserter->setOperator(self::OPERATOR_NOT_CONTAINS, function ($a, $b) {
			return \stripos($b, $a) === false;
		});
		$asserter->setOperator(self::OPERATOR_ENDS_WITH, function ($a, $b) {
			return $a === "" || \substr($b, -\strlen($a)) === $a;
		});
		$asserter->setOperator(self::OPERATOR_NOT_ENDS_WITH, function ($a, $b) {
			return $a === "" || \substr($b, -\strlen($a)) !== $a;
		});
	}
}
