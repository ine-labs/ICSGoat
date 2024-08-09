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

namespace OCA\CustomGroups;

use OCP\IDBConnection;
use OCP\ILogger;

/**
 * Search criteria
 */
class Search {

	/**
	 * Search pattern
	 *
	 * @var string
	 */
	private $pattern;

	/**
	 * Offset
	 *
	 * @var int
	 */
	private $offset;

	/**
	 * Limit
	 *
	 * @var int
	 */
	private $limit;

	/**
	 * Role filter
	 *
	 * @var int
	 */
	private $roleFilter = null;

	/**
	 * Constructs a new search
	 *
	 * @param string $pattern pattern or null for none
	 * @param int $offset offset or null for none
	 * @param int $limit limit or null for none
	 */
	public function __construct($pattern = null, $offset = null, $limit = null) {
		$this->setPattern($pattern);
		$this->setOffset($offset);
		$this->setLimit($limit);
	}

	/**
	 * Set search pattern or null for none
	 *
	 * @param string $pattern pattern
	 */
	public function setPattern($pattern) {
		if ($pattern === '') {
			$pattern = null;
		}
		$this->pattern = $pattern;
	}

	/**
	 * Returns the search pattern
	 *
	 * @return string
	 */
	public function getPattern() {
		return $this->pattern;
	}

	/**
	 * Set offset or null for none
	 *
	 * @param int $offset offset
	 */
	public function setOffset($offset) {
		if ($offset <= 0) {
			$offset = null;
		}
		$this->offset = $offset;
	}

	/**
	 * Returns the offset
	 *
	 * @return int
	 */
	public function getOffset() {
		return $this->offset;
	}

	/**
	 * Set limit or null for no limit.
	 *
	 * @param int $limit limit
	 */
	public function setLimit($limit) {
		if ($limit <= 0) {
			$limit = null;
		}
		$this->limit = $limit;
	}

	/**
	 * Returns the limit
	 *
	 * @return int
	 */
	public function getLimit() {
		return $this->limit;
	}

	/**
	 * Set role filter
	 *
	 * @param int $roleFilter role as integer
	 */
	public function setRoleFilter($roleFilter) {
		if ($roleFilter === '') {
			$roleFilter = null;
		}
		$this->roleFilter = $roleFilter;
	}

	/**
	 * Returns the role filter
	 *
	 * @return int role filter
	 */
	public function getRoleFilter() {
		return $this->roleFilter;
	}
}
