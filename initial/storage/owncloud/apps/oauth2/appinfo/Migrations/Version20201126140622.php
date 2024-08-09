<?php
namespace OCA\oauth2\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use OCP\Migration\ISchemaMigration;

class Version20201126140622 implements ISchemaMigration {
	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];
		$table = $schema->getTable("{$prefix}oauth2_auth_codes");
		if (!$table->hasColumn('code_challenge')) {
			$table->addColumn('code_challenge', Type::STRING, ['notNull' => false]);
		}
		if (!$table->hasColumn('code_challenge_method')) {
			$table->addColumn('code_challenge_method', Type::STRING, ['notNull' => false]);
		}
	}
}
