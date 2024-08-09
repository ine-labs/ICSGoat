<?php
/**
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 *
 * @copyright Copyright (c) 2021, ownCloud GmbH
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

namespace OCA\windows_network_drive\lib;

use OCP\ICache;

class MRUCache implements ICache, \ArrayAccess {
	private $capacity;
	private $cache = [];
	private $mruKey;

	public function __construct($capacity = 512) {
		$this->capacity = $capacity;
	}

	public function hasKey($key) {
		return isset($this->cache[$key]);
	}

	public function get($key) {
		if (isset($this->cache[$key])) {
			$this->mruKey = $key;
			return $this->cache[$key];
		}
		return null;
	}

	public function set($key, $value, $ttl = 0) {
		if (\count($this->cache) >= $this->capacity) {
			unset($this->cache[$this->mruKey]);
		}
		$this->cache[$key] = $value;
		$this->mruKey = $key;
	}

	public function remove($key) {
		unset($this->cache[$key]);
		return true;
	}

	public function clear($prefix = '') {
		if ($prefix === '') {
			$this->cache = [];
		} else {
			foreach ($this->cache as $key => $value) {
				if (\strpos($key, $prefix) === 0) {
					unset($this->cache[$key]);
				}
			}
		}
		return true;
	}

	public function offsetExists($offset) {
		return $this->hasKey($offset);
	}

	public function offsetGet($offset) {
		return $this->get($offset);
	}

	public function offsetSet($offset, $value) {
		$this->set($offset, $value);
	}

	public function offsetUnset($offset) {
		$this->remove($offset);
	}
}
