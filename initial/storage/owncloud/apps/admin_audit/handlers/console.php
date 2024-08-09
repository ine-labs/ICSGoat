<?php
/**
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

namespace OCA\Admin_Audit\Handlers;

use OCA\Admin_Audit\Logger;
use OCP\Console\ConsoleEvent;

class Console {
	/** @var Logger */
	protected $logger;

	/**
	 * Listener constructor.
	 *
	 * @param Logger $logger
	 */
	public function __construct(Logger $logger) {
		$this->logger = $logger;
	}

	/**
	 * @param ConsoleEvent $event
	 */
	public function consoleEvent(ConsoleEvent $event) {
		if ($event->getEvent() === ConsoleEvent::EVENT_RUN) {
			$this->logger->log('Command "{command}" was run', [
				'command' => \implode(' ', $event->getArguments()),
			], [
				'action' => 'command_executed',
				'command' => \implode(' ', $event->getArguments())
			]);
		}
	}
}
