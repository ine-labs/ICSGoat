<?php
/**
 *
 * @author Semih Serhat Karakaya <karakayasemi@itu.edu.tr>
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

use OCA\PasswordPolicy\Db\OldPasswordMapper;
use OCP\IL10N;
use OCP\Security\IHasher;

class PasswordHistory extends Base {

	/** @var OldPasswordMapper */
	protected $mapper;
	/** @var IHasher */
	protected $hasher;

	/**
	 * @param IL10N $l10n
	 * @param OldPasswordMapper $mapper
	 * @param IHasher $hasher
	 */
	public function __construct(
		IL10N $l10n,
		OldPasswordMapper $mapper,
		IHasher $hasher
	) {
		parent::__construct($l10n);
		$this->mapper = $mapper;
		$this->hasher = $hasher;
	}

	/**
	 * @param string $password
	 * @param int $val
	 * @param string $uid
	 * @throws PolicyException
	 */
	public function verify($password, $val, $uid) {
		if (empty($uid)) {
			return;
		}
		$oldPasswords = $this->mapper->getOldPasswords(
			$uid,
			$val
		);
		foreach ($oldPasswords as $oldPassword) {
			if ($this->hasher->verify($password, $oldPassword->getPassword())) {
				throw new PolicyException(
					$this->l10n->t('The password must be different than your previous %d passwords.', [$val])
				);
			}
		}
	}
}
