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
use OCP\ILogger;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;

class SystemTag extends BaseRule {

	/** @var ISystemTagManager */
	protected $manager;

	/** @var ISystemTagObjectMapper */
	protected $mapper;

	/** @var ILogger */
	protected $logger;

	/**
	 * Class Constructor
	 *
	 * @param Operators $operators
	 * @param Context $context
	 * @param ISystemTagManager $manager
	 * @param ISystemTagObjectMapper $mapper
	 */
	public function __construct(Operators $operators, Context $context, ISystemTagManager $manager, ISystemTagObjectMapper $mapper, ILogger $logger) {
		parent::__construct($operators, $context);
		$this->manager = $manager;
		$this->mapper = $mapper;
		$this->logger = $logger;
	}

	/**
	 * @param mixed $ruleValue
	 * @param string $ruleId
	 * @throws \OutOfBoundsException when the value is not allowed
	 */
	public function validateRuleValue($ruleValue, $ruleId) {
		if (!\is_string($ruleValue) && !\is_int($ruleValue)) {
			throw new \OutOfBoundsException('The rule value "' . \json_encode($ruleValue) . '" is not valid for rule "' . $ruleId . '"');
		}

		try {
			$this->manager->getTagsByIds($ruleValue);
		} catch (TagNotFoundException $e) {
			throw new \OutOfBoundsException('The rule value "' . \json_encode($ruleValue) . '" is not valid for rule "' . $ruleId . '"');
		} catch (\InvalidArgumentException $e) {
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
	 * @param string $ruleValue
	 * @return boolean True if the check passes, false if the check fails
	 */
	public function doCheck($operator, $ruleValue) {
		try {
			$oneFileHasTag = $this->mapper->haveTag($this->context->getFileIds(), 'files', $ruleValue, false);
		} catch (TagNotFoundException $e) {
			$this->logger->logException($e, [
					'app' => 'workflow',
			]);

			// To avoid access where it was not intended, all access is denied,
			// when the tag does not exist anymore.
			return true;
		}

		if ($operator === Operators::OPERATOR_EQUALS) {
			return $oneFileHasTag;
		} else {
			return !$oneFileHasTag;
		}
	}
}
