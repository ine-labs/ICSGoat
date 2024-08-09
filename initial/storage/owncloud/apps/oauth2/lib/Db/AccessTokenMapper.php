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

class AccessTokenMapper extends Mapper {

	/** @var ILogger */
	private $logger;

	/** @var string */
	private $appName;

	/**
	 * AccessTokenMapper constructor.
	 *
	 * @param IDb $db Instance of the Db abstraction layer.
	 * @param ILogger $logger The logger.
	 * @param string $AppName The app's name.
	 */
	public function __construct(IDb $db, ILogger $logger, $AppName) {
		parent::__construct($db, 'oauth2_access_tokens');

		$this->logger = $logger;
		$this->appName = $AppName;
	}

	/**
	 * Selects an access token by its ID.
	 *
	 * @param int $id The access token's ID.
	 *
	 * @return Entity The access token entity.
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
	 * Selects an access token by its token.
	 *
	 * @param string $token The access token.
	 *
	 * @return Entity The access token entity.
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
	 * Selects all access tokens.
	 *
	 * @param int $limit The maximum number of rows.
	 * @param int $offset From which row we want to start.
	 *
	 * @return array All access tokens.
	 */
	public function findAll($limit = null, $offset = null) {
		$sql = 'SELECT * FROM `' . $this->tableName . '`';
		return $this->findEntities($sql, [], $limit, $offset);
	}

	/**
	 * Deletes all access tokens for a given client_id.
	 * Used for client deletion by the administrator in the
	 * admin settings.
	 *
	 * @param int $clientId The client ID
	 * @see \OCA\OAuth2\Controller\SettingsController::deleteClient()
	 */
	public function deleteByClient($clientId) {
		if (!\is_int($clientId)) {
			throw new InvalidArgumentException('Argument client_id must be an int');
		}

		$sql = 'DELETE FROM `' . $this->tableName . '` ' . ' WHERE `client_id` = ?';
		$stmt = $this->execute($sql, [$clientId], null, null);
		$stmt->closeCursor();
	}

	/**
	 * Deletes all access tokens for the given user ID.
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
	 * Deletes all access tokens for given client and user ID
	 * Used for the token deletion by the UserHooks.
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
	 * Deletes all access tokens that expired one week before.
	 */
	public function cleanUp() {
		$this->logger->info('Cleaning up expired Access Tokens.', ['app' => $this->appName]);

		$sql = 'DELETE FROM `' . $this->tableName . '` WHERE `expires` <= ' . (\time() - 60 * 60 * 24 * 7);
		$stmt = $this->execute($sql);
		$stmt->closeCursor();
	}
}
