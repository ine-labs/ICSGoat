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

class RefreshTokenMapper extends Mapper {

	/**
	 * RefreshTokenMapper constructor.
	 *
	 * @param IDb $db Database Connection.
	 */
	public function __construct(IDb $db) {
		parent::__construct($db, 'oauth2_refresh_tokens');
	}

	/**
	 * Selects an refresh code by its ID.
	 *
	 * @param int $id The refresh code's ID.
	 *
	 * @return Entity The refresh code entity.
	 *
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found.
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more
	 * than one result.
	 */
	public function find($id) {
		if (!\is_int($id)) {
			throw new InvalidArgumentException('Argument id must be an int');
		}

		$sql = 'SELECT * FROM `' . $this->tableName . '` WHERE `id` = ?';
		return $this->findEntity($sql, [$id], null, null);
	}

	/**
	 * Selects an refresh token by its token.
	 *
	 * @param string $token The refresh token.
	 *
	 * @return Entity The refresh token entity.
	 *
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found.
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more
	 * than one result.
	 */
	public function findByToken($token) {
		if (!\is_string($token)) {
			throw new InvalidArgumentException('Argument token must be a string');
		}

		$sql = 'SELECT * FROM `' . $this->tableName . '` WHERE `token` = ?';
		return $this->findEntity($sql, [$token], null, null);
	}

	/**
	 * Selects all refresh codes.
	 *
	 * @param int $limit The maximum number of rows.
	 * @param int $offset From which row we want to start.
	 *
	 * @return array All refresh codes.
	 */
	public function findAll($limit = null, $offset = null) {
		$sql = 'SELECT * FROM `' . $this->tableName . '`';
		return $this->findEntities($sql, [], $limit, $offset);
	}

	/**
	 * Deletes all refresh tokens for given client and user ID.
	 *
	 * @param int|mixed $clientId The client ID.
	 * @param string $userId The user ID.
	 */
	public function deleteByClientUser($clientId, $userId) {
		if (!\is_int($clientId) || !\is_string($userId)) {
			throw new InvalidArgumentException('Argument client_id must be an int and user_id must be a string');
		}

		$sql = 'DELETE FROM `' . $this->tableName . '` ' . 'WHERE `client_id` = ? AND `user_id` = ?';
		$stmt = $this->execute($sql, [$clientId, $userId], null, null);
		$stmt->closeCursor();
	}

	/**
	 * Deletes all refresh tokens for a given client_id.
	 * Used for client deletion by the administrator in the
	 * admin settings.
	 *
	 * @param int $clientId The client ID
	 * @see SettingsController::deleteClient()
	 */
	public function deleteByClient($clientId) {
		if (!\is_int($clientId)) {
			throw new InvalidArgumentException('Argument client_id must be an int');
		}

		$sql = 'DELETE FROM `' . $this->tableName . '` ' . 'WHERE `client_id` = ?';
		$stmt = $this->execute($sql, [$clientId], null, null);
		$stmt->closeCursor();
	}

	/**
	 * Deletes all refresh tokens for the given user ID.
	 * Used for the token deletion by the UserHooks.
	 *
	 * @param string $userId The user ID.
	 */
	public function deleteByUser($userId) {
		if (!\is_string($userId)) {
			throw new InvalidArgumentException('Argument user_id must be a string');
		}

		$sql = 'DELETE FROM `' . $this->tableName . '` WHERE `user_id` = ?';
		$stmt = $this->execute($sql, [$userId], null, null);
		$stmt->closeCursor();
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
