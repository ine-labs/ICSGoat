<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\activity\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use OCP\Migration\ISchemaMigration;

/**
 * Adds migrations that were missing when running update.
 *
 * - set type to "longtext" for "subjectparams" and "messageparams" by increasing length
 *
 */
class Version20181019151118 implements ISchemaMigration {
	/**
	 * @param Schema $schema
	 * @param array $options
	 */
	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];
		
		$activityTable = $schema->getTable("{$prefix}activity");

		if (!$activityTable->hasIndex('activity_time')) {
			$activityTable->addIndex(
				['timestamp'],
				'activity_time'
			);
		}

		if (!$activityTable->hasIndex('activity_object')) {
			$activityTable->addIndex(
				['object_type', 'object_id'],
				'activity_object'
			);
		}
	}
}
