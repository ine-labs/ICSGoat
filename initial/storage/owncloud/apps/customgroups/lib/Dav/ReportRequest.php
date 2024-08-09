<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
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

namespace OCA\CustomGroups\Dav;

use Sabre\Xml\Element\Base;
use Sabre\Xml\Element\KeyValue;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

use OCA\CustomGroups\Search;
use OCP\IDBConnection;
use OCP\ILogger;
use OCA\CustomGroups\Dav\Roles;

/**
 * Report request
 */
class ReportRequest implements XmlDeserializable {

	/**
	 * @var string[]
	 */
	private $properties;

	/**
	 * @var Search
	 */
	private $search;

	/**
	 * Constructs a new search
	 *
	 * @param string $pattern pattern or null for none
	 * @param int $offset offset or null for none
	 * @param int $limit limit or null for none
	 */
	public function __construct($properties = null, $search = null) {
		$this->properties = $properties;
		$this->search = $search;
	}

	/**
	 * Returns the requested properties
	 *
	 * @return string[]
	 */
	public function getProperties() {
		return $this->properties;
	}

	/**
	 * Returns the search
	 *
	 * @return Search search
	 */
	public function getSearch() {
		return $this->search;
	}

	/**
	 * @param Reader $reader
	 * @return mixed
	 */
	public static function xmlDeserialize(Reader $reader) {
		$request = new ReportRequest();

		$elems = (array)$reader->parseInnerTree([
			'{DAV:}prop' => KeyValue::class,
			'{http://owncloud.org/ns}search' => KeyValue::class,
		]);

		if (!\is_array($elems)) {
			$elems = [];
		}

		$search = null;
		$properties = [];

		foreach ($elems as $elem) {
			switch ($elem['name']) {
				case '{http://owncloud.org/ns}search':
					$value = $elem['value'];
					$search = new Search();
					if (isset($value['{http://owncloud.org/ns}pattern'])) {
						$search->setPattern($value['{http://owncloud.org/ns}pattern']);
					}
					if (isset($value['{http://owncloud.org/ns}limit'])) {
						$search->setLimit((int)$value['{http://owncloud.org/ns}limit']);
					}
					if (isset($value['{http://owncloud.org/ns}offset'])) {
						$search->setOffset((int)$value['{http://owncloud.org/ns}offset']);
					}
					break;
				case '{DAV:}prop':
					$properties = \array_keys($elem['value']);
					break;
			}
		}

		return new ReportRequest($properties, $search);
	}
}
