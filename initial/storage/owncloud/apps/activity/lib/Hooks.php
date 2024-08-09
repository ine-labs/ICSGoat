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

namespace OCA\Activity;

use OCA\Activity\AppInfo\Application;
use OCA\Files_Sharing\Activity;
use OCP\IDBConnection;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Handles the stream and mail queue of a user when he is being deleted
 */
class Hooks {

	/**
	 * Delete remaining activities and emails when a user is deleted
	 *
	 * @param array $params The hook params
	 */
	public static function deleteUser($params) {
		$connection = \OC::$server->getDatabaseConnection();
		self::deleteUserStream($params['uid']);
		self::deleteUserMailQueue($connection, $params['uid']);
	}

	/**
	 * Delete all items of the stream
	 *
	 * @param string $user
	 */
	protected static function deleteUserStream($user) {
		// Delete activity entries
		$app = new Application();
		/** @var Data $activityData */
		$activityData = $app->getContainer()->query('ActivityData');
		$activityData->deleteActivities(['affecteduser' => $user]);
	}

	/**
	 * Delete all mail queue entries
	 *
	 * @param IDBConnection $connection
	 * @param string $user
	 */
	protected static function deleteUserMailQueue(IDBConnection $connection, $user) {
		// Delete entries from mail queue
		$queryBuilder = $connection->getQueryBuilder();

		$queryBuilder->delete('activity_mq')
			->where($queryBuilder->expr()->eq('amq_affecteduser', $queryBuilder->createParameter('user')))
			->setParameter('user', $user);
		$queryBuilder->execute();
	}

	/**
	 * Set the object ID for a received federated share activity.
	 *
	 * @param GenericEvent $params
	 */
	public static function onRemoteShareAccepted($params) {
		$shareId = $params->getArgument('shareId');
		$fileId = $params->getArgument('fileId');
		$shareRecipient = $params->getArgument('shareRecipient');

		if ($shareId === null || $fileId === null) {
			return;
		}

		$connection = \OC::$server->getDatabaseConnection();
		$queryBuilder = $connection->getQueryBuilder();

		$queryBuilder->update('activity')
			->set('object_id', $queryBuilder->createNamedParameter($fileId))
			->where($queryBuilder->expr()->eq('subject', $queryBuilder->createParameter('subject')))
			->andWhere(
				$queryBuilder->expr()->eq('affecteduser', $queryBuilder->createParameter('affecteduser'))
			)
			->andWhere(
				$queryBuilder->expr()->like('object_id', $queryBuilder->createParameter('object_id'))
			)
			->andWhere(
				$queryBuilder->expr()->like('subjectparams', $queryBuilder->createParameter('subjectparams'))
			)
			->setParameter('subject', Activity::SUBJECT_REMOTE_SHARE_RECEIVED)
			->setParameter('affecteduser', $shareRecipient)
			->setParameter('object_id', 0)
			->setParameter('subjectparams', '%{"shareId":"' . $shareId . '"}%');

		$queryBuilder->execute();
	}
}
