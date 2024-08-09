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
 * Copied from \OCA\SmbAcl\Models\SecurityDescriptor
 */
class SecurityDescriptor {
	/** @var int */
	private $revision = 1;
	/** @var string */
	private $owner;
	/** @var string */
	private $group;
	/** @var ACE[] */
	private $acl = [];

	/**
	 * Revision will always be 1
	 * @param string $owner
	 * @param string $group
	 * @param ACE[] $acl
	 */
	public function __construct($owner, $group, array $acl) {
		$this->owner = $owner;
		$this->group = $group;
		$acl = \array_filter($acl, function ($ace) {
			return $ace instanceof ACE;
		});
		$this->acl = $acl;
	}

	/**
	 * This method shouldn't be used. The expected revision is always 1 by the SMB server
	 * and changing the revision might cause undefined behaviour in the SMB server.
	 * The revision is set to 1 by default, so this method doesn't need to be called.
	 * This is here for completeness
	 * @param int $revision
	 */
	public function setRevision($revision) {
		$this->revision = $revision;
	}

	/**
	 * @return int
	 */
	public function getRevision() {
		return $this->revision;
	}

	/**
	 * @param string $owner
	 */
	public function setOwner($owner) {
		$this->owner = $owner;
	}

	/**
	 * @return string
	 */
	public function getOwner() {
		return $this->owner;
	}

	/**
	 * @param string $group
	 */
	public function setGroup($group) {
		$this->group = $group;
	}

	/**
	 * @return string
	 */
	public function getGroup() {
		return $this->group;
	}

	/**
	 * @param ACE[] $acl the list of ACEs to be set
	 */
	public function setAcl(array $acl) {
		$acl = \array_filter($acl, function ($ace) {
			return $ace instanceof ACE;
		});
		$this->acl = $acl;
	}

	/**
	 * @return ACE[] a list of ACEs (might be empty)
	 */
	public function getAcl() {
		return $this->acl;
	}

	/**
	 * @param ACE $newAce the ACE we want to check if it's present in this security descriptor
	 * @return bool true if the ACE is present, false otherwise
	 */
	public function isACEPresent(ACE $newAce) {
		foreach ($this->acl as $ace) {
			if ($ace == $newAce) {
				// object comparison intended because we want to check all attributes
				return true;
			}
		}
		return false;
	}

	private static function getEndIfContentStartsWith($content, $key) {
		if (\strpos($content, $key) === 0) {
			return \substr($content, \strlen($key));
		} else {
			return false;
		}
	}

	/**
	 * @return string
	 */
	public function toString() {
		$aceList = [];
		foreach ($this->getAcl() as $ace) {
			$aceList[] = $ace->toString();
		}
		$aclString = \implode(',', $aceList);
		$revision = $this->getRevision();
		$owner = \ltrim($this->getOwner(), '\\');
		$group = \ltrim($this->getGroup(), '\\');
		if ($aclString === '') {
			return "REVISION:{$revision},OWNER:{$owner},GROUP:{$group}";
		} else {
			return "REVISION:{$revision},OWNER:{$owner},GROUP:{$group},{$aclString}";
		}
	}

	/**
	 * Create a new SecurityDescriptor model with the information parsed from $data
	 * REVISION must be present in the string even though it will be ignored
	 * @param string $data the SecurityDescriptor string
	 * @return SecurityDescriptor|false the created SecurityDescriptor or false if there is some error
	 */
	public static function fromString($data) {
		$parts = \explode(',', $data);
		if (\count($parts) < 3) {
			// we'll need at least the revision, owner and group
			return false;
		}
		$revision = self::getEndIfContentStartsWith($parts[0], 'REVISION:');
		$owner = self::getEndIfContentStartsWith($parts[1], 'OWNER:');
		$group = self::getEndIfContentStartsWith($parts[2], 'GROUP:');
		if ($revision === false || $owner === false || $group === false) {
			return false;
		}
		$acl = [];
		foreach ($parts as $part) {
			if (\strpos($part, 'ACL:') === 0) {
				$ace = ACE::fromString($part);
				if ($ace) {
					$acl[] = $ace;
				} else {
					return false;
				}
			}
		}
		return new SecurityDescriptor(\ltrim($owner, '\\'), \ltrim($group, '\\'), $acl);
	}

	/**
	 * Returns an array with the following information
	 * [
	 * "revision" => 1
	 * "owner" => the owner
	 * "group" => the group
	 * "acl" => [$ace1, $ace2]
	 * ]
	 * Each $ace will return the same information that would return normally
	 * (check the ACE::toConvertedArray() method)
	 * @return array
	 */
	public function toConvertedArray() {
		return [
			'revision' => $this->getRevision(),
			'owner' => \ltrim($this->getOwner(), '\\'),
			'group' => \ltrim($this->getGroup(), '\\'),
			'acl' => \array_map(function ($ace) {
				return $ace->toConvertedArray();
			}, $this->getAcl()),
		];
	}

	/**
	 * Get a new SecurityDescriptor instance based on the $data array information
	 * The array must contain "owner", "group" and "acl" key, and the list of ACEs in the
	 * "acl" key must be corrected parsed. Note that any other information (the revision, for example)
	 * will be ignored.
	 * This method is does the reverse operation as the "toConvertedArray()"
	 * @param array $data
	 * @return SecurityDescriptor|false the SecurityDescriptor or false in case of error
	 */
	public static function fromConvertedArray(array $data) {
		// revision will be ignored
		if (!isset($data['owner']) || !isset($data['group']) || !isset($data['acl'])) {
			return false;
		}
		$acl = [];
		foreach ($data['acl'] as $aceData) {
			$ace = ACE::fromConvertedArray($aceData);
			if ($ace) {
				$acl[] = $ace;
			} else {
				return false;
			}
		}
		return new SecurityDescriptor(\ltrim($data['owner'], '\\'), \ltrim($data['group'], '\\'), $acl);
	}
}
