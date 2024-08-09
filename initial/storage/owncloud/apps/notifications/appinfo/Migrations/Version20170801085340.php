<?php

namespace OCA\notifications\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use OCP\Migration\ISchemaMigration;

class Version20170801085340 implements ISchemaMigration {
	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];
		if ($schema->hasTable("{$prefix}notifications")) {
			return;
		}

		$table = $schema->createTable("{$prefix}notifications");
		$table->addColumn('notification_id', Type::INTEGER, ['autoincrement' => true]);
		$table->addColumn('app', Type::STRING, ['length' => 32]);
		$table->addColumn('user', Type::STRING, ['length' => 64]);
		$table->addColumn('timestamp', Type::INTEGER, ['default' => 0]);
		$table->addColumn('object_type', Type::STRING, ['length' => 64]);
		$table->addColumn('object_id', Type::STRING, ['length' => 64]);
		$table->addColumn('subject', Type::STRING, ['length' => 64]);
		$table->addColumn('subject_parameters', Type::TEXT, ['notNull' => false]);
		$table->addColumn('message', Type::STRING, ['length' => 64]);
		$table->addColumn('message_parameters', Type::TEXT, ['notNull' => false]);
		$table->addColumn('link', Type::STRING, ['length' => 4000]);
		$table->addColumn('actions', Type::TEXT, ['notNull' => false]);
		$table->addColumn('icon', Type::STRING, ['length' => 4000, 'notNull' => false]);

		$table->setPrimaryKey(['notification_id']);
		$table->addIndex(['app']);
		$table->addIndex(['user']);
		$table->addIndex(['timestamp']);
		$table->addIndex(['object_type', 'object_id']);
	}
}
