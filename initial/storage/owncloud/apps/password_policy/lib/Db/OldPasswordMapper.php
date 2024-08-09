<?php
/**
 *
 * @author Semih Serhat Karakaya <karakayasemi@itu.edu.tr>
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\PasswordPolicy\Db;

use Generator;
use OCP\AppFramework\Db\Mapper;
use OCP\IDBConnection;
use Doctrine\DBAL\Platforms\OraclePlatform;

class OldPasswordMapper extends Mapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'user_password_history');
	}

	/**
	 * @param string $uid
	 * @param int $length
	 * @param bool $excludeForceExpired
	 * @return OldPassword[]
	 */
	public function getOldPasswords($uid, $length, $excludeForceExpired = true) {
		/* @var OCP\DB\QueryBuilder\IQueryBuilder $qb */
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('user_password_history')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->orderBy('id', 'desc')
			->setMaxResults($length);
		if ($excludeForceExpired) {
			if ($this->db->getDatabasePlatform() instanceof OraclePlatform) {
				// to_char() because for Oracle we need to convert CLOB to STRING...
				$passwordField = $qb->createFunction('TO_CHAR(`password`)');
			} else {
				$passwordField = 'password';
			}
			$qb->andWhere($qb->expr()->neq($passwordField, $qb->expr()->literal(OldPassword::EXPIRED)));
		}
		$result = $qb->execute();
		/* @phan-suppress-next-line PhanDeprecatedFunction */
		$rows = $result->fetchAll();
		/* @phan-suppress-next-line PhanDeprecatedFunction */
		$result->closeCursor();
		return \array_map(function ($row) {
			return OldPassword::fromRow($row);
		}, $rows);
	}

	/**
	 * @param string $uid
	 * @return OldPassword|null
	 */
	public function getLatestPassword($uid) {
		$passwords = $this->getOldPasswords($uid, 1, false);
		if (\count($passwords) === 0) {
			return null;
		}
		return $passwords[0];
	}

	/**
	 * Get the passwords that are about to expire or already expired.
	 * Last passwords which have been changed before the timestamp are the ones
	 * selectable. Previous stored passwords won't be included
	 * In addition, passwords from multiple users are expected
	 * @param int $maxTimestamp timestamp marker, last passwords changed before
	 * the timestamp will be selected
	 * @return Generator to traverse the selected passwords
	 */
	public function getPasswordsAboutToExpire($maxTimestamp) {
		$query = "SELECT `f`.`id`, `f`.`uid`, `f`.`password`, `f`.`change_time`
				  FROM (
					SELECT `uid`, max(`id`) AS `maxid`
					FROM `*PREFIX*user_password_history`
					GROUP BY `uid`
				  ) `x` INNER JOIN `*PREFIX*user_password_history` `f`
				  ON `f`.`uid` = `x`.`uid`
				    AND `f`.`id` = `x`.`maxid`
				  WHERE `f`.`change_time` < ?";

		$stmt = $this->db->prepare($query);
		$stmt->bindValue(1, $maxTimestamp);
		$result = $stmt->execute();

		if ($result === false) {
			/* @phan-suppress-next-line PhanDeprecatedFunction */
			$info = \json_encode($stmt->errorInfo());
			$message = "Cannot get the passwords that are about to expire. Error: {$info}";
			\OCP\Util::writeLog('password_policy', $message, \OCP\Util::ERROR);
			return;
		}

		/* @phan-suppress-next-line PhanDeprecatedFunction */
		while ($row = $stmt->fetch()) {
			yield OldPassword::fromRow($row);
		}
		/* @phan-suppress-next-line PhanDeprecatedFunction */
		$stmt->closeCursor();
	}

	/**
	 * Remove the entries of user from the user_password_history table once the
	 * user is deleted from oC
	 *
	 * @param string $uid uid of a user
	 */
	public function cleanUserHistory($uid) {
		/* @var IQueryBuilder $qb */
		$qb = $this->db->getQueryBuilder();
		$qb->delete('user_password_history')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)));
		$qb->execute();
	}
}
