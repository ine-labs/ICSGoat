<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\CustomGroups;

use OCP\IDBConnection;
use OCP\ILogger;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Database handler for custom groups
 */
class CustomGroupsDatabaseHandler {
	public const ROLE_MEMBER = 0;
	public const ROLE_ADMIN = 1;

	/**
	 * Database connection
	 *
	 * @var IDBConnection
	 */
	private $dbConn;

	/**
	 * Logger
	 *
	 * @var ILogger
	 */
	private $logger;

	/**
	 * Constructor
	 *
	 * @param IDBConnection $dbConn database connection
	 * @param ILogger $logger logger
	 */
	public function __construct(IDBConnection $dbConn, ILogger $logger) {
		$this->dbConn = $dbConn;
		$this->logger = $logger;
	}

	/**
	 * Checks whether the user is member of a group or not.
	 *
	 * @param string $uid uid of the user
	 * @param int $numericGroupId id of the group
	 * @return boolean true if the user is in group, false otherwise
	 * @throws \Doctrine\DBAL\Exception\DriverException in case of database exception
	 */
	public function inGroup($uid, $numericGroupId) {
		$qb = $this->dbConn->getQueryBuilder();

		$cursor = $qb->select('user_id')
			->from('custom_group_member')
			->where($qb->expr()->eq('group_id', $qb->createNamedParameter($numericGroupId)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($uid)))
			->execute();

		$result = $cursor->fetch();
		$cursor->closeCursor();

		return $result ? true : false;
	}

	/**
	 * Checks whether the user is member of a group or not.
	 *
	 * @param string $uid uid of the user
	 * @param string $uri uri
	 * @return boolean true if the user is in group, false otherwise
	 * @throws \Doctrine\DBAL\Exception\DriverException in case of database exception
	 */
	public function inGroupByUri($uid, $uri) {
		$qb = $this->dbConn->getQueryBuilder();

		$cursor = $qb->select('user_id')
			->from('custom_group_member', 'm')
			->from('custom_group', 'g')
			->where($qb->expr()->eq('g.group_id', 'm.group_id'))
			->where($qb->expr()->eq('uri', $qb->createNamedParameter($uri)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($uid)))
			->execute();

		$result = $cursor->fetch();
		$cursor->closeCursor();

		return $result ? true : false;
	}

	/**
	 * Get all group memberships of the given user
	 *
	 * @param string $uid Name of the user
	 * @param Search $search search
	 * @return array an array of member info
	 * @throws \Doctrine\DBAL\Exception\DriverException in case of database exception
	 */
	public function getUserMemberships($uid, $search = null) {
		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('m.group_id', 'm.user_id', 'm.role', 'g.uri', 'g.display_name')
			->from('custom_group_member', 'm')
			->from('custom_group', 'g')
			->where($qb->expr()->eq('g.group_id', 'm.group_id'))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($uid)))
			->orderBy('m.group_id', 'ASC');

		$this->applySearch($qb, $search, 'display_name');

		$cursor = $qb->execute();

		$results = [];
		while ($row = $cursor->fetch()) {
			$result = $this->formatMemberInfo($row);
			$result['uri'] = $row['uri'];
			$result['display_name'] = $row['display_name'];
			$results[] = $result;
		}
		$cursor->closeCursor();

		return $results;
	}

	/**
	 * Searches groups by display names where the given
	 * search string can appear anywhere within the display name
	 * field.
	 *
	 * @param Search $search search
	 * @return array an array of group info
	 * @throws \Doctrine\DBAL\Exception\DriverException in case of database exception
	 */
	public function searchGroups($search = null) {
		$qb = $this->dbConn->getQueryBuilder();
		$qb->select(['group_id', 'uri', 'display_name'])
			->from('custom_group')
			->orderBy('display_name', 'ASC');

		$this->applySearch($qb, $search, 'display_name');

		$cursor = $qb->execute();
		$groups = $cursor->fetchAll();
		$cursor->closeCursor();
		return $groups;
	}

	/**
	 * Returns the info for a given group.
	 *
	 * @param string $numericGroupId group id
	 * @return array|null group info or null if not found
	 * @throws \Doctrine\DBAL\Exception\DriverException in case of database exception
	 */
	public function getGroup($numericGroupId) {
		return $this->getGroupBy('group_id', $numericGroupId);
	}

	/**
	 * Returns the info for a given group.
	 *
	 * @param string $uri group uri
	 * @return array|null group info or null if not found
	 * @throws \Doctrine\DBAL\Exception\DriverException in case of database exception
	 */
	public function getGroupByUri($uri) {
		return $this->getGroupBy('uri', $uri);
	}

	/**
	 * Returns the info for a given group.
	 *
	 * @param string $field field to filter by
	 * @param string $fieldValue field value
	 * @return array|null group info or null if not found
	 * @throws \Doctrine\DBAL\Exception\DriverException in case of database exception
	 */
	private function getGroupBy($field, $fieldValue) {
		$qb = $this->dbConn->getQueryBuilder();
		$cursor = $qb->select(['group_id', 'uri', 'display_name'])
			->from('custom_group')
			->where($qb->expr()->eq($field, $qb->createNamedParameter($fieldValue)))
			->execute();
		$result = $cursor->fetch();
		$cursor->closeCursor();

		if (!$result) {
			return null;
		}

		return $result;
	}

	/**
	 * Get groups by display name in a case-insensitive manner.
	 *
	 * @param string $displayName numeric group id
	 * @return array[] array of group infos
	 * @throws \Doctrine\DBAL\Exception\DriverException in case of database exception
	 */
	public function getGroupsByDisplayName($displayName) {
		$qb = $this->dbConn->getQueryBuilder();
		$cursor = $qb->select(['group_id', 'uri', 'display_name'])
			->from('custom_group')
			->where($qb->expr()->eq($qb->createFunction('LOWER(`display_name`)'), $qb->createNamedParameter(\strtolower($displayName))))
			->orderBy('display_name', 'ASC')
			->execute();

		$results = $cursor->fetchAll();
		$cursor->closeCursor();

		return $results;
	}

	/**
	 * Returns the info for all groups.
	 *
	 * @param Search $search search
	 *
	 * @return array array of group info
	 * @throws \Doctrine\DBAL\Exception\DriverException in case of database exception
	 */
	public function getGroups($search = null) {
		return $this->searchGroups($search);
	}

	/**
	 * Creates a new group
	 *
	 * @param string $uri group URI
	 * @param string $displayName display name
	 * @return int group id
	 * @throws \Doctrine\DBAL\Exception\DriverException in case of database exception
	 */
	public function createGroup($uri, $displayName = null) {
		try {
			$result = $this->dbConn->insertIfNotExist('*PREFIX*custom_group', [
				'uri' => $uri,
				'display_name' => $displayName,
			]);
		} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
			$this->logger->logException($e, [
				'app' => 'customgroups',
				'message' => 'Cannot create a group that already exists'
			]);
			return null;
		}

		if ($result === 1) {
			return $this->dbConn->lastInsertId('*PREFIX*custom_group');
		}

		return null;
	}

	/**
	 * Deletes the group with the given id
	 *
	 * @param int $gid numeric group id
	 * @return true if group was deleted, false otherwise
	 * @throws \Doctrine\DBAL\Exception\DriverException in case of database exception
	 */
	public function deleteGroup($gid) {
		$this->dbConn->beginTransaction();
		// Delete the group-user relation
		$qb = $this->dbConn->getQueryBuilder();
		$qb->delete('custom_group_member')
			->where($qb->expr()->eq('group_id', $qb->createNamedParameter($gid)))
			->execute();

		// Delete the group
		$qb = $this->dbConn->getQueryBuilder();
		$result = $qb->delete('custom_group')
			->where($qb->expr()->eq('group_id', $qb->createNamedParameter($gid)))
			->execute();
		$this->dbConn->commit();

		return ($result === 1);
	}

	/**
	 * Update group info
	 *
	 * @param int $gid numeric group id
	 * @param int $uri group uri
	 * @param string $displayName group display name
	 * @return bool true if the info got updated, false otherwise
	 * @throws \Doctrine\DBAL\Exception\DriverException in case of database exception
	 */
	public function updateGroup($gid, $uri, $displayName) {
		$qb = $this->dbConn->getQueryBuilder();
		$result = $qb->update('custom_group')
			->set('uri', $qb->createNamedParameter($uri))
			->set('display_name', $qb->createNamedParameter($displayName))
			->where($qb->expr()->eq('group_id', $qb->createNamedParameter($gid)))
			->execute();

		return $result === 1;
	}

	/**
	 * Add a user to a group.
	 *
	 * @param string $uid user id
	 * @param int $gid numeric group id
	 * @param int $role initial permission
	 * @return boolean true if user was added, false otherwise
	 * @throws \Doctrine\DBAL\Exception\DriverException in case of database exception
	 */
	public function addToGroup($uid, $gid, $role = self::ROLE_MEMBER) {
		$result = $this->dbConn->insertIfNotExist('*PREFIX*custom_group_member', [
			'user_id' => $uid,
			'group_id' => $gid,
			'role' => $role
		]);

		return ($result === 1);
	}

	/**
	 * Remove a user from a group.
	 *
	 * @param string $uid user id
	 * @param int $gid numeric group id
	 * @return bool true if user was removed, false otherwise
	 * @throws \Doctrine\DBAL\Exception\DriverException in case of database exception
	 */
	public function removeFromGroup($uid, $gid) {
		$qb = $this->dbConn->getQueryBuilder();
		$result = $qb->delete('custom_group_member')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($uid)))
			->andWhere($qb->expr()->eq('group_id', $qb->createNamedParameter($gid)))
			->execute();

		return ($result === 1);
	}

	/**
	 * Returns the group members
	 *
	 * @param int $gid numeric group id
	 * filter by non-admin-only or admin-only
	 * @param Search $search search
	 * @return array array of member info
	 * @throws \Doctrine\DBAL\Exception\DriverException in case of database exception
	 */
	public function getGroupMembers($gid, $search = null) {
		$qb = $this->dbConn->getQueryBuilder();
		$qb->select(['user_id', 'group_id', 'role'])
			->from('custom_group_member')
			->where($qb->expr()->eq('group_id', $qb->createNamedParameter($gid)))
			->orderBy('user_id', 'ASC');

		// TODO: also by display name
		$this->applySearch($qb, $search, 'user_id');

		$cursor = $qb->execute();

		$results = [];
		while ($row = $cursor->fetch()) {
			$results[] = $this->formatMemberInfo($row);
		}
		$cursor->closeCursor();

		return $results;
	}

	/**
	 * Returns a specific group member info
	 *
	 * @param int $gid numeric group id
	 * @param int $uid user id
	 * @return array member info
	 * @throws \Doctrine\DBAL\Exception\DriverException in case of database exception
	 */
	public function getGroupMemberInfo($gid, $uid) {
		$qb = $this->dbConn->getQueryBuilder();
		$cursor = $qb->select(['user_id', 'group_id', 'role'])
			->from('custom_group_member')
			->where($qb->expr()->eq('group_id', $qb->createNamedParameter($gid)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($uid)))
			->orderBy('user_id', 'ASC')
			->execute();

		$result = $cursor->fetchAll();
		$cursor->closeCursor();

		if (empty($result)) {
			return null;
		}

		return $this->formatMemberInfo($result[0]);
	}

	/**
	 * Update group member info
	 *
	 * @param int $gid numeric group id
	 * @param int $uid user id
	 * @param int $role membership role
	 * @return bool true if the info got updated, false otherwise
	 * @throws \Doctrine\DBAL\Exception\DriverException in case of database exception
	 */
	public function setGroupMemberInfo($gid, $uid, $role) {
		$qb = $this->dbConn->getQueryBuilder();
		$result = $qb->update('custom_group_member')
			->set('role', $qb->createNamedParameter($role))
			->where($qb->expr()->eq('group_id', $qb->createNamedParameter($gid)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($uid)))
			->execute();

		return $result === 1;
	}

	/**
	 * Formats the group member info from the database
	 *
	 * @param array $row database row
	 * @return array formatted array
	 */
	private function formatMemberInfo(array $row) {
		return [
			'user_id' => $row['user_id'],
			'group_id' => (int)$row['group_id'],
			'role' => (int)$row['role'],
		];
	}

	/**
	 * Apply search to the given query
	 *
	 * @param IQueryBuilder $qb query builder
	 * @param Search $search search
	 * @param string $property property to apply search on
	 * @return IQueryBuilder query builder
	 */
	private function applySearch(IQueryBuilder $qb, $search, $property = null) {
		if ($search !== null) {
			if ($search->getPattern() !== null && $property !== null) {
				$likeString = '%' . $this->dbConn->escapeLikeParameter(\strtolower($search->getPattern())) . '%';
				$qb->andWhere($qb->expr()->like($qb->createFunction('LOWER(`' . $property . '`)'), $qb->createNamedParameter($likeString)));
			}

			if ($search->getLimit() !== null) {
				$qb->setMaxResults($search->getLimit());
			}

			if ($search->getOffset() !== null) {
				$qb->setFirstResult($search->getOffset());
			}

			if ($search->getRoleFilter() !== null) {
				$qb->andWhere($qb->expr()->eq('role', $qb->createNamedParameter($search->getRoleFilter())));
			}
		}
		return $qb;
	}
}
