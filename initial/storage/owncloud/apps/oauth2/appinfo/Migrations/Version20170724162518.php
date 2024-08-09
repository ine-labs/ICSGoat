<?php
namespace OCA\oauth2\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use OCP\Migration\ISchemaMigration;

class Version20170724162518 implements ISchemaMigration {
	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];
		$table = $schema->getTable("{$prefix}oauth2_refresh_tokens");
		if (!$table->hasColumn('access_token_id')) {
			$table->addColumn('access_token_id', Type::INTEGER, ['notNull' => false]);
		}
	}
}
