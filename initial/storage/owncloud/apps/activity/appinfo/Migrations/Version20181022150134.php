<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
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

namespace OCA\activity\Migrations;

use OCP\Migration\ISqlMigration;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use OCP\IDBConnection;

/**
 * Adds migrations that were missing when running update.
 *
 * - set type to "longtext" for "subjectparams" and "messageparams" by increasing length
 *
 */
class Version20181022150134 implements ISqlMigration {

	/**
	 * @param Schema $schema
	 * @param array $options
	 */
	public function sql(IDBConnection $connection) {
		$platform = $connection->getDatabasePlatform();
		$tableName = "*PREFIX*activity";
		$tableName = $connection->getDatabasePlatform()->quoteIdentifier($tableName);
		
		if ($platform instanceof MySqlPlatform) {
			$sqls = [
				"ALTER TABLE $tableName MODIFY COLUMN `subjectparams` LONGTEXT NOT NULL",
				"ALTER TABLE $tableName MODIFY COLUMN `messageparams` LONGTEXT NULL",
			];
		} else {
			$sqls = [];
		}

		return $sqls;
	}
}
