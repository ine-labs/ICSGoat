<?php
/**
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
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

namespace OCA\windows_network_drive\lib\listener;

class ListenerException extends \Exception {
	public const LISTENER_EXIT = 0;  // listener should exit the listener
	public const LISTENER_EXIT_DO_NOTHING = 1;  // listener should do nothing and rethrow the exception
	public const LISTENER_EXIT_STOP_PROCESS = 2;  // listener should stop the process and rethrow the exception

	private $action = self::LISTENER_EXIT;

	public function setAction($action) {
		$this->action = $action;
	}

	public function getAction() {
		return $this->action;
	}
}
