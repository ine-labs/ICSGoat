<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2016, Joas Schilling <nickvergessen@owncloud.com>
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

$app = new \OCA\AnnouncementCenter\AppInfo\Application();

\OC::$server->getActivityManager()->registerExtension(function () use ($app) {
	return $app->getContainer()->query('OCA\AnnouncementCenter\Activity\Extension');
});

\OC::$server->getNotificationManager()->registerNotifier(function () use ($app) {
	return $app->getContainer()->query('OCA\AnnouncementCenter\Notification\Notifier');
}, function () use ($app) {
	$l = $app->getContainer()->getServer()->getL10NFactory()->get('announcementcenter');
	return [
		'id' => 'announcementcenter',
		'name' => $l->t('Announcements'),
	];
});
