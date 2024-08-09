<?php
/**
 * ownCloud
 *
 * @author Jörn Friedrich Dreyer <jfd@owncloud.com>
 * @copyright (C) 2017-2018 ownCloud GmbH
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\User_Shibboleth\Mapper;

class NoOpMapper implements IMapper {

	/**
	 * @param string $uid
	 * @return string the $uid without changes
	 */
	public function map($uid) {
		return $uid;
	}
}
