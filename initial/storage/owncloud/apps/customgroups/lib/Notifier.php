<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\CustomGroups;

use OCP\L10N\IFactory;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCA\CustomGroups\CustomGroupsDatabaseHandler;
use OCA\CustomGroups\Dav\Roles;
use OCP\IL10N;

class Notifier implements INotifier {

	/**
	 * @var IFactory
	 */
	protected $l10NFactory;

	/**
	 * @var CustomGroupsDatabaseHandler
	 */
	protected $handler;

	/**
	 * Notifier constructor.
	 *
	 * @param IFactory $l10NFactory
	 * @param CustomGroupsDatabaseHandler $handler
	 */
	public function __construct(IFactory $l10NFactory, CustomGroupsDatabaseHandler $handler) {
		$this->l10NFactory = $l10NFactory;
		$this->handler = $handler;
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws \InvalidArgumentException When the notification was not prepared by a notifier
	 */
	public function prepare(INotification $notification, $languageCode) {
		if ($notification->getApp() !== 'customgroups') {
			throw new \InvalidArgumentException();
		}

		$l = $this->l10NFactory->get('customgroups', $languageCode);
		if ($notification->getObjectType() === 'customgroup') {
			if ($notification->getSubject() === 'added_member') {
				$notification->setParsedSubject(
					$l->t('Added to group "%2$s".', $notification->getSubjectParameters())
				);
			}
			if ($notification->getMessage() === 'added_member') {
				$notification->setParsedMessage(
					$l->t('You have been added to the group "%2$s" by "%1$s".', $notification->getMessageParameters())
				);
			}

			if ($notification->getSubject() === 'removed_member') {
				$notification->setParsedSubject(
					$l->t('Removed from group "%2$s".', $notification->getSubjectParameters())
				);
			}
			if ($notification->getMessage() === 'removed_member') {
				$notification->setParsedMessage(
					$l->t('You have been removed from the group "%2$s" by "%1$s".', $notification->getMessageParameters())
				);
			}

			if ($notification->getSubject() === 'changed_member_role') {
				$groupName = $notification->getSubjectParameters()[1];
				$notification->setParsedSubject(
					$l->t('Role change in group "%1$s".', [$groupName])
				);
			}
			if ($notification->getMessage() === 'changed_member_role') {
				list($user, $group, $role) = $notification->getMessageParameters();
				$roleName = $this->formatRole($l, $role);
				$notification->setParsedMessage(
					$l->t('"%1$s" assigned the "%3$s" role for the group "%2$s" to you.', [$user, $group, $roleName])
				);
			}
		}

		return $notification;
	}

	/**
	 * Returns a human-readable role string
	 *
	 * @param IL10N $l translator
	 * @param int $role backend role value
	 * @return string translated role name
	 */
	private function formatRole($l, $role) {
		if ($role === Roles::BACKEND_ROLE_MEMBER) {
			return $l->t('Member');
		} elseif ($role === Roles::BACKEND_ROLE_ADMIN) {
			return $l->t('Group owner');
		}
		return $l->t('Unknown role');
	}
}
