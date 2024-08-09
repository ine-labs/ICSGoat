<?php
/**
 *
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Arthur Schiwon <blizzz@owncloud.com>
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
use OCP\App\IAppManager;
use OCP\App\ManagerEvent;

class App {
	/** @var Logger */
	protected $logger;
	/** @var IAppManager */
	protected $appManager;

	/**
	 * Listener constructor.
	 *
	 * @param IAppManager $appManager
	 * @param Logger $logger
	 */
	public function __construct(
		IAppManager $appManager,
		Logger $logger
	) {
		$this->logger = $logger;
		$this->appManager = $appManager;
	}

	/**
	 * @param \OCP\App\ManagerEvent $event
	 */
	public function managerEvent(ManagerEvent $event) {
		if ($event->getEvent() === ManagerEvent::EVENT_APP_ENABLE) {
			$this->logger->log('{actor} enabled app "{appname}"', [
				'appname' => $event->getAppID(),
			], [
				'action' => 'app_enabled',
				'targetApp' => $event->getAppID()
			]);
		} elseif ($event->getEvent() === ManagerEvent::EVENT_APP_ENABLE_FOR_GROUPS) {
			$this->logger->log('{actor} enabled app "{appname}" for these groups: {groups}', [
				'appname' => $event->getAppID(),
				'groups' => $event->getGroups(),
			], [
				'action' => 'app_enabled',
				'groups' => $event->getGroups(),
				'targetApp' => $event->getAppID()
			]);
		} elseif ($event->getEvent() === ManagerEvent::EVENT_APP_DISABLE) {
			$this->logger->log('{actor} disabled app "{appname}"', [
				'appname' => $event->getAppID(),
			], [
				'action' => 'app_disabled',
				'targetApp' => $event->getAppID()
			]);
		}
	}
}
