<?php
/**
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\PasswordPolicy\Rules;

class Numbers extends Base {

	/**
	 * @param string $password
	 * @param int $val
	 * @throws PolicyException
	 */
	public function verify($password, $val) {
		if ($this->countDigits($password) < $val) {
			throw new PolicyException(
				$this->l10n->n(
					'The password contains too few numbers. At least one number is required.',
					'The password contains too few numbers. At least %n numbers are required.',
					$val
				)
			);
		}
	}

	private function countDigits($str) {
		return \preg_match_all('/[0-9]/', $str);
	}
}
