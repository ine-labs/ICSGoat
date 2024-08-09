<?php
/**
 * ownCloud
 *
 * @author Tom Needham <tom@owncloud.com>
 * @copyright (C) 2017-2018 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\User_Shibboleth\Mapper;

use OC\HintException;

class ADFSMapper implements IMapper {

	/**
	 * Used with ADFS, which inputs uids in the format username,userName
	 * And we only need the lowercased version, without the command and the other version
	 *
	 * @param string $uid
	 * @return string the mapped uid
	 * @throws HintException  on an error
	 */
	public function map($uid) {
		return \strtolower(\explode(';', $uid)[0]);
	}
}
