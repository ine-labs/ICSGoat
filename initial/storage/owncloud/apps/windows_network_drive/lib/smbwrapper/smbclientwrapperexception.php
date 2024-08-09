<?php
/**
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
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

namespace OCA\windows_network_drive\lib\smbwrapper;

class SmbclientWrapperException extends \Exception {
	public static $errorMap = [
		1 => "Operation not permitted",  // EPERM
		2 => "No such file or directory",  // ENOENT
		12 => "Out of memory",  // ENOMEM
		13 => "Permission denied",  // EACCESS
		17 => "File exists",  // EEXIST
		20 => "Not a directory",  // ENOTDIR
		22 => "Invalid argument",  // EINVAL
		110 => "Connection timed out",  // ETIMEDOUT
		111 => "Connection refused",  // ECONNREFUSED
		113 => "No route to host",  // EHOSTUNREACH
	];

	/** @var string */
	private $params;
	/** @var string */
	private $operation;

	public function __construct($errorCode, $operation, $params) {
		if (isset(self::$errorMap[$errorCode])) {
			$message = self::$errorMap[$errorCode];
		} else {
			$message = "Unknown error";
		}
		$message .= " in {$operation} {$this->stringifyParams($params)}";
		parent::__construct($message, $errorCode);
		$this->operation = $operation;
		$this->params = $params;
	}

	private function stringifyParams(array $params) {
		$stringList = [];
		foreach ($params as $param) {
			if (\is_object($param)) {
				$stringList[] = '** ' . \get_class($param) . ' obj **';
			} elseif (\is_array($param)) {
				$stringList[] = $this->stringifyParams($param);
			} else {
				$stringList[] = \strval($param);
			}
		}
		return '[' . \implode(', ', $stringList) . ']';
	}

	/**
	 * @return string
	 */
	public function getOperation() {
		return $this->operation;
	}
	/**
	 * @return array
	 */
	public function getParams() {
		return $this->params;
	}
}
