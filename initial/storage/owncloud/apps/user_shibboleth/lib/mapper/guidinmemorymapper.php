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

use OC\HintException;

class GUIDInMemoryMapper implements IMapper {

	/**
	 * converts a binary GUID, eg "1D88AB4755064D41982F02998C905A28"
	 * to a string representation "47AB881D-0655-414D-982F-02998C905A28"
	 *
	 * from http://www.ldapadministrator.com/forum/post1424.html?sid=489cbecd782983e53ddfc8cadb73e32e#p1424
	 * The issue is that GUID string and actual in memory bytes representation are different.
	 *
	 * For example for GUID {47AB881D-0655-414D-982F-02998C905A28} the in memory representation is: 1D88AB4755064D41982F02998C905A28.
	 *
	 * The rule is: for the first 3 GUID parts you need to reverse hex-pairs order:
	 *
	 * 47AB881D becomes 1D88AB47
	 * 0655 becomes 5506
	 * 414D becomes 4D41
	 *
	 * Juniper seems to send bin GUID
	 *
	 * @param string $uid
	 * @return string the mapped uid
	 * @throws HintException  on an error
	 */
	public function map($uid) {
		if (!\is_string($uid)) {
			throw new HintException(
				"The GUID Mapper expected a string, got ".\json_encode($uid),
				'Expected a string'
			);
		}
		$length = \strlen($uid);
		if ($length == 34 && \substr($uid, 0, 2) === '0x') {
			$uid = \substr($uid, 2);
		} elseif ($length !== 32) {
			throw new HintException(
				"UID mapper did not receive a valid GUID, got $uid",
				"Expected 32 chars optionally prefixed with '0x', received '$uid'"
			);
		}
		$stringGUID  =     \substr($uid, 6, 2);
		$stringGUID .=     \substr($uid, 4, 2);
		$stringGUID .=     \substr($uid, 2, 2);
		$stringGUID .=     \substr($uid, 0, 2);
		$stringGUID .= '-'.\substr($uid, 10, 2);
		$stringGUID .=     \substr($uid, 8, 2);
		$stringGUID .= '-'.\substr($uid, 14, 2);
		$stringGUID .=     \substr($uid, 12, 2);
		$stringGUID .= '-'.\substr($uid, 16, 4);
		$stringGUID .= '-'.\substr($uid, 20, 12);
		return $stringGUID;
	}
}
