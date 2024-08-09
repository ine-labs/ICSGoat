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

namespace OCA\Workflow\PublicAPI\Event;

use OCA\Workflow\PublicAPI\Engine\FlowInterface;

interface ValidateFlowInterface {
	public const FLOW_VALIDATE = 'validateFlow';

	/**
	 * @return FlowInterface
	 */
	public function getFlow();

	/**
	 * @param array $actions
	 */
	public function setFlowActions(array $actions);

	/**
	 * Stops the propagation of the event to further event listeners.
	 */
	public function stopPropagation();
}
