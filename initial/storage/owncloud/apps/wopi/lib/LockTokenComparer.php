<?php
/**
 * ownCloud Wopi
 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @copyright 2020 ownCloud GmbH.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

namespace OCA\WOPI;

class LockTokenComparer {
	public function compare(string $l0, string $l1): bool {
		if ($l0 === $l1) {
			return true;
		}
		// the comparison below is a workaround to a bug in Office Online
		// https://social.msdn.microsoft.com/Forums/en-US/28d45554-7a13-4ebc-ae52-44804932b6f5/office-onlineword-locks-a-file-multiple-times-how-do-deal-with-this-situation?forum=os_office
		$decoded0 = \json_decode($l0, true);
		$decoded1 = \json_decode($l1, true);
		// S - is the key used by Word
		// L - is the key used by Powerpoint
		foreach (['S', 'L'] as $lockKey) {
			if (isset($decoded0[$lockKey])) {
				if (!isset($decoded1[$lockKey])) {
					return false;
				}
				/** @phan-suppress-next-line PhanTypeArraySuspiciousNullable */
				return $decoded0[$lockKey] === $decoded1[$lockKey];
			}
		}

		return false;
	}
}
