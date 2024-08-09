<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
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

namespace OCA\windows_network_drive\AppInfo;

use \OCP\AppFramework\App;
use \OCP\IContainer;
use \OCA\windows_network_drive\lib\notification_queue\NotificationQueueDBHandler;
use \OCA\windows_network_drive\lib\notification_queue\NotificationQueueProcessor;
use \OCA\windows_network_drive\lib\notification_queue\StorageFactory;
use \OCA\windows_network_drive\lib\custom_loggers\WNDConditionalLogger;
use \OCA\windows_network_drive\lib\acl\ACLHooks;
use \OCA\windows_network_drive\lib\activity\Extension;
use \Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @package OCA\windows_network_drive\Appinfo
 */
class Application extends App {
	public function __construct(array $urlParams = []) {
		parent::__construct('windows_network_drive', $urlParams);
		$container = $this->getContainer();

		$container->registerService(NotificationQueueProcessor::class, function (IContainer $c) {
			return new NotificationQueueProcessor();
		});

		$container->registerService(NotificationQueueDBHandler::class, function (IContainer $c) {
			$server = $c->query('ServerContainer');
			return new NotificationQueueDBHandler(
				$server->getDatabaseConnection(),
				$server->getConfig(),
				new WNDConditionalLogger($server->getLogger(), $server->getConfig())
			);
		});

		$container->registerService(StorageFactory::class, function (IContainer $c) {
			$server = $c->query('ServerContainer');
			return new StorageFactory(
				$server->getGlobalStoragesService(),
				$server->getUserManager(),
				$server->getGroupManager(),
				new WNDConditionalLogger($server->getLogger(), $server->getConfig())
			);
		});
	}

	public function setupSymfonyEventListeners() {
		$container = $this->getContainer();
		$server = $container->getServer();

		$config = $server->getConfig();
		if ($config->getSystemValue('wnd.listen_events.smb_acl', false)) {
			$aclHooks = $container->query(ACLHooks::class);
			$eventDispatcher = $server->getEventDispatcher();
			$eventDispatcher->addListener('smb_acl.acl.afterset', [$aclHooks, 'aclSet']);
			$eventDispatcher->addListener('smb_acl.acl.propagation.downwards.start', [$aclHooks, 'aclDownwardsPropagationStarts']);
			$eventDispatcher->addListener('smb_acl.acl.propagation.downwards.set', [$aclHooks, 'aclDownwardsPropagationSet']);
			$eventDispatcher->addListener('smb_acl.acl.propagation.downwards.end', [$aclHooks, 'aclDownwardsPropagationEnds']);
		}
	}

	public function registerExtensions() {
		$container = $this->getContainer();
		$server = $container->getServer();

		$config = $server->getConfig();
		if ($config->getSystemValue('wnd.activity.registerExtension', false)) {
			$activityManager = $server->getActivityManager();
			$activityManager->registerExtension(function () use ($container) {
				return $container->query(Extension::class);
			});
		}
	}
}
