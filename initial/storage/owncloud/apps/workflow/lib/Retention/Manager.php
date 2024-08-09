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

namespace OCA\Workflow\Retention;

use OCA\Workflow\Engine\Flow;
use OCA\Workflow\Engine\FlowManager;
use OCA\Workflow\Retention\Exception\TagAlreadyHasRetention;
use OCA\Workflow\Retention\Exception\TagHasNoRetention;

class Manager {

	/** @var FlowManager */
	protected $flowManager;

	/**
	 * Manager constructor.
	 *
	 * @param FlowManager $flowManager
	 */
	public function __construct(FlowManager $flowManager) {
		$this->flowManager = $flowManager;
	}

	/**
	 * @param string $tagId
	 * @param int $numUnits Number of days|weeks|months|years until retention is performed
	 * @param string $unit One of days|weeks|months|years
	 * @return array
	 * @throws TagAlreadyHasRetention
	 */
	public function add($tagId, $numUnits, $unit) {
		try {
			$this->get($tagId);
			throw new TagAlreadyHasRetention();
		} catch (TagHasNoRetention $e) {
		}

		$conditions = [
			'tagId' => $tagId,
			'numUnits' => (int) $numUnits,
			'unit' => $unit,
		];

		$flow = $this->flowManager->createFlow();
		$flow->setType('retention')
			->setName('Retention for tag: ' . $tagId)
			->setConditions($conditions)
			->setActions(['retention' => true]);

		$this->flowManager->addFlow($flow);

		return $conditions;
	}

	/**
	 * @param string $tagId
	 * @param int $numUnits Number of days|weeks|months|years until retention is performed
	 * @param string $unit One of days|weeks|months|years
	 * @return array
	 * @throws TagHasNoRetention
	 */
	public function update($tagId, $numUnits, $unit) {
		$flow = $this->get($tagId);

		$conditions = [
			'tagId' => $tagId,
			'numUnits' => $numUnits,
			'unit' => $unit,
		];

		$flow->setConditions($conditions);

		$this->flowManager->updateFlow($flow);

		return $conditions;
	}

	/**
	 * @param string $tagId
	 * @throws TagHasNoRetention
	 */
	public function delete($tagId) {
		$flow = $this->get($tagId);
		$this->flowManager->deleteFlow($flow);
	}

	/**
	 * @param string $tagId
	 * @return array
	 * @throws TagHasNoRetention
	 */
	public function getRetention($tagId) {
		$flow = $this->get($tagId);
		return $this->flowToArray($flow);
	}

	/**
	 * @param string $tagId
	 * @return Flow
	 * @throws TagHasNoRetention
	 */
	protected function get($tagId) {
		/** @var Flow[] $flows */
		$flows = $this->flowManager->getRetentionFlows();

		foreach ($flows as $flow) {
			$conditions = $flow->getConditions();
			if ($tagId === $conditions['tagId']) {
				return $flow;
			}
		}

		throw new TagHasNoRetention();
	}

	/**
	 * @return array
	 */
	public function getAll() {
		/** @var Flow[] $flows */
		$flows = $this->flowManager->getRetentionFlows();

		$retentionPeriods = [];
		foreach ($flows as $flow) {
			$retentionPeriods[] = $this->flowToArray($flow);
		}

		return $retentionPeriods;
	}

	/**
	 * @param Flow $flow
	 * @return array
	 */
	protected function flowToArray(Flow $flow) {
		$conditions = $flow->getConditions();

		return $conditions;
	}
}
