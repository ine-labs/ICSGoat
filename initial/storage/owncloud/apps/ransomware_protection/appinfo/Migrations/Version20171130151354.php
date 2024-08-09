<?php

namespace OCA\Ransomware_Protection\Migrations;

use Doctrine\DBAL\Schema\Schema;
use OCP\Migration\ISchemaMigration;

/*
 * Create initial tables for the ransomware_protection app
 */
class Version20171130151354 implements ISchemaMigration {
	/** @var  string */
	private $prefix;

	/**
	 * @param Schema $schema
	 * @param array $options
	 */
	public function changeSchema(Schema $schema, array $options) {
		$this->prefix = $options['tablePrefix'];
		$this->createRansomwareLogTable($schema);
	}

	/**
	 * @param Schema $schema
	 */
	private function createRansomwareLogTable(Schema $schema) {
		if (!$schema->hasTable("{$this->prefix}ransomware_log")) {
			$table = $schema->createTable("{$this->prefix}ransomware_log");
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'unsigned' => true,
				'notnull' => true,
				'length' => 20,
			]);
			$table->addColumn('fileid', 'bigint', [
				'unsigned' => true,
				'notnull' => true,
				'length' => 20,
			]);
			$table->addColumn('timestamp', 'bigint', [
				'unsigned' => true,
				'notnull' => true,
				'length' => 20,
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('source', 'string', [
				'notnull' => true,
				'length' => 4000,
			]);
			$table->addColumn('target', 'string', [
				'notnull' => true,
				'length' => 4000,
			]);

			$table->setPrimaryKey(['id']);

			$table->addIndex(
				['fileid'],
				'rwp_log_fileid'
			);
		}
	}
}
