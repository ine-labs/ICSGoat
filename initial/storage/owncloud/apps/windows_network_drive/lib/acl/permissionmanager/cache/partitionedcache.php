<?php
/**
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 *
 * @copyright Copyright (c) 2020, ownCloud GmbH
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

namespace OCA\windows_network_drive\lib\acl\permissionmanager\cache;

/**
 * Cache divided into multiple partitions. The maximum number of elements in the cache
 * is fixed (default to 512).
 * When you try to set a new value inside a partition and there isn't enough space,
 * the oldest partition excluding the one where the value is being set will be removed
 * Except for this "partition removal", it isn't possible to make more space. In particular,
 * if there is only one partition and you're trying to add more items over the limit,
 * the cache won't set new elements and fail the "set" call, meaning that the value
 * hasn't been cached.
 *
 * Assuming that $obj is the object using the partitioned cache, it's recommended to
 * use `intval(spl_object_hash($obj), 16)` as partition id, and clear that partition
 * on the destructor of $obj
 */
class PartitionedCache {
	/** @var int */
	private $maxSize;
	/** @var int */
	private $currentSize;
	/** @var array */
	private $data = [];

	public function __construct(int $maxSize = 512) {
		$this->maxSize = $maxSize;
		$this->currentSize = 0;
	}

	private function canSet(int $partition) {
		if ($this->maxSize <= 0) {
			return false;
		}

		if ($this->currentSize < $this->maxSize) {
			return true;
		} else {
			// check if we can clear one partition
			if (\count($this->data) > 1) {
				\reset($this->data);
				$partitionToDelete = \key($this->data);

				if ($partitionToDelete === $partition) {
					// choose the next one
					\next($this->data);
					$partitionToDelete = \key($this->data);
				}

				$this->currentSize -= \count($this->data[$partitionToDelete]);
				unset($this->data[$partitionToDelete]);
				return true;
			} else {
				\reset($this->data);
				$partitionToDelete = \key($this->data);
				// if the partition is different than the only one saved
				// just clear the cache
				if ($partitionToDelete !== $partition) {
					$this->data = [];
					$this->currentSize = 0;
					return true;
				}
			}
			return false;
		}
	}

	/**
	 * Set a new value using the key inside the corresponding partition
	 * Please, check the caching policies in the class. It's possible that the
	 * value won't be cached
	 * In addition, avoid to store null values in the cache. The "get" call might
	 * return a null value if the value doesn't exists, which would be confusing.
	 * @param int $partition the partition id
	 * @param string $key the key to be used
	 * @param mixed $value the value to be stored
	 * @return bool true if the value has been cached, false otherwise
	 */
	public function set(int $partition, string $key, $value) {
		if (!$this->canSet($partition)) {
			return false;
		}

		if (!isset($this->data[$partition])) {
			$this->data[$partition] = [];
		}
		$this->data[$partition][$key] = $value;
		$this->currentSize++;
		return true;
	}

	/**
	 * Get the value using the key inside the partition.
	 * @param int $partition the partition to be used to get the key
	 * @param string $key the key to be used
	 * @return mixed|null the value stored or null if the key isn't found
	 */
	public function get(int $partition, string $key) {
		if (isset($this->data[$partition][$key])) {
			return $this->data[$partition][$key];
		} else {
			return null;
		}
	}

	/**
	 * Remove the partition and free memory
	 * @param int $partition the partition id
	 */
	public function removePartition(int $partition) {
		if (isset($this->data[$partition])) {
			$this->currentSize -= \count($this->data[$partition]);
			unset($this->data[$partition]);
		}
	}
}
