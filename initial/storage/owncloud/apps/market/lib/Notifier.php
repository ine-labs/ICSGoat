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

use OCP\App\IAppManager;
use OCP\L10N\IFactory;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {

	/** @var IManager */
	protected $notificationManager;

	/** @var IAppManager */
	protected $appManager;

	/** @var IFactory */
	protected $l10NFactory;

	/**
	 * Notifier constructor.
	 *
	 * @param IManager $notificationManager
	 * @param IAppManager $appManager
	 * @param IFactory $l10NFactory
	 */
	public function __construct(IManager $notificationManager, IAppManager $appManager, IFactory $l10NFactory) {
		$this->notificationManager = $notificationManager;
		$this->appManager = $appManager;
		$this->l10NFactory = $l10NFactory;
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws \InvalidArgumentException When the notification was not prepared by a notifier
	 */
	public function prepare(INotification $notification, $languageCode) {
		if (
			$notification->getApp() !== 'market'
			|| $notification->getObjectType() === 'core'
		) {
			throw new \InvalidArgumentException();
		}

		$l = $this->l10NFactory->get('market', $languageCode);
		/**
		 * @var array|null $appInfo
		 */
		$appInfo = $this->getAppInfo($notification->getObjectType());
		$appName = ($appInfo === null) ? $notification->getObjectType() : $appInfo['name'];
		$appVersions = $this->getAppVersions();
		if (isset($appVersions[$notification->getObjectType()])) {
			$this->updateAlreadyInstalledCheck($notification, $appVersions[$notification->getObjectType()]);
		} else {
			throw new \InvalidArgumentException();
		}

		$notification->setParsedSubject(
			$l->t(
				'Update for %1$s to version %2$s is available.',
				[$appName, $notification->getObjectId()]
			)
		);
		return $notification;
	}

	/**
	 * Remove the notification and prevent rendering,
	 * when either the update is installed or app was removed
	 *
	 * @param INotification $notification
	 * @param string $installedVersion
	 * @throws \InvalidArgumentException When the update is already installed
	 */
	protected function updateAlreadyInstalledCheck(INotification $notification, $installedVersion) {
		if (
			$this->appManager->getAppPath($notification->getObjectType()) === false
			|| \version_compare($notification->getObjectId(), $installedVersion, '<=')
		) {
			$this->notificationManager->markProcessed($notification);
			throw new \InvalidArgumentException();
		}
	}

	protected function getAppVersions() {
		return \OC_App::getAppVersions();
	}

	/**
	 * @param string $appId
	 * @return string[]
	 */
	protected function getAppInfo($appId) {
		return $this->appManager->getAppInfo($appId);
	}
}
