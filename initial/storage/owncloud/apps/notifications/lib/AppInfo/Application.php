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

namespace OCA\Notifications\AppInfo;

use OCA\Notifications\Capabilities;
use OCA\Notifications\Controller\EndpointController;
use OCA\Notifications\Handler;
use OCA\Notifications\App as NotificationApp;
use OCA\Notifications\Notifier;
use OCP\AppFramework\App;
use OCP\IContainer;
use OCP\Notification\Events\RegisterConsumerEvent;
use OCP\Notification\Events\RegisterNotifierEvent;
use Symfony\Component\EventDispatcher\GenericEvent;

class Application extends App {
	public function __construct(array $urlParams = []) {
		parent::__construct('notifications', $urlParams);
		$container = $this->getContainer();

		$container->registerService('EndpointController', function (IContainer $c) {
			/** @var \OC\Server $server */
			$server = $c->query('ServerContainer');

			return new EndpointController(
				$c->query('AppName'),
				$server->getRequest(),
				new Handler(
					$server->getDatabaseConnection(),
					$server->getNotificationManager()
				),
				$server->getNotificationManager(),
				$server->getConfig(),
				$server->getUserSession(),
				$server->getURLGenerator()
			);
		});

		$container->registerService('Capabilities', function (IContainer $c) {
			return new Capabilities();
		});
		$container->registerCapability('Capabilities');
	}

	public function setupConsumerAndNotifier() {
		$container = $this->getContainer();

		$dispatcher = $container->getServer()->getEventDispatcher();

		$dispatcher->addListener(RegisterConsumerEvent::NAME, function (RegisterConsumerEvent $event) use ($container) {
			$event->registerNotificationConsumer($container->query(NotificationApp::class));
		});

		$dispatcher->addListener(RegisterNotifierEvent::NAME, function (RegisterNotifierEvent $event) use ($container) {
			$l10n = $container->getServer()->getL10N('notifications');
			$event->registerNotifier($container->query(Notifier::class), 'notifications', $l10n->t('Admin notifications'));
		});
	}

	public function setupSymfonyEventListeners() {
		$container = $this->getContainer();

		$container->getServer()->getEventDispatcher()->addListener('user.afterdelete', function (GenericEvent $event) use ($container) {
			$handler = $container->query(Handler::class);

			$handler->deleteUserNotifications($event->getArgument('uid'));
		});
	}
}
