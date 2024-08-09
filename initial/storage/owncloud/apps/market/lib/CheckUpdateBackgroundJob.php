<?php
/**
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
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

use OC\BackgroundJob\TimedJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\Notification\IManager;

/**
 * Class CheckUpdateBackgroundJob checks for updates for enabled apps at marketplace
 *
 * @package OCA\Market
 */

class CheckUpdateBackgroundJob extends TimedJob {

	/** @var IConfig */
	private $config;
	/** @var ITimeFactory */
	private $timeFactory;
	/** @var IManager */
	private $notificationManager;
	/** @var IGroupManager */
	protected $groupManager;
	/** @var MarketService */
	private $marketService;
	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var bool|string[] */
	private $users;

	/**
	 * @param IConfig $config
	 * @param ITimeFactory $timeFactory
	 * @param IManager $notificationManager
	 * @param IGroupManager $groupManager
	 * @param MarketService $marketService
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(
		IConfig $config,
		ITimeFactory $timeFactory,
		IManager $notificationManager,
		IGroupManager $groupManager,
		MarketService $marketService,
		IURLGenerator $urlGenerator
	) {
		// Run daily
		$this->setInterval(60 * 60 * 24);

		$this->config = $config;
		$this->notificationManager = $notificationManager;
		$this->groupManager = $groupManager;
		$this->timeFactory = $timeFactory;
		$this->marketService = $marketService;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @param string $argument
	 */
	protected function run($argument) {
		$updates = $this->marketService->getUpdates();

		foreach ($updates as $appId => $appInfo) {
			$url = $this->urlGenerator->linkToRoute(
				'market.page.index'
			);
			$url .= '#/app/' . $appId;
			if ($appInfo['major'] !== false) {
				$this->createNotifications($appId, $appInfo['major'], $url);
			}
			if ($appInfo['minor'] !== false) {
				$this->createNotifications($appId, $appInfo['minor'], $url);
			}
		}
	}

	/**
	 * Create notifications for this app version
	 *
	 * @param string $app
	 * @param string $version
	 * @param string $url
	 */
	protected function createNotifications($app, $version, $url) {
		$lastNotification = $this->config->getAppValue('market', $app, false);
		if ($lastNotification === $version) {
			// We already notified about this update
			return;
		} elseif ($lastNotification !== false) {
			// Delete old updates
			$this->deleteOutdatedNotifications($app, $lastNotification);
		}

		$notification = $this->notificationManager->createNotification();
		$notification->setApp('market')
			->setDateTime(
				\DateTime::createFromFormat(
					'U',
					\strval($this->timeFactory->getTime())
				)
			)
			->setObject($app, $version)
			->setSubject('update_available')
			->setLink($url);

		foreach ($this->getUsersToNotify() as $uid) {
			$notification->setUser($uid);
			$this->notificationManager->notify($notification);
		}

		$this->config->setAppValue('market', $app, $version);
	}

	/**
	 * @return string[]
	 */
	protected function getUsersToNotify() {
		if ($this->users !== null) {
			return $this->users;
		}

		$notifyGroups = \json_decode($this->config->getAppValue('market', 'notify_groups', '["admin"]'), true);
		$this->users = [];
		foreach ($notifyGroups as $group) {
			$groupToNotify = $this->groupManager->get($group);
			if ($groupToNotify instanceof IGroup) {
				foreach ($groupToNotify->getUsers() as $user) {
					$this->users[$user->getUID()] = true;
				}
			}
		}

		$this->users = \array_keys($this->users);

		return $this->users;
	}

	/**
	 * Delete notifications for old updates
	 *
	 * @param string $app
	 * @param string $version
	 */
	protected function deleteOutdatedNotifications($app, $version) {
		$notification = $this->notificationManager->createNotification();
		$notification->setApp('market')
			->setObject($app, $version);
		$this->notificationManager->markProcessed($notification);
	}
}
