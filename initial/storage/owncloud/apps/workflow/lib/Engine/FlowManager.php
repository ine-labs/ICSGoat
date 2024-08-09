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

namespace OCA\Workflow\Engine;

use OCA\Workflow\Engine\Event\CollectTypes;
use OCA\Workflow\Engine\Exception\FlowDoesNotExist;
use OCA\Workflow\PublicAPI\Event\CollectTypesInterface;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FlowManager {
	protected $tableName = 'workflows';

	/** @var IDBConnection */
	protected $connection;

	/** @var EventDispatcherInterface */
	protected $dispatcher;

	/** @var array */
	protected $types = null;

	/**
	 * Manager constructor.
	 *
	 * @param IDBConnection $connection
	 * @param EventDispatcherInterface $dispatcher
	 */
	public function __construct(IDBConnection $connection, EventDispatcherInterface $dispatcher) {
		$this->connection = $connection;
		$this->dispatcher = $dispatcher;
	}

	protected function getTypes() {
		if ($this->types !== null) {
			return $this->types;
		}

		$event = new CollectTypes();
		$event->addType('retention', '');

		$this->dispatcher->dispatch('OCA\Workflow\Engine::' . CollectTypesInterface::TYPES_COLLECT, $event); /** @phpstan-ignore-line */
		$this->types = $event->getTypes();
		unset($this->types['retention']);
		\ksort($this->types);

		return $this->types;
	}

	public function getTypeList() {
		$types = $this->getTypes();

		$list = [];
		foreach ($types as $type => $data) {
			$list[$type] = $data['description'];
		}
		return $list;
	}

	/**
	 * @param string|null $trigger
	 * @return string[]
	 */
	protected function getTypesForTrigger($trigger) {
		$types = $this->getTypes();

		$list = [];
		foreach ($types as $type => $data) {
			if ($trigger === null) {
				$list[] = $type;
				continue;
			}

			if (empty($data['supportedFileActions']) || \in_array($trigger, $data['supportedFileActions'])) {
				$list[] = $type;
			}
		}
		return $list;
	}

	/**
	 * @return Flow[]
	 */
	public function getRetentionFlows() {
		return $this->getFlows('retention');
	}

	/**
	 * @param string|null $trigger
	 * @return Flow[]
	 */
	public function getFlowsForTrigger($trigger) {
		$types = $this->getTypesForTrigger($trigger);
		return $this->getFlows($types);
	}

	/**
	 * @param string|string[]|null $types
	 * @return Flow[]
	 */
	protected function getFlows($types) {
		$query = $this->connection->getQueryBuilder();

		$query->select('*')
			->from($this->tableName);

		if (\is_array($types)) {
			$query->where($query->expr()->in('workflow_type', $query->createNamedParameter($types, IQueryBuilder::PARAM_STR_ARRAY)));
		} elseif ($types !== null) {
			$query->where($query->expr()->eq('workflow_type', $query->createNamedParameter($types, IQueryBuilder::PARAM_STR)));
		}

		$workflows = [];

		$result = $query->execute();
		while ($row = $result->fetch()) {
			$workflows[] = $this->createFlowFromRow($row);
		}
		$result->closeCursor();

		return $workflows;
	}

	/**
	 * @param int $id
	 * @return Flow
	 * @throws FlowDoesNotExist
	 */
	public function getFlowById($id) {
		$query = $this->connection->getQueryBuilder();

		$query->select('*')
			->from($this->tableName)
			->where($query->expr()->eq('id', $query->createNamedParameter((int) $id)));

		$result = $query->execute();
		$workflow = $result->fetch();
		$result->closeCursor();

		if (!$workflow) {
			throw new FlowDoesNotExist();
		}

		return $this->createFlowFromRow($workflow);
	}

	/**
	 * @param array $row
	 * @return Flow
	 */
	protected function createFlowFromRow(array $row) {
		$flow = $this->createFlow($row['id']);
		$flow->setName($row['workflow_name']);
		$flow->setType($row['workflow_type']);
		$flow->setConditions(\json_decode($row['workflow_conditions'], true));
		$flow->setActions(\json_decode($row['workflow_actions'], true));

		return $flow;
	}

	/**
	 * @param int $id
	 * @return Flow
	 */
	public function createFlow($id = 0) {
		return new Flow((int) $id);
	}

	/**
	 * @param Flow $flow
	 * @return int The id for the new entry
	 */
	public function addFlow(Flow $flow) {
		$query = $this->connection->getQueryBuilder();

		$query->insert($this->tableName)
			->values([
				'workflow_name' => $query->createNamedParameter($flow->getName()),
				'workflow_type' => $query->createNamedParameter($flow->getType()),
				'workflow_conditions' => $query->createNamedParameter(\json_encode($flow->getConditions())),
				'workflow_actions' => $query->createNamedParameter(\json_encode($flow->getActions())),
			]);
		$query->execute();

		return $query->getLastInsertId();
	}

	/**
	 * @param Flow $flow
	 * @return bool True if the flow got update in the database, false otherwise
	 */
	public function updateFlow(Flow $flow) {
		$query = $this->connection->getQueryBuilder();

		$query->update($this->tableName)
			->set('workflow_name', $query->createNamedParameter($flow->getName()))
			->set('workflow_type', $query->createNamedParameter($flow->getType()))
			->set('workflow_conditions', $query->createNamedParameter(\json_encode($flow->getConditions())))
			->set('workflow_actions', $query->createNamedParameter(\json_encode($flow->getActions())))
			->where($query->expr()->eq('id', $query->createNamedParameter($flow->getId())));

		return (bool) $query->execute();
	}

	/**
	 * @param Flow $flow
	 * @return bool True if the flow got deleted from the database, false otherwise
	 */
	public function deleteFlow(Flow $flow) {
		$query = $this->connection->getQueryBuilder();

		$query->delete($this->tableName)
			->where($query->expr()->eq('id', $query->createNamedParameter($flow->getId())));

		return (bool) $query->execute();
	}

	/**
	 * @param Flow $flow
	 * @return array
	 */
	public function flowToArray(Flow $flow) {
		return [
			'id' => $flow->getId(),
			'type' => $flow->getType(),
			'name' => $flow->getName(),
			'conditions' => $flow->getConditions(),
			'actions' => (array) $flow->getActions(),
		];
	}
}
