<?php

namespace OCA\activity\Migrations;

use Doctrine\DBAL\Schema\Schema;
use OC\DB\MDB2SchemaReader;
use OCP\Migration\ISchemaMigration;

class Version20161122085340 implements ISchemaMigration {
	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];
		if ($schema->hasTable("{$prefix}activity")) {
			return;
		}

		// not that valid ....
		$schemaReader = new MDB2SchemaReader(\OC::$server->getConfig(), \OC::$server->getDatabaseConnection()->getDatabasePlatform());
		$schemaReader->loadSchemaFromFile(__DIR__ . '/../database.xml', $schema);
	}
}
