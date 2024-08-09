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
use OCP\ILogger;

class AuthorizationCodeMapper extends Mapper {

	/** @var ILogger */
	private $logger;

	/** @var string */
	private $appName;

	/**
	 * AuthorizationCodeMapper constructor.
	 *
	 * @param IDb $db Instance of the Db abstraction layer.
	 * @param ILogger $logger The logger.
	 * @param string $AppName The app's name.
	 */
	public function __construct(IDb $db, ILogger $logger, $AppName) {
		parent::__construct($db, 'oauth2_auth_codes');

		$this->logger = $logger;
		$this->appName = $AppName;
	}

	/**
	 * Selects an authorization code by its ID.
	 *
	 * @param int $id The authorization code's ID.
	 *
	 * @return Entity The authorization code entity.
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
	 * Selects an authorization code by its code.
	 *
	 * @param string $code The authorization code.
	 *
	 * @return Entity The authorization code entity.
	 *
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found.
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more
	 * than one result.
	 */
	public function findByCode($code) {
		if (!\is_string($code)) {
			throw new InvalidArgumentException('Argument code must be a string');
		}

		$sql = 'SELECT * FROM `' . $this->tableName . '` WHERE `code` = ?';
		return $this->findEntity($sql, [$code], null, null);
	}

	/**
	 * Selects all authorization codes.
	 *
	 * @param int $limit The maximum number of rows.
	 * @param int $offset From which row we want to start.
	 *
	 * @return array All authorization codes.
	 */
	public function findAll($limit = null, $offset = null) {
		$sql = 'SELECT * FROM `' . $this->tableName . '`';
		return $this->findEntities($sql, [], $limit, $offset);
	}

	/**
	 * Deletes all authorization codes for a given client ID.
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
	 * Deletes all authorization codes for the given user ID.
	 * Used for the authorization code deletion by the UserHooks.
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
	 * Deletes all authorization codes for given client and user ID.
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
	 * Deletes all entities in the table.
	 */
	public function deleteAll() {
		$sql = 'DELETE FROM `' . $this->tableName . '`';
		$stmt = $this->execute($sql, []);
		$stmt->closeCursor();
	}

	/**
	 * Deletes all authorization codes that expired one week before.
	 */
	public function cleanUp() {
		$this->logger->info('Cleaning up expired Authorization Codes.', ['app' => $this->appName]);

		$sql = 'DELETE FROM `' . $this->tableName . '` WHERE `expires` <= ' . (\time() - 60 * 60 * 24 * 7);
		$stmt = $this->execute($sql);
		$stmt->closeCursor();
	}
}
