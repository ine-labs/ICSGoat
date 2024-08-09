<?php
namespace OCA\activity\Migrations;

use Doctrine\DBAL\Platforms\OraclePlatform;
use OCP\IDBConnection;
use OCP\Migration\ISqlMigration;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version20170131134507 implements ISqlMigration {
	public function sql(IDBConnection $connection) {
		if ($connection->getDatabasePlatform() instanceof OraclePlatform) {
			$tableName = "*PREFIX*activity";

			$tableName = $connection->getDatabasePlatform()->quoteIdentifier($tableName);
			return [
				"ALTER TABLE $tableName ADD (\"tmpsubjectparams\" CLOB, \"tmpmessageparams\" CLOB)",
				"UPDATE $tableName SET \"tmpsubjectparams\"=\"subjectparams\", \"tmpmessageparams\"=\"messageparams\"",
				"COMMIT",
				"ALTER TABLE $tableName DROP COLUMN \"subjectparams\"",
				"ALTER TABLE $tableName DROP COLUMN \"messageparams\"",
				"ALTER TABLE $tableName RENAME COLUMN \"tmpsubjectparams\" TO \"subjectparams\"",
				"ALTER TABLE $tableName RENAME COLUMN \"tmpmessageparams\" TO \"messageparams\""
			];
		}
		return [];
	}
}
