<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\Notifications;

use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {

	/**
	 * @inheritdoc
	 */
	public function prepare(INotification $notification, $languageCode) {
		if ($notification->getApp() !== 'notifications') {
			throw new \InvalidArgumentException();
		}
		if ($notification->getObjectType() !== 'admin-notification') {
			throw new \InvalidArgumentException();
		}
		if ($notification->getMessage() === 'admin-notification') {
			$params = $notification->getMessageParameters();
			if (isset($params[0]) && $params[0] !== '') {
				$notification->setParsedMessage($params[0]);
			}
		}
		$notification->setParsedSubject($notification->getSubjectParameters()[0]);
		return $notification;
	}
}
