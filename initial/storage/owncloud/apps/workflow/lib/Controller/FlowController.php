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

namespace OCA\Workflow\Controller;

use OCA\Workflow\Condition\RuleFactory;
use OCA\Workflow\Engine\Event\ValidateFlow;
use OCA\Workflow\Engine\Exception\FlowDoesNotExist;
use OCA\Workflow\Engine\Flow;
use OCA\Workflow\Engine\FlowManager;
use OCA\Workflow\PublicAPI\Event\ValidateFlowInterface;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IL10N;
use OCP\IRequest;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FlowController extends Controller {

	/** @var FlowManager */
	protected $manager;

	/** @var EventDispatcherInterface */
	protected $dispatcher;

	/** @var RuleFactory */
	protected $ruleFactory;

	/** @var IL10N */
	protected $l;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param FlowManager $manager
	 * @param EventDispatcherInterface $dispatcher
	 * @param RuleFactory $ruleFactory
	 * @param IL10N $l
	 */
	public function __construct($AppName, IRequest $request, FlowManager $manager, EventDispatcherInterface $dispatcher, RuleFactory $ruleFactory, IL10N $l) {
		parent::__construct($AppName, $request);
		$this->manager = $manager;
		$this->dispatcher = $dispatcher;
		$this->ruleFactory = $ruleFactory;
		$this->l = $l;
	}

	/**
	 * @return JSONResponse
	 */
	public function getWorkFlows() {
		$flows = $this->manager->getFlowsForTrigger(null);
		$flows = \array_map([$this->manager, 'flowToArray'], $flows);
		return new JSONResponse($flows);
	}

	/**
	 * @param string $type
	 * @param string $name
	 * @param array $conditions
	 * @param array $actions
	 * @return JSONResponse
	 */
	public function addWorkFlow($type, $name, $conditions, array $actions) {
		// Empty arrays are sent as null/undefined by some browsers, so we
		// catch that case and use an empty array here instead.
		if (!\is_array($conditions)) {
			$conditions = [];
		}

		try {
			$flow = $this->manager->createFlow();
			$flow->setType($type)
				->setName($name)
				->setConditions($conditions)
				->setActions($actions);

			$this->validateWorkFlow($flow);
		} catch (\OutOfBoundsException $e) {
			return new JSONResponse(
				['error' => $e->getMessage()],
				Http::STATUS_BAD_REQUEST
			);
		}

		$flowId = $this->manager->addFlow($flow);
		$flowData = $this->manager->flowToArray($flow);
		$flowData['id'] = (int) $flowId;

		return new JSONResponse($flowData);
	}

	/**
	 * @param int $flowId
	 * @param string $name
	 * @param array $conditions
	 * @param array $actions
	 * @return JSONResponse
	 */
	public function updateWorkFlow($flowId, $name, $conditions, array $actions) {
		// Empty arrays are sent as null/undefined by some browsers, so we
		// catch that case and use an empty array here instead.
		if (!\is_array($conditions)) {
			$conditions = [];
		}

		try {
			$flow = $this->manager->getFlowById($flowId);
		} catch (FlowDoesNotExist $e) {
			return new JSONResponse(
				['error' => (string) $this->l->t('The workflow does not exist')],
				Http::STATUS_BAD_REQUEST
			);
		}

		try {
			// Changing the type is not allowed atm
			$flow->setName($name)
				->setConditions($conditions)
				->setActions($actions);

			$this->validateWorkFlow($flow);
		} catch (\OutOfBoundsException $e) {
			return new JSONResponse(
				['error' => $e->getMessage()],
				Http::STATUS_BAD_REQUEST
			);
		}

		$this->manager->updateFlow($flow);

		return new JSONResponse($this->manager->flowToArray($flow));
	}

	/**
	 * @param int $flowId
	 * @return Response
	 */
	public function deleteWorkFlow($flowId) {
		try {
			$flow = $this->manager->getFlowById($flowId);
			$this->manager->deleteFlow($flow);
		} catch (FlowDoesNotExist $e) {
			// Kill the exception:
			// No autotagging is exactly what we want to achieve
		}

		return new Response();
	}

	/**
	 * Return the options for conditions and the types
	 *
	 * @return DataResponse
	 */
	public function getConditionValuesAndTypes() {
		$conditionValues = [];
		foreach ($this->ruleFactory->getRules() as $id) {
			$rule = $this->ruleFactory->getRuleInstance($id);
			$validValues = $rule->getValidValues();
			if (\is_array($validValues)) {
				$conditionValues[$id] = $validValues;
			}
		}

		return new DataResponse([
			'conditionValues'	=> $conditionValues,
			'types'				=> $this->manager->getTypeList(),
		], Http::STATUS_OK);
	}

	/**
	 * Validate the input data
	 *
	 * @param Flow $flow
	 * @throws \OutOfBoundsException
	 */
	protected function validateWorkFlow(Flow $flow) {
		$type = $flow->getType();
		if (!\is_string($type) || isset($type[64])) {
			throw new \OutOfBoundsException((string) $this->l->t('Workflow type too long or invalid'), 1);
		}

		$name = $flow->getName();
		if (!\is_string($name) || isset($name[200])) {
			throw new \OutOfBoundsException((string) $this->l->t('Workflow name too long or invalid'), 2);
		}

		foreach ($flow->getConditions() as $condition) {
			if (\sizeof($condition) !== 3 || !isset($condition['rule']) || !isset($condition['operator']) || !isset($condition['value'])) {
				throw new \OutOfBoundsException((string) $this->l->t('At least one of the conditions is invalid'), 3);
			}

			try {
				$rule = $this->ruleFactory->getRuleInstance($condition['rule']);
				$rule->validateOperator($condition['operator'], $condition['rule']);
				$rule->validateRuleValue($condition['value'], $condition['rule']);
			} catch (\Exception $e) {
				// Catch \InvalidArgumentException when the rule type does not exist
				// Catch \OutOfBoundsException when the operator or value is invalid
				throw new \OutOfBoundsException((string) $this->l->t('At least one of the conditions is invalid'), 3);
			}
		}

		$event = new ValidateFlow($flow);
		$this->dispatcher->dispatch('OCA\Workflow\Engine::' . ValidateFlowInterface::FLOW_VALIDATE, $event); /** @phpstan-ignore-line */
	}
}
