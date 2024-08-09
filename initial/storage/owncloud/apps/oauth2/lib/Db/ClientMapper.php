<?php
/**
 * @author Project Seminar "sciebo@Learnweb" of the University of Muenster
 * @copyright Copyright (c) 2017, University of Muenster
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
 */

namespace OCA\OAuth2\Db;

use InvalidArgumentException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\Mapper;
use OCP\IDb;

class ClientMapper extends Mapper {

	/**
	 * ClientMapper constructor.
	 *
	 * @param IDb $db Database Connection.
	 */
	public function __construct(IDb $db) {
		parent::__construct($db, 'oauth2_clients');
	}

	/**
	 * Selects a client by its ID.
	 *
	 * @param int $id The client's ID.
	 *
	 * @return Entity The client entity.
	 *
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found.
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more
	 * than one result.
	 */
	public function find($id) {
		if (!\is_int($id)) {
			throw new InvalidArgumentException('id must not be null');
		}

		$sql = 'SELECT * FROM `' . $this->tableName . '` WHERE `id` = ?';
		return $this->findEntity($sql, [$id], null, null);
	}

	/**
	 * Selects a client by its identifier.
	 *
	 * @param string $identifier The client's identifier.
	 *
	 * @return Entity The client entity.
	 *
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found.
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more
	 * than one result.
	 */
	public function findByIdentifier($identifier) {
		if (!\is_string($identifier)) {
			throw new InvalidArgumentException('identifier must not be null');
		}

		$sql = 'SELECT * FROM `' . $this->tableName . '` WHERE `identifier` = ?';
		return $this->findEntity($sql, [$identifier], null, null);
	}

	public function findByName($name) {
		if (!\is_string($name)) {
			throw new InvalidArgumentException('name must not be null');
		}
		$sql = 'SELECT * FROM `' . $this->tableName . '` WHERE `name` = ?';
		return $this->findEntity($sql, [$name], null, null);
	}

	/**
	 * Selects all clients.
	 *
	 * @param int $limit The maximum number of rows.
	 * @param int $offset From which row we want to start.
	 * @return array All clients.
	 */
	public function findAll($limit = null, $offset = null) {
		$sql = 'SELECT * FROM `' . $this->tableName . '`';
		return $this->findEntities($sql, [], $limit, $offset);
	}

	/**
	 * Selects clients by the given user ID.
	 *
	 * @param string $userId The user ID.
	 *
	 * @return array The client entities.
	 */
	public function findByUser($userId) {
		if (!\is_string($userId)) {
			throw new InvalidArgumentException('userId must not be null');
		}

		$sql = 'SELECT * FROM `' . $this->tableName . '` '
			. 'WHERE `id` IN ( '
			. 'SELECT `client_id` FROM `*PREFIX*oauth2_auth_codes` WHERE `user_id` = ? '
			. 'UNION '
			. 'SELECT `client_id` FROM `*PREFIX*oauth2_access_tokens` WHERE `user_id` = ? '
			. ')';
		return $this->findEntities($sql, [$userId, $userId], null, null);
	}

	/**
	 * Deletes all entities in the table.
	 */
	public function deleteAll() {
		$sql = 'DELETE FROM `' . $this->tableName . '`';
		$stmt = $this->execute($sql, []);
		$stmt->closeCursor();
	}
}
