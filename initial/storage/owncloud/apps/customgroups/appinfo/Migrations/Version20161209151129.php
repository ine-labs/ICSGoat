<?php

namespace OCA\CustomGroups\Migrations;

use OCP\Migration\ISchemaMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create initial tables for the customgroups app
 */
class Version20161209151129 implements ISchemaMigration {
	/**
	 * @param Schema $schema
	 */
	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];
		$this->createGroupsTable($prefix, $schema);
		$this->createMembersTable($prefix, $schema);
	}

	private function createGroupsTable($prefix, Schema $schema) {
		$table = $schema->createTable("${prefix}custom_group");
		$table->addColumn('group_id', 'bigint', [
			'autoincrement' => true,
			'unsigned' => true,
			'notnull' => true,
			'length' => 20,
		]);
		$table->addColumn('uri', 'string', [
			'length' => 255,
			'notnull' => true,
		]);
		$table->addColumn('display_name', 'string', [
			'length' => 64,
			'notnull' => true,
		]);
		// TODO: find how to set sort to ascending
		$table->addUniqueIndex(['uri'], 'cg_uri_index');
		$table->setPrimaryKey(['group_id']);
	}

	private function createMembersTable($prefix, Schema $schema) {
		$table = $schema->createTable("${prefix}custom_group_member");
		$table->addColumn('group_id', 'bigint', [
			'unsigned' => true,
			'notnull' => true,
			'length' => 20,
		]);
		$table->addColumn('user_id', 'string', [
			'length' => 64,
			'notnull' => true,
		]);
		$table->addColumn('role', 'integer', [
			'length' => 4,
			'notnull' => true,
			'default' => 0,
		]);
		$table->setPrimaryKey(['group_id', 'user_id']);
	}

	/**
	 * @param Schema $schema
	 */
	public function down(Schema $schema) {
		$schema->dropTable('custom_group_member');
		$schema->dropTable('custom_group');
	}
}
