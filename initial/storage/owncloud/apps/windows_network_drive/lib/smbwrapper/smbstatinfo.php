<?php
/**
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 *
 * @copyright Copyright (c) 2021, ownCloud GmbH
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

namespace OCA\windows_network_drive\lib\smbwrapper;

class SMBStatInfo implements \JsonSerializable {
	// MODE_DOS constants extracted from https://github.com/samba-team/samba/blob/f753e2f7acf8f3394a5f1107344d0323acc05694/source3/libsmb/libsmb_stat.c
	private const MODE_DOS_ISDIR   = 0040555;
	private const MODE_DOS_ISFILE  = 0100444;
	private const MODE_DOS_ARCHIVE = 0000100;
	private const MODE_DOS_SYSTEM  = 0000010;
	private const MODE_DOS_HIDDEN  = 0000001;
	private const MODE_DOS_WRITE   = 0000200;  // not read-only

	// RAW_DOS constants extracted from https://github.com/samba-team/samba/blob/e742661bd2507d39dfa47e40531dc1dca636cbbe/source3/include/libsmbclient.h
	private const RAW_DOS_READONLY = 0x01;
	private const RAW_DOS_HIDDEN = 0x02;
	private const RAW_DOS_SYSTEM = 0x04;
	private const RAW_DOS_VOLUME_ID = 0x08;
	private const RAW_DOS_DIRECTORY = 0x10;
	private const RAW_DOS_ARCHIVE = 0x20;
	private const RAW_DOS_NORMAL = 0x80;
	/** @var int */
	private $atime;
	/** @var int */
	private $mtime;
	/** @var int */
	private $ctime;
	/** @var int */
	private $size;
	/** @var string */
	private $type;
	/** @var bool */
	private $isArchive;
	/** @var bool */
	private $isSystem;
	/** @var bool */
	private $isHidden;
	/** @var bool */
	private $isReadonly;

	/**
	 * Call "$smbWrapper->statPath" and parse the results. For directories, the file attributes
	 * are unreliable, so they should be ignored. Use "parseDosAttrs" instead (if possible,
	 * be ware of limitations)
	 * @param SmbclientWrapper $smbWrapper the SmbclientWrapper to be used for the calls
	 * @param string $path the path to check with the SmbclientWrapper
	 * @return SMBStatInfo
	 * @throws SmbclientWrapperException from any failed call
	 */
	public static function parseStat(SmbclientWrapper $smbWrapper, string $path): SMBStatInfo {
		$statData = $smbWrapper->statPath($path);
		$data = [
			'atime' => $statData['atime'],
			'mtime' => $statData['mtime'],
			'ctime' => $statData['ctime'],
			'size' => $statData['size'],
		];

		$mode = $statData['mode'];
		if (($mode & self::MODE_DOS_ISDIR) === self::MODE_DOS_ISDIR) {
			$data['type'] = 'dir';
		} else {
			$data['type'] = 'file';
		}
		$data['isArchive'] = ($mode & self::MODE_DOS_ARCHIVE) === self::MODE_DOS_ARCHIVE;
		$data['isSystem'] = ($mode & self::MODE_DOS_SYSTEM) === self::MODE_DOS_SYSTEM;
		$data['isHidden'] = ($mode & self::MODE_DOS_HIDDEN) === self::MODE_DOS_HIDDEN;
		$data['isReadonly'] = !(($mode & self::MODE_DOS_WRITE) === self::MODE_DOS_WRITE);

		return new SMBStatInfo($data);
	}

	/**
	 * Call "$smbWrapper->getDosAttr" and parse the results.
	 * Note that, due to problems in the native library, this function won't work reliably
	 * with libsmbclient 4.11+ (likely 4.10+). It should work with 4.7.
	 * With libsmbclient 4.11+, the "mode" will return "0x41ed" instead of the expected "0x10"
	 * for directories
	 * @param SmbclientWrapper $smbWrapper the SmbclientWrapper to be used for the call
	 * @param string $path the path to check with the SmbclientWrapper
	 * @return SMBStatInfo
	 * @throws SmbclientWrapperException from any failed call
	 */
	public static function parseDosAttrs(SmbclientWrapper $smbWrapper, string $path): SMBStatInfo {
		$dosAttrsString = $smbWrapper->getDosAttr($path);
		$data = [];
		foreach (\explode(',', $dosAttrsString) as $item) {
			$parts = \explode(':', $item);
			switch ($parts[0]) {
				case 'SIZE':
					$data['size'] = \intval($parts[1]);
					break;
				case 'CREATE_TIME':
					$data['ctime'] = \intval($parts[1]);
					break;
				case 'ACCESS_TIME':
					$data['atime'] = \intval($parts[1]);
					break;
				case 'WRITE_TIME':
					$data['mtime'] = \intval($parts[1]);
					break;
				case 'MODE':
					$rawDosMode = \intval($parts[1], 16);
					$data['isArchive'] = ($rawDosMode & self::RAW_DOS_ARCHIVE) === self::RAW_DOS_ARCHIVE;
					$data['isSystem'] = ($rawDosMode & self::RAW_DOS_SYSTEM) === self::RAW_DOS_SYSTEM;
					$data['isHidden'] = ($rawDosMode & self::RAW_DOS_HIDDEN) === self::RAW_DOS_HIDDEN;
					$data['isReadonly'] = ($rawDosMode & self::RAW_DOS_READONLY) === self::RAW_DOS_READONLY;
					if (($rawDosMode & self::RAW_DOS_DIRECTORY) === self::RAW_DOS_DIRECTORY) {
						$data['type'] = 'dir';
					} else {
						$data['type'] = 'file';
					}
			}
		}
		return new SMBStatInfo($data);
	}

	private function __construct(array $data) {
		$this->atime = $data['atime'];
		$this->mtime = $data['mtime'];
		$this->ctime = $data['ctime'];
		$this->size = $data['size'];
		$this->type = $data['type'];
		$this->isArchive = $data['isArchive'];
		$this->isSystem = $data['isSystem'];
		$this->isHidden = $data['isHidden'];
		$this->isReadonly = $data['isReadonly'];
	}

	/**
	 * @return int the access time
	 */
	public function getAtime(): int {
		return $this->atime;
	}

	/**
	 * @return int the modify time
	 */
	public function getMtime(): int {
		return $this->mtime;
	}

	/**
	 * @return int the creation time
	 */
	public function getCtime(): int {
		return $this->ctime;
	}

	/**
	 * @return int the size
	 */
	public function getSize(): int {
		return $this->size;
	}

	/**
	 * @return string 'dir' for directories, 'file' for the rest
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @return bool is the file archived?
	 */
	public function isArchive(): bool {
		return $this->isArchive;
	}

	/**
	 * @return bool is a system file?
	 */
	public function isSystem(): bool {
		return $this->isSystem;
	}

	/**
	 * @return bool is the file hidden?
	 */
	public function isHidden(): bool {
		return $this->isHidden;
	}

	/**
	 * @return bool is the file read-only?
	 */
	public function isReadonly(): bool {
		return $this->isReadonly;
	}

	public function jsonSerialize() {
		return [
			'atime' => $this->atime,
			'mtime' => $this->mtime,
			'ctime' => $this->ctime,
			'size' => $this->size,
			'type' => $this->type,
			'isArchive' => $this->isArchive,
			'isSystem' => $this->isSystem,
			'isHidden' => $this->isHidden,
			'isReadonly' => $this->isReadonly,
		];
	}
}
