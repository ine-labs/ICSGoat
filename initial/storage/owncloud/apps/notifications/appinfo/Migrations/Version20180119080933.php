<?php
namespace OCA\notifications\Migrations;

use Doctrine\DBAL\Schema\Schema;
use OCP\Migration\ISchemaMigration;

class Version20180119080933 implements ISchemaMigration {

	/**
	 * @param Schema $schema
	 * @param array $options
	 * @throws \Doctrine\DBAL\Schema\SchemaException
	 */
	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];
		$table = $schema->getTable("${prefix}notifications");
		$column = $table->getColumn('message');
		$column->setNotnull(false);
		$column = $table->getColumn('link');
		$column->setNotnull(false);
	}
}
