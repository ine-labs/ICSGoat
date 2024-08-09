<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Workflow\Engine;

use OCA\Workflow\Condition\Context;
use OCA\Workflow\Condition\RuleFactory;
use OCA\Workflow\Engine\Event\FileAction;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Plugin {

	/** @var FlowManager */
	protected $flowManager;

	/** @var Context */
	protected $context;

	/** @var RuleFactory */
	protected $ruleFactory;

	/** @var EventDispatcherInterface */
	protected $dispatcher;

	/**
	 * Plugin constructor.
	 *
	 * @param FlowManager $flowManager
	 * @param Context $context
	 * @param RuleFactory $ruleFactory
	 * @param EventDispatcherInterface $dispatcher
	 */
	public function __construct(FlowManager $flowManager, Context $context, RuleFactory $ruleFactory, EventDispatcherInterface $dispatcher) {
		$this->flowManager = $flowManager;
		$this->context = $context;
		$this->ruleFactory = $ruleFactory;
		$this->dispatcher = $dispatcher;
	}

	/**
	 * @param string $triggerName
	 * @param string $absolutePath
	 * @param int|null $fileId
	 * @param int[]|null $parentIds
	 */
	public function trigger($triggerName, $absolutePath, $fileId = null, array $parentIds = null) {
		if ($fileId === null && $parentIds === null) {
			$this->context->setFileIds(null);
		} else {
			if ($parentIds === null) {
				$parentIds = [];
			}
			if ($fileId !== null) {
				$parentIds[] = $fileId;
			}
			$this->context->setFileIds($parentIds);
		}

		/** @var Flow[] $flows */
		$flows = $this->flowManager->getFlowsForTrigger($triggerName);
		foreach ($flows as $flow) {
			foreach ($flow->getConditions() as $condition) {
				$instance = $this->ruleFactory->getRuleInstance($condition['rule']);
				if (!$instance->doCheck($condition['operator'], $condition['value'])) {
					continue 2;
				}
			}

			// All conditions met execute the flow
			$event = new FileAction($flow, $absolutePath, $fileId);
			$this->dispatcher->dispatch('OCA\Workflow\Engine::' . $triggerName, $event); /** @phpstan-ignore-line */
		}

		$this->context->setFileIds(null);
	}
}
