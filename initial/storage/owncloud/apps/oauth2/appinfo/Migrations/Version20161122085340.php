<?php

namespace OCA\oauth2\Migrations;

use Doctrine\DBAL\Schema\Schema;
use OC\DB\MDB2SchemaReader;
use OCP\Migration\ISchemaMigration;

class Version20161122085340 implements ISchemaMigration {
	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];
		if ($schema->hasTable("{$prefix}oauth2_clients")) {
			return;
		}

		// not that valid ....
		$schemaReader = new MDB2SchemaReader(\OC::$server->getConfig(), \OC::$server->getDatabaseConnection()->getDatabasePlatform());
		$schemaReader->loadSchemaFromFile(__DIR__ . '/../database.xml', $schema);
	}
}
