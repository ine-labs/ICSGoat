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

namespace OCA\PasswordPolicy\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method int getId()
 * @method void setId(int $id)
 * @method string getUid()
 * @method void setUid(string $uid)
 * @method void setPassword(string $password)
 * @method int getChangeTime()
 * @method void setChangeTime(int $changeTime)
 */
class OldPassword extends Entity {
	public const EXPIRED = 'expired';

	/** @var string $uid */
	protected $uid;

	/** @var string $password */
	protected $password;

	/** @var int $changeTime */
	protected $changeTime;

	public function getPassword() {
		return $this->password;
	}
}
