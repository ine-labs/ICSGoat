<?php
/**
 * ownCloud
 *
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 * @copyright Copyright (c) 2016, ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\windows_network_drive\lib\notification_queue;

use \OCP\IDBConnection;
use \OCP\IConfig;
use \OCP\DB\QueryBuilder\IQueryBuilder;
use \OCP\ILogger;
use \Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class NotificationQueueDBHandler {
	private $dbConnection;
	private $config;
	private $logger;

	public function __construct(IDBConnection $dbConnection, IConfig $config, ILogger $logger) {
		$this->dbConnection = $dbConnection;
		$this->config = $config;
		$this->logger = $logger;
	}

	/**
	 * @param string $action the type of notification (add, remove, modify, rename)
	 * @param string $server the SMB server generating the notification
	 * @param string $share the share (from the SMB server) generating the notification
	 * @param string[] $params parameters for the actions
	 */
	private function insertNotification($action, $server, $share, $params) {
		$hash = $this->getNotificationHash($action, $server, $share, $params);
		$stringifiedParams = $this->stringifyArray($params);

		$qb = $this->dbConnection->getQueryBuilder();
		// TODO: Review if 'Don't ask for permission' works better than check if exists + error handling
		$qb->insert('wnd_nqueue')->values(
			['notification_hash' => $qb->createNamedParameter($hash, IQueryBuilder::PARAM_STR),
				'action' => $qb->createNamedParameter($action, IQueryBuilder::PARAM_STR),
				'target_server' => $qb->createNamedParameter($server, IQueryBuilder::PARAM_STR),
				'target_share' => $qb->createNamedParameter($share, IQueryBuilder::PARAM_STR),
				'parameters' => $qb->createNamedParameter($stringifiedParams, IQueryBuilder::PARAM_STR),
				'timestamp' => $qb->createNamedParameter(\microtime(true), IQueryBuilder::PARAM_INT)]
		)->execute();
	}

	/**
	 * Get a hash to detect duplicated notifications. This isn't being used directly for debouncing
	 * purposes, but to prevent inserting duplicated notifications in the DB
	 *
	 * @param string $action the type of notification (add, remove, modify, rename)
	 * @param string $server the SMB server generating the notification
	 * @param string $share the share (from the SMB server) generating the notification
	 * @param string[] $params parameters for the actions
	 */
	private function getNotificationHash($action, $server, $share, $params) {
		$stringifiedParams = $this->stringifyArray($params);
		$hash = \sha1("$action:$server:$share:$stringifiedParams");
		return $hash;
	}

	/**
	 * json_encode the params
	 * @param string[] $params list of strings to be joined in a string
	 */
	private function stringifyArray($params) {
		return \json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	/**
	 * reverse operation of the stringifyArray function. Do not use it outside of this scope
	 */
	private function parseToArray($string) {
		return \json_decode($string);
	}

	/**
	 * Insert an "add" notification for that path
	 *
	 * @param string $server the SMB server generating the notification
	 * @param string $share the share (from the SMB server) generating the notification
	 * @param string $path the path which has been added
	 */
	public function insertAddNotification($server, $share, $path) {
		try {
			$this->insertNotification('add', $server, $share, [\trim($path, '/')]);
		} catch (UniqueConstraintViolationException $e) {
			$this->logger->debug($e->getMessage(), ['app' => 'wnd']);
		}
	}

	/**
	 * Insert an "remove" notification for that path
	 *
	 * @param string $server the SMB server generating the notification
	 * @param string $share the share (from the SMB server) generating the notification
	 * @param string $path the path which has been removed
	 */
	public function insertRemoveNotification($server, $share, $path) {
		try {
			$this->insertNotification('remove', $server, $share, [\trim($path, '/')]);
		} catch (UniqueConstraintViolationException $e) {
			$this->logger->debug($e->getMessage(), ['app' => 'wnd']);
		}
	}

	/**
	 * Insert an "modify" notification for that path
	 *
	 * @param string $server the SMB server generating the notification
	 * @param string $share the share (from the SMB server) generating the notification
	 * @param string $path the path which has been modify
	 */
	public function insertModifyNotification($server, $share, $path) {
		try {
			$this->insertNotification('modify', $server, $share, [\trim($path, '/')]);
		} catch (UniqueConstraintViolationException $e) {
			$this->logger->debug($e->getMessage(), ['app' => 'wnd']);
		}
	}

	/**
	 * Insert an "forced_modify" notification for that path. This should be handled different than
	 * the normal "modify" notification. The notification processor should respect this notification
	 * (not ignore or squeeze), and the storage should trigger an update regardless of any change
	 * (path will be rescanned no matter the changes)
	 *
	 * @param string $server the SMB server generating the notification
	 * @param string $share the share (from the SMB server) generating the notification
	 * @param string $path the path which has been modify
	 */
	public function insertForcedModifyNotification($server, $share, $path) {
		try {
			$this->insertNotification('forced_modify', $server, $share, [\trim($path, '/')]);
		} catch (UniqueConstraintViolationException $e) {
			$this->logger->debug($e->getMessage(), ['app' => 'wnd']);
		}
	}

	/**
	 * Insert an "rename" notification for that path
	 *
	 * @param string $server the SMB server generating the notification
	 * @param string $share the share (from the SMB server) generating the notification
	 * @param string $src the original path which has been renamed
	 * @param string $dst the new path which has been renamed
	 */
	public function insertRenameNotification($server, $share, $src, $dst) {
		try {
			$this->insertNotification('rename', $server, $share, [\trim($src, '/'), \trim($dst, '/')]);
		} catch (UniqueConstraintViolationException $e) {
			$this->logger->debug($e->getMessage(), ['app' => 'wnd']);
		}
	}

	/**
	 * Get a list of notifications from the queue. Notifications will be returned (filtered) sorted
	 * by timestamp. By default you'll get all the queued notifications
	 * This method won't remove the notifications from the DB, so succesive runs of this method will
	 * return the same result.
	 *
	 * @param string|null $server the target SMB server that triggered the notification or null if
	 * no filtering is requested
	 * @param string|null $share the target SMB share that triggered the notification or null if no
	 * filtering is requested. If $share is supplied, $server mustn't be null, otherwise $share will
	 * be ignored
	 * @param int $limit the maximum number of notifications you'll get if $limit > 0, otherwise get
	 * all the notifications
	 * @return array the notification list. Each item in the list will be a "notification" containing
	 * "action", "target_server", "target_share" and "parameters".
	 */
	public function getNotifications($server, $share, $limit = 0) {
		$notificationList = [];

		$qb = $this->dbConnection->getQueryBuilder();
		$query = $qb->select('*')
			->from('wnd_nqueue')
			->where($qb->expr()->eq('target_server', $qb->createNamedParameter($server, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq('target_share', $qb->createNamedParameter($share, IQueryBuilder::PARAM_STR)))
			->orderBy('timestamp', 'ASC')->addOrderBy('id');
		if ($limit > 0) {
			$query = $query->setMaxResults($limit);
		}
		$result = $query->execute();

		while ($row = $result->fetch()) {
			$notificationList[] = ['action' => $row['action'],
					'target_server' => $row['target_server'],
					'target_share' => $row['target_share'],
					'parameters' => $this->parseToArray($row['parameters'])];
		}

		return $notificationList;
	}

	/**
	 * Get the notifications from the queue and remove them. This method uses the "getNotifications"
	 * one, and in addition remove the returned notifications. This means that successive calls of
	 * this method might not return the same result.
	 * Note that this method will only remove the returned notifications. Notifications which has
	 * been filtered out won't be removed.
	 *
	 * @param string|null $server the target SMB server that triggered the notification or null if
	 * no filtering is requested
	 * @param string|null $share the target SMB share that triggered the notification or null if no
	 * filtering is requested. If $share is supplied, $server mustn't be null, otherwise $share will
	 * be ignored
	 * @param int $limit the maximum number of notifications you'll get if $limit > 0, otherwise get
	 * all the notifications
	 * @return array the notification list. Each item in the list will be a "notification" containing
	 * "action", "target_server", "target_share" and "parameters".
	 */
	public function getNotificationsAndRemove($server, $share, $limit = 0) {
		$notificationList = $this->getNotifications($server, $share, $limit);
		if (!empty($notificationList)) {
			// remove the notifications that have been retrieved so they won't be processed again the next
			// run. The rest of the notifications will remain.
			$notificationHashes = \array_map(function ($value) {
				return $this->getNotificationHash($value['action'], $value['target_server'], $value['target_share'], $value['parameters']);
			}, $notificationList);

			$qb = $this->dbConnection->getQueryBuilder();
			$params = \array_map(function ($value) use ($qb) {
				return $qb->createNamedParameter($value, IQueryBuilder::PARAM_STR);
			}, $notificationHashes);

			$qb->delete('wnd_nqueue')
					->where($qb->expr()->in('notification_hash', $params))
					->execute();
		}
		return $notificationList;
	}

	/**
	 * Get a list of servers and shares whose notifications are yet to be processed. Note that this
	 * isn't a destructive operation.
	 * The result will be something like:
	 * [
	 *  ['target_server' => '10.10.10.10', 'target_share' => 'share1'],
	 *  ['target_server' => '10.10.10.10', 'target_share' => 'share2'],
	 *  ['target_server' => '10.10.10.10', 'target_share' => 'share3'],
	 *  ['target_server' => 'server', 'target_share' => 'share']
	 * ]
	 *
	 * The result order will be based on the insertion order ("id" column). It's expected those
	 * notifications will have a lower timestamp and will be processed sooner than the rest. This
	 * should also prevent starvation
	 *
	 * NOTE: $offset will only be considered if $limit is provided. If $limit isn't provided (or it's
	 * less or equal to 0) the offset will be ignored, so all rows will be returned.
	 *
	 * @param int $limit to limit the number of results (use 0 to not limit the results)
	 * @param int $offset the result's offset
	 * @return array containing the list of servers and shares
	 */
	public function getServerAndShareList($limit = 0, $offset = 0) {
		$qb = $this->dbConnection->getQueryBuilder();
		$query = $qb->select($qb->createFunction('MIN(`id`) as `min_id`'))
				->addSelect('target_server')
				->addSelect('target_share')
				->from('wnd_nqueue')
				->groupBy('target_server')
				->addGroupBy('target_share')
				->orderBy('min_id', 'ASC');

		if ($limit > 0) {
			$query->setMaxResults($limit);
			if ($offset > 0) {
				$query->setFirstResult($offset);
			}
		}

		return $query->execute()->fetchAll();
	}

	public function conditionalDBReconnect($force = false) {
		static $timestamp = 0;
		// get the previous timestamp and set the new one
		$time = $timestamp;
		$timestamp = \time();
		// check time difference: if it's too high, close the current connection and open a new one
		$connection = $this->dbConnection;
		$maxTimeDifference = $this->config->getSystemValue('wnd.listen.reconnectAfterTime', 28800);  // 8 hours
		if ($force || $timestamp - $time >= $maxTimeDifference || !$connection->isConnected()) {
			try {
				$connection->close();
			} catch (\Exception $e) {
				$this->logger->error($e->getMessage());
			}
			if (!$connection->isConnected()) {
				$connectResult = $connection->connect();
				return $connectResult;
			}
		}
		return true;
	}
}
