<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright (C) 2017 ownCloud, Inc.
 * @license OCL
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

namespace OCA\windows_network_drive\lib\migration;

use OCP\IUser;
use OCP\IUserSession;

class DummyUserSession implements IUserSession {

	/**
	 * @var IUser
	 */
	private $user;

	public function login($user, $password) {
	}

	public function logout() {
	}

	public function setUser($user) {
		$this->user = $user;
	}

	public function getUser() {
		return $this->user;
	}

	public function isLoggedIn() {
		return $this->user !== null;
	}
}
