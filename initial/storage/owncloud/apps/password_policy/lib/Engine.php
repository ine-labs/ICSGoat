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

namespace OCA\PasswordPolicy;

use OCA\PasswordPolicy\Db\OldPasswordMapper;
use OCA\PasswordPolicy\Rules\Length;
use OCA\PasswordPolicy\Rules\Numbers;
use OCA\PasswordPolicy\Rules\PolicyException;
use OCA\PasswordPolicy\Rules\Special;
use OCA\PasswordPolicy\Rules\Lowercase;
use OCA\PasswordPolicy\Rules\Uppercase;
use OCA\PasswordPolicy\Rules\PasswordHistory;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\Security\IHasher;
use OCP\Security\ISecureRandom;

class Engine {

	/** @var array */
	private $configValues;
	/** @var IL10N */
	private $l10n;
	/** @var ISecureRandom */
	private $random;
	/** @var IDBConnection */
	private $db;
	/** @var IHasher */
	private $hasher;

	/**
	 * @param array $configValues
	 * @param IL10N $l10n
	 * @param ISecureRandom $random
	 * @param IDBConnection $db
	 * @param IHasher $hasher
	 */
	public function __construct(
		array $configValues,
		IL10N $l10n,
		ISecureRandom $random,
		IDBConnection $db,
		IHasher $hasher
	) {
		$this->configValues = $configValues;
		$this->l10n = $l10n;
		$this->random = $random;
		$this->db = $db;
		$this->hasher = $hasher;
	}

	/**
	 * @return string
	 */
	public function generatePassword() {
		$length = 20;
		$password = '';
		if ($this->yes('spv_min_chars_checked')) {
			$length = $this->configValues['spv_min_chars_value'] + 1;
		}
		if ($this->yes('spv_lowercase_checked')) {
			$val = $this->configValues['spv_lowercase_value'];
			$password .= $this->random->generate($val + 1, ISecureRandom::CHAR_LOWER);
		}
		if ($this->yes('spv_uppercase_checked')) {
			$val = $this->configValues['spv_uppercase_value'];
			$password .= $this->random->generate($val + 1, ISecureRandom::CHAR_UPPER);
		}
		if ($this->yes('spv_numbers_checked')) {
			$val = $this->configValues['spv_numbers_value'];
			$password .= $this->random->generate($val + 1, ISecureRandom::CHAR_DIGITS);
		}
		if ($this->yes('spv_special_chars_checked')) {
			$val = $this->configValues['spv_special_chars_value'];
			$chars = $this->configValues['spv_def_special_chars_value'];
			if ($chars == '') {
				$chars = ISecureRandom::CHAR_SYMBOLS;
			}
			$password .= $this->random->generate($val + 1, $chars);
		}

		if ($length - \strlen($password) > 0) {
			$password .= $this->random->generate($length - \strlen($password), ISecureRandom::CHAR_LOWER);
		}

		return \str_shuffle($password);
	}

	/**
	 * @param string|null $password
	 * @param string $uid
	 * @param string $type
	 * @throws PolicyException
	 */
	public function verifyPassword($password, $uid = null, $type = 'user') {
		// skip policy when no password is specified for public link
		// enforcement of password existence is done on a different level
		if ($type === 'public' && ($password === null || $password === '')) {
			return;
		}

		if ($this->yes('spv_min_chars_checked')) {
			$val = (int) $this->configValues['spv_min_chars_value'];
			$r = new Length($this->l10n);
			$r->verify($password, $val);
		}
		if ($this->yes('spv_lowercase_checked')) {
			$val = (int) $this->configValues['spv_lowercase_value'];
			$r = new Lowercase($this->l10n);
			$r->verify($password, $val);
		}
		if ($this->yes('spv_uppercase_checked')) {
			$val = (int) $this->configValues['spv_uppercase_value'];
			$r = new Uppercase($this->l10n);
			$r->verify($password, $val);
		}
		if ($this->yes('spv_numbers_checked')) {
			$val = (int) $this->configValues['spv_numbers_value'];
			$r = new Numbers($this->l10n);
			$r->verify($password, $val);
		}
		if ($this->yes('spv_special_chars_checked')) {
			$val = (int) $this->configValues['spv_special_chars_value'];
			$chars = '';
			if ($this->yes('spv_def_special_chars_checked')) {
				$chars = $this->configValues['spv_def_special_chars_value'];
			}
			$r = new Special($this->l10n);
			$r->verify($password, $val, $chars);
		}
		if ($uid !== null && $this->yes('spv_password_history_checked')) {
			$val = (int) $this->configValues['spv_password_history_value'];
			$dbMapper = new OldPasswordMapper($this->db);
			$r = new PasswordHistory($this->l10n, $dbMapper, $this->hasher);
			$r->verify($password, $val, $uid);
		}
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function yes($key) {
		if ($this->configValues[$key] === 'on') {
			return true;
		}
		return $this->configValues[$key];
	}

	/**
	 * @return array
	 */
	public function getConfigValues() {
		return $this->configValues;
	}
	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getConfigValue($key) {
		return $this->configValues[$key];
	}
}
