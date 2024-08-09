<?php
/**
 * ownCloud Workflow
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @copyright 2016 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\Workflow\Migration;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class ConvertTypesToStrings implements IRepairStep {

	/** @var IDBConnection */
	protected $db;

	/**
	 * ConvertTypesToStrings constructor.
	 *
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}

	/**
	 * Returns the step's name
	 *
	 * @return string
	 * @since 9.1.0
	 */
	public function getName() {
		return 'Convert the workflow types to strings';
	}

	/**
	 * Run repair step.
	 * Must throw exception on error.
	 *
	 * @since 9.1.0
	 * @param IOutput $output
	 * @throws \Exception in case of failure
	 */
	public function run(IOutput $output) {
		$output->startProgress(2);
		$this->convertType($output, 'retention', 1);
		$this->convertType($output, 'workflow_autotagging', 2);
		$output->finishProgress();
	}

	protected function convertType(IOutput $output, $string, $id) {
		$output->advance(1, 'Convert workflows of type ' . $string);

		$query = $this->db->getQueryBuilder();
		$query->update('workflows')
			->set('workflow_type', $query->createNamedParameter($string, IQueryBuilder::PARAM_STR))
			->where($query->expr()->eq('workflow_type', $query->createNamedParameter($id, IQueryBuilder::PARAM_STR)));
		$query->execute();
	}
}
