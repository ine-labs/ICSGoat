<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH
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

namespace OCA\Market;

use OCA\Market\Notifier;
use OCP\AppFramework\App;
use OCP\Migration\IRepairStep;
use Symfony\Component\EventDispatcher\GenericEvent;

class Application extends App {

	/**
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct('market', $urlParams);
		// needed for translation
		// t('Market')

		$listener = $this->getContainer()->query(Listener::class);
		$dispatcher = $this->getContainer()->getServer()->getEventDispatcher();
		$dispatcher->addListener(
			IRepairStep::class . '::upgradeAppStoreApp',
			function ($event) use ($listener) {
				if ($event instanceof GenericEvent) {
					try {
						$isMajorUpdate = $event->getArgument('isMajorUpdate');
					} catch (\InvalidArgumentException $e) {
						$isMajorUpdate = false;
					}
					$listener->upgradeAppStoreApp(
						$event->getSubject(),
						$isMajorUpdate
					);
				}
			}
		);
		$dispatcher->addListener(
			IRepairStep::class . '::reinstallAppStoreApp',
			function ($event) use ($listener) {
				if ($event instanceof GenericEvent) {
					$listener->reinstallAppStoreApp($event->getSubject());
				}
			}
		);

		$manager = \OC::$server->getNotificationManager();
		$manager->registerNotifier(function () use ($manager) {
			return new Notifier(
				$manager,
				\OC::$server->getAppManager(),
				\OC::$server->getL10NFactory()
			);
		}, function () {
			$l = \OC::$server->getL10N('market');
			return [
				'id' => 'market',
				'name' => $l->t('Market notifications'),
			];
		});
	}
}
