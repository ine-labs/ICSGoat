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

namespace OCA\Workflow\Engine\Event;

use OCA\Workflow\Engine\Flow;
use OCA\Workflow\PublicAPI\Engine\FlowInterface;
use OCA\Workflow\PublicAPI\Event\ValidateFlowInterface;
use Symfony\Component\EventDispatcher\Event;

class ValidateFlow extends Event implements ValidateFlowInterface {

	/** @var Flow */
	protected $flow;

	/**
	 * ValidateFlow constructor.
	 *
	 * @param Flow $flow
	 */
	public function __construct($flow) {
		$this->flow = $flow;
	}

	/**
	 * @return FlowInterface
	 */
	public function getFlow() {
		return $this->flow;
	}

	/**
	 * @param array $actions
	 */
	public function setFlowActions(array $actions) {
		$this->flow->setActions($actions);
	}
}
