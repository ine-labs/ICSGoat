<?php
namespace OCA\oauth2\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use OCP\Migration\ISchemaMigration;

class Version20201123114127 implements ISchemaMigration {
	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];
		$table = $schema->getTable("{$prefix}oauth2_clients");
		if (!$table->hasColumn('trusted')) {
			$table->addColumn('trusted', Types::BOOLEAN, ['notNull' => true, 'default' => false]);
		}
	}
}
