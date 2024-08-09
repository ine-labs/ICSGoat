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

use OCA\Firewall\Context;
use OCA\Firewall\Ruler;
use OCA\Firewall\Rules\Contracts\Rule;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;

/**
 * @package OCA\Firewall\Rules
 */
class SystemTag extends Rule {

	/** @var ISystemTagManager */
	protected $manager;

	/** @var ISystemTagObjectMapper */
	protected $mapper;

	/**
	 * Class Constructor
	 *
	 * @param Ruler $ruler
	 * @param Context $context
	 * @param ISystemTagManager $manager
	 * @param ISystemTagObjectMapper $mapper
	 */
	public function __construct(Ruler $ruler, Context $context, ISystemTagManager $manager, ISystemTagObjectMapper $mapper) {
		parent::__construct($ruler, $context);
		$this->manager = $manager;
		$this->mapper = $mapper;
	}

	/**
	 * @param string|int $ruleValue
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
		return [Ruler::OPERATOR_EQUALS, Ruler::OPERATOR_NOT_EQUALS];
	}

	/**
	 * @param string $operator
	 * @param array $ruleValue Array with two entries
	 * @return boolean True if the check passes, false if the check fails
	 */
	public function doCheck($operator, $ruleValue) {
		try {
			$oneFileHasTag = $this->mapper->haveTag($this->context->getFileIds(), 'files', $ruleValue, false);
		} catch (TagNotFoundException $e) {
			\OC::$server->getLogger()->error(
				'Tag #{tag} for firewall rule does not exist',
				[
					'tag' => $ruleValue,
					'app' => 'firewall',
				]
			);

			// To avoid access where it was not intended, all access is denied,
			// when the tag does not exist anymore.
			return true;
		}

		if ($operator === Ruler::OPERATOR_EQUALS) {
			return $oneFileHasTag;
		} else {
			return !$oneFileHasTag;
		}
	}
}
