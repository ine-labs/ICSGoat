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

class Special extends Base {

	/**
	 * @param string $password
	 * @param int $val
	 * @param string $allowedSpecialChars
	 * @throws PolicyException
	 */
	public function verify($password, $val, $allowedSpecialChars) {
		$special = $this->stripAlphaNumeric($password);
		if (!empty($allowedSpecialChars) && !empty($special)) {
			$allowedSpecialCharsAsArray = \str_split($allowedSpecialChars);
			$s = \array_filter(\str_split($special), function ($char) use ($allowedSpecialCharsAsArray) {
				return !\in_array($char, $allowedSpecialCharsAsArray, true);
			});
			if (\count($s) > 0) {
				throw new PolicyException(
					$this->l10n->t('The password contains invalid special characters. Only %s are allowed.', [$allowedSpecialChars])
				);
			}
		}
		if (\strlen($special) < $val) {
			if (!empty($allowedSpecialChars)) {
				throw new PolicyException(
					$this->l10n->n(
						'The password contains too few special characters. At least one special character (%s) is required.',
						'The password contains too few special characters. At least %n special characters (%s) are required.',
						$val,
						[$allowedSpecialChars]
					)
				);
			} else {
				throw new PolicyException(
					$this->l10n->n(
						'The password contains too few special characters. At least one special character is required.',
						'The password contains too few special characters. At least %n special characters are required.',
						$val
					)
				);
			}
		}
	}

	private function stripAlphaNumeric($string) {
		return \preg_replace('/[a-z0-9]/i', '', $string);
	}
}
