<?php
/**
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\windows_network_drive\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use OCP\Migration\ISchemaMigration;

/** Creates initial schema */
class Version20191029123324 implements ISchemaMigration {
	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];
		if (!$schema->hasTable("{$prefix}wnd_nqueue")) {
			$table = $schema->createTable("{$prefix}wnd_nqueue");
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'unsigned' => false,
				'notnull' => true,
				'length' => 11,
			]);

			$table->addColumn('notification_hash', Type::STRING, [
				'notnull' => true,
				'length' => 40
			]);

			$table->addColumn('action', Type::STRING, [
				'notnull' => true,
				'length' => 64,
			]);

			$table->addColumn('target_server', Type::STRING, [
				'notnull' => true,
				'length' => 127
			]);

			$table->addColumn('target_share', Type::STRING, [
				'notnull' => true,
				'length' => 127
			]);

			$table->addColumn('parameters', Type::STRING, [
				'notnull' => true,
				'length' => 8100,
			]);

			$table->addColumn('timestamp', Type::FLOAT, [
				'length' => 64,
				'notnull' => true
			]);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['notification_hash'], 'nqueue_hash_index');
			$table->addIndex(['timestamp'], 'nqueue_timestamp_index');
		}
	}
}
