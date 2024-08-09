<?php
namespace OCA\notifications\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use OCP\Migration\ISchemaMigration;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version20170801152524 implements ISchemaMigration {
	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];
		$table = $schema->getTable("${prefix}notifications");
		if ($table->hasColumn('icon')) {
			return;
		}
		$table->addColumn('icon', Type::STRING, ['length' => 4000, 'notNull' => false]);
	}
}
