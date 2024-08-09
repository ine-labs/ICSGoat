<?php
/**
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
 * @license AGPL-3.0
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

namespace OCA\windows_network_drive\lib\acl\models;

/**
 * Copied from \OCA\SmbAcl\Models\ACE
 *
 * ACE::MASK_* is intended to be used as mask like MASK_READ|MASK_WRITE
 * MASK_FULL is equivalent to MASK_READ|MASK_WRITE|MASK_DELETE|MASK_OWNERSHIP_AND_PERM
 * You can also use ACE::MASK_FULL & ~ACE::MASK_OWNERSHIP_AND_PERM to remove the OWNERSHIP_AND_PERM
 *
 * ACE::MODE_* is fixed and can't be combined. You use one or another
 *
 * ACE::FLAG_* can be combined, but some combination might not make sense. For common inheritance
 * flags you can use the ACE::FULL_FLAG_* which might be easier to handle.
 */
class ACE {
	/** This includes "read" and "execute" permissions. smbcacls will show "RX" instead of just "R" */
	public const MASK_READ = 0x001200a9;  // 1179817
	public const MASK_WRITE = 0x00000116;  // 278
	public const MASK_DELETE = 0x00010000;  // 65536
	/** This includes permissions to write DAC (discretionary access control), to write the owner and to delete children */
	public const MASK_OWNERSHIP_AND_PERM = 0x000c0040;  // 786496
	/** This includes all permisions as convenience. It will be the same as adding all the mask */
	public const MASK_FULL = 0x001f01ff;  // 2032127

	public const MODE_ALLOWED = 0;
	public const MODE_DENIED = 1;

	public const FLAG_OI = 0x01;  // object inherit: subordinate file will inherit this ACE
	public const FLAG_CI = 0x02;  // container inherit: subordinate containers will inherit this ACE
	public const FLAG_NPI = 0x04;  // Non-propagate Inherit: subordinate object will not propagate the inherit ACE further
	public const FLAG_IO = 0x08;  // Inherit only: The ACE doesn't affect the object to which it applies
	public const FLAG_IA = 0x10;  // Inherited ACE: This ACE was inherited from its parent object

	/** @var string */
	private $trustee;
	/** @var int */
	private $accessMode;
	/** @var int */
	private $inheritanceFlags;
	/** @var int */
	private $accessMask;

	/**
	 * @param string $trustee
	 * @param int $accessMode
	 * @param int $inheritanceFlags
	 * @param int $accessMask
	 */
	public function __construct($trustee, $accessMode, $inheritanceFlags, $accessMask) {
		$this->trustee = $trustee;
		$this->accessMode = $accessMode;
		$this->inheritanceFlags = $inheritanceFlags;
		$this->accessMask = $accessMask;
	}

	/**
	 * @param string $trustee
	 */
	public function setTrustee($trustee) {
		$this->trustee = $trustee;
	}

	/**
	 * @return string
	 */
	public function getTrustee() {
		return $this->trustee;
	}

	/**
	 * @param int $accessMode ACE::MODE_ALLOWED or ACE::MODE_DENIED
	 */
	public function setAccessMode($accessMode) {
		$this->accessMode = $accessMode;
	}

	/**
	 * @return int
	 */
	public function getAccessMode() {
		return $this->accessMode;
	}

	/**
	 * @param int $flags any combination of ACE::FLAG_* such as ACE::FLAG_OI|ACE::FLAG_CI
	 */
	public function setInheritanceFlags($flags) {
		$this->inheritanceFlags = $flags;
	}

	/**
	 * @return int
	 */
	public function getInheritanceFlags() {
		return $this->inheritanceFlags;
	}

	/**
	 * @param int $accessMask ACE::MASK_FULL or any combination of the rest of the ACE::MASK_*
	 * such as ACE::MASK_READ|ACE::MASK_WRITE or ACE::MASK_FULL&~ACE::MASK_OWNERSHIP_AND_PERM
	 */
	public function setAccessMask($accessMask) {
		$this->accessMask = $accessMask;
	}

	/**
	 * @return int
	 */
	public function getAccessMask() {
		return $this->accessMask;
	}

	/**
	 * get the number as integer or null if the number isn't a positive
	 * integer nor an hexadecimal string (starting with "0x")
	 * @return int|null
	 */
	private static function getAsInt($number) {
		if (\ctype_digit($number)) {
			return \intval($number);
		}
		if (\substr($number, 0, 2) === '0x') {
			$hexString = \substr($number, 2);
			if (\ctype_xdigit($hexString)) {
				return \hexdec($hexString);
			}
		}
		return null;
	}

	/**
	 * @return string
	 */
	public function toString() {
		$trustee = \ltrim($this->getTrustee(), '\\');  // remove the initial "\"
		$accessMode = $this->getAccessMode();
		$inheritanceFlags = $this->getInheritanceFlags();
		$accessMask = $this->getAccessMask();
		return "ACL:{$trustee}:{$accessMode}/{$inheritanceFlags}/{$accessMask}";
	}

	/**
	 * Create a new ACE model with the information parsed from $data
	 * Expected string format is something like the one defined in https://www.samba.org/samba/docs/current/man-html/smbcacls.1.html
	 *
	 * REVISION:<revision number>
	 * OWNER:<sid or name>
	 * GROUP:<sid or name>
	 * ACL:<sid or name>:<type>/<flags>/<mask>
	 *
	 * Below is the string we get:
	 * REVISION:1,OWNER:WINDEV1806EVAL\User,GROUP:WINDEV1806EVAL\None,ACL:WINDEV1806EVAL\user2:1/3/0x00010000,ACL:WINDEV1806EVAL\User:0/3/0x001f01ff
	 *
	 * Note that both windows and linux don't allow usernames nor groups with the ":" character.
	 * @param string $data the ACE string
	 * @return ACE|false the created ACE or false if there is some error
	 */
	public static function fromString($data) {
		$parts = \explode(':', $data);
		if (\count($parts) !== 3) {
			return false;
		}
		$trustee = $parts[1];
		$trustee = \ltrim($trustee, '\\');  // remove the initial "\" char which mess up things later
		$permissionMask = \explode('/', $parts[2]);
		if (\count($permissionMask) !== 3) {
			return false;
		}
		$accessMode = self::getAsInt($permissionMask[0]);
		$inheritanceFlags = self::getAsInt($permissionMask[1]);
		$accessMask = self::getAsInt($permissionMask[2]);
		if (\is_int($accessMode) && \is_int($inheritanceFlags) && \is_int($accessMask)) {
			return new ACE($trustee, $accessMode, $inheritanceFlags, $accessMask);
		}
		return false;
	}

	/**
	 * This method will just return "allowed" or "denied" if it's one of those modes
	 * or "?" if it isn't
	 */
	private function stringifyAccessMode($accessMode) {
		$info = [
			ACE::MODE_ALLOWED => "allowed",
			ACE::MODE_DENIED => "denied",
		];
		if (isset($info[$accessMode])) {
			return $info[$accessMode];
		} else {
			return "?";
		}
	}

	/**
	 * Similar to the stringifyMask, this method converts the inheritance flags to a string
	 * Each flag will be separated with "|"
	 * ACE::FLAG_IA => 'IA'
	 * ACE::FLAG_IO => 'IO'
	 * ACE::FLAG_NPI => 'NPI'
	 * ACE::FLAG_CI => 'CI'
	 * ACE::FLAG_OI => 'OI'
	 * ACE::FLAG_CI|ACE::FLAG_OI => 'CI|OI'
	 *
	 * There are a couple of flags for auditing that we won't handle. If those are present, a "+"
	 * char will also be included, something like 'CI|OI|+'
	 */
	private function stringifyFlags($inheritanceFlags) {
		$flagList = [
			ACE::FLAG_IA => 'IA',
			ACE::FLAG_IO => 'IO',
			ACE::FLAG_NPI => 'NPI',
			ACE::FLAG_CI => 'CI',
			ACE::FLAG_OI => 'OI',
		];
		$activeFlags = [];
		foreach ($flagList as $flag => $code) {
			if (($inheritanceFlags & $flag) === $flag) {
				$activeFlags[$flag] = $code;
			}
		}
		$activeFlagsAsInt = 0;
		foreach ($activeFlags as $flag => $code) {
			$activeFlagsAsInt |= $flag;
		}
		if ($activeFlagsAsInt !== $inheritanceFlags) {
			// there might be additional flags not under control
			$activeFlags[] = '+';
		}
		return \implode('|', $activeFlags);
	}

	/**
	 * Converts the access mask to a string. For example:
	 * ACE::MASK_READ => 'R'
	 * ACE::MASK_WRITE => 'W'
	 * ACE::MASK_DELETE => 'D'
	 * ACE::MASK_OWNERSHIP_AND_PERM => 'O'
	 * ACE::MASK_READ|ACE::MASK_WRITE => 'RW'
	 * ACE::MASK_FULL => 'RWDO'
	 *
	 * Note that the ACE::MASK_* usually defines a group of permissions: the ACE::MASK_READ
	 * implies reading contents, as well as reading attributes and extended attributes (there might
	 * be more); something similar for the rest.
	 * In order to show the "R" (or any other), all the permissions covered by the mask (ACE::MASK_READ
	 * in this case) must be present, otherwise it won't be shown.
	 *
	 * In addition, a "+" char could be included. This implies that there are extra permissions not
	 * fully covered by all the chars: you could have full read permissions (covered by the ACE::MASK_READ)
	 * but you could only have write permissions for the content. Since you don't have permissions
	 * to write attributes, the "W" char won't be shown. It will be shown as "R+".
	 * Note that the "+" will always be at the end of the string, and it doesn't applies just to an element:
	 * "RD+" could happen if we add the delete permission in the previous example, and it can also be the same
	 * if you can change the ACLs but not change the ownership.
	 */
	private function stringifyMask($accessMask) {
		$maskList = [
			ACE::MASK_READ => 'R',
			ACE::MASK_WRITE => 'W',
			ACE::MASK_DELETE => 'D',
			ACE::MASK_OWNERSHIP_AND_PERM => 'O',
		];
		$activePerms = [];
		foreach ($maskList as $mask => $code) {
			if (($accessMask & $mask) === $mask) {
				$activePerms[$mask] = $code;
			}
		}
		$activePermsAsInt = 0;
		foreach ($activePerms as $mask => $code) {
			$activePermsAsInt |= $mask;
		}
		if ($activePermsAsInt !== $accessMask) {
			$activePerms[] = '+';
		}
		return \implode('', $activePerms);
	}

	/**
	 * Returns an array with the following information:
	 * [
	 * "trustee" => the one who this ACE should be applied to
	 * "mode" => "allowed" or "denied"
	 * "flags" => any combination of "IA|IO|NPI|CI|OI|+" (matching the ACE::FLAG_* plus the "+" if there is something more)
	 * "flagsAsInt" => the integer value of the whole flags (it might not match the expectation if "+" is present in the flags)
	 * "mask" => any combination of "RWDO+" (matching the ACE::MASK_* plus the "+" if there is something more)
	 * "maskAsInt" => the integer value of the whole mask (it might nor match the expectation if "+" is present in the mask)
	 * ]
	 * @return array
	 */
	public function toConvertedArray() {
		return [
			'trustee' => \ltrim($this->getTrustee(), '\\'),  // remove the initial "\"
			'mode' => $this->stringifyAccessMode($this->getAccessMode()),
			'flags' => $this->stringifyFlags($this->getInheritanceFlags()),
			'mask' => $this->stringifyMask($this->getAccessMask()),
			'flagsAsInt' => $this->getInheritanceFlags(),
			'maskAsInt' => $this->getAccessMask(),
		];
	}

	private static function handleMaskConversion($stringifiedMask) {
		$stringToIntMaskConversion = [
			'R' => ACE::MASK_READ,
			'W' => ACE::MASK_WRITE,
			'D' => ACE::MASK_DELETE,
			'O' => ACE::MASK_OWNERSHIP_AND_PERM,
		];
		$maskList = \str_split($stringifiedMask);
		$realMask = 0;
		foreach ($maskList as $maskItem) {
			if (isset($stringToIntMaskConversion[$maskItem])) {
				$realMask |= $stringToIntMaskConversion[$maskItem];
			}
		}
		return $realMask;
	}

	private static function handleFlagConversion($stringifiedFlags) {
		$stringToIntFlagsConversion = [
			'IA' => ACE::FLAG_IA,
			'IO' => ACE::FLAG_IO,
			'NPI' => ACE::FLAG_NPI,
			'CI' => ACE::FLAG_CI,
			'OI' => ACE::FLAG_OI,
		];
		$flagList = \explode('|', $stringifiedFlags);
		$realFlags = 0;
		foreach ($flagList as $flagItem) {
			if (isset($stringToIntFlagsConversion[$flagItem])) {
				$realFlags |= $stringToIntFlagsConversion[$flagItem];
			}
		}
		return $realFlags;
	}

	/**
	 * Create a new ACE instance based on the information of the array.
	 * The array must contain the "trustee", "mode", "flags" and "mask" keys
	 * The "flagsAsInt" and "maskAsInt" are optional and will only be used if they're
	 * present and the "+" char is also present in the "flags" and "mask" values respectively
	 *
	 * For example: "mask" => "R+", "maskAsInt" => 6 will create a mask the ACE::MASK_READ and apply the "6"
	 * (write contents but not attributes)
	 * Note that the recommendation is to ignore the "+". The intention is to be able to recreate
	 * the same object that was converted with the "toConvertedArray" function
	 * @param array $data
	 * @return ACE|false the created ACE or false if we can't create the ACE
	 */
	public static function fromConvertedArray(array $data) {
		if (!isset($data['trustee']) || !isset($data['mode']) || !isset($data['flags']) || !isset($data['mask'])) {
			return false;
		}
		$mask = self::handleMaskConversion($data['mask']);
		if (\substr($data['mask'], -1) === '+' && isset($data['maskAsInt'])) {
			// there might be more permissions not properly mapped, so include the
			// maskAsInt too if possible
			$mask |= $data['maskAsInt'];
		}
		$flags = self::handleFlagConversion($data['flags']);
		if (\substr($data['flags'], -1) === '+' && isset($data['flagsAsInt'])) {
			$flags |= $data['flagsAsInt'];
		}
		if ($data['mode'] === 'allowed') {
			$mode = ACE::MODE_ALLOWED;
		} elseif ($data['mode'] === 'denied') {
			$mode = ACE::MODE_DENIED;
		} else {
			return false;
		}
		return new ACE(\ltrim($data['trustee'], '\\'), $mode, $flags, $mask);
	}
}
