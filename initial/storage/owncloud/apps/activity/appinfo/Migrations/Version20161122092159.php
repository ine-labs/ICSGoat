<?php

namespace OCA\activity\Migrations;

use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use OCP\Migration\ISchemaMigration;

class Version20161122092159 implements ISchemaMigration {

	/**
	 * @param Schema $schema
	 * @param array $options
	 */
	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];
		$tableName = "{$prefix}activity";
		$table = $schema->getTable($tableName);
		// we only apply this step if the columns is not yet a CLOB
		if ($table->getColumn('subjectparams')->getType() === Type::getType(Type::TEXT)) {
			return;
		}

		if (\OC::$server->getDatabaseConnection()->getDatabasePlatform() instanceof OraclePlatform) {
			return;
		}
		$table = $schema->getTable($tableName);
		$table->changeColumn('subjectparams', ['type' => Type::getType('text')]);
		$table->changeColumn('messageparams', ['type' => Type::getType('text')]);
	}
}
