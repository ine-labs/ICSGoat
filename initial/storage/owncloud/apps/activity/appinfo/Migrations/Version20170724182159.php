<?php

namespace OCA\activity\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use OCP\Migration\ISchemaMigration;

/**
 * Updates column type from integer to bigint
 */

class Version20170724182159 implements ISchemaMigration {

	/**
	 * @param Schema $schema
	 * @param array $options
	 */
	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];
		
		$activityTable = $schema->getTable("{$prefix}activity");
		$activityIdColumn = $activityTable->getColumn('activity_id');
		if ($activityIdColumn->getType()->getName() !== Type::BIGINT) {
			$activityIdColumn->setType(Type::getType(Type::BIGINT));
			$activityIdColumn->setOptions(['length' => 20]);
		}
		
		$objectIdColumn = $activityTable->getColumn('object_id');
		if ($objectIdColumn->getType()->getName() !== Type::BIGINT) {
			$objectIdColumn->setType(Type::getType(Type::BIGINT));
			$objectIdColumn->setOptions(['length' => 20]);
		}
		
		$activityMqTable = $schema->getTable("{$prefix}activity_mq");
		$mailIdColumn = $activityMqTable->getColumn('mail_id');
		if ($mailIdColumn->getType()->getName() !== Type::BIGINT) {
			$mailIdColumn->setType(Type::getType(Type::BIGINT));
			$mailIdColumn->setOptions(['length' => 20]);
		}
	}
}
