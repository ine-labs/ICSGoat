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

/**
 * Roles constants and utility
 */
class Roles {
	public const BACKEND_ROLE_MEMBER = 0;
	public const BACKEND_ROLE_ADMIN = 1;

	public const DAV_ROLE_MEMBER = 'member';
	public const DAV_ROLE_ADMIN = 'admin';

	private static $instance = null;

	private $mapping;

	/**
	 * Converts the given DAV role name to a backend value
	 *
	 * @param string $davRole DAV role string
	 * @return int numeric backend value
	 *
	 * @throws \InvalidArgumentException if an invalid role was given
	 */
	public static function davToBackend($davRole) {
		$mapping = self::getInstance()->getReverseMapping();
		if (isset($mapping[$davRole])) {
			return $mapping[$davRole];
		}
		throw new \InvalidArgumentException("Invalid DAV role \"$davRole\"");
	}

	/**
	 * Converts the given numeric role value to a DAV string
	 *
	 * @param int $backendRole numeric backend role
	 * @return string DAV role string
	 *
	 * @throws \InvalidArgumentException if an invalid role was given
	 */
	public static function backendToDav($backendRole) {
		$mapping = self::getInstance()->getMapping();
		if (isset($mapping[$backendRole])) {
			return $mapping[$backendRole];
		}
		throw new \InvalidArgumentException("Invalid backend role \"$backendRole\"");
	}

	public function __construct() {
		$this->mapping = [
			self::BACKEND_ROLE_MEMBER => self::DAV_ROLE_MEMBER,
			self::BACKEND_ROLE_ADMIN => self::DAV_ROLE_ADMIN,
		];
	}

	public function getMapping() {
		return $this->mapping;
	}

	public function getReverseMapping() {
		return \array_flip($this->mapping);
	}

	private static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new Roles();
		}
		return self::$instance;
	}
}
