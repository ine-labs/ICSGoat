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

use OCA\windows_network_drive\lib\acl\models\SecurityDescriptor;

class SmbclientWrapper {
	public const PROTO_SMB1 = 'NT1';
	public const PROTO_SMB2 = 'SMB2';
	public const PROTO_SMB3 = 'SMB3';

	public const OPTS_TIMEOUT = 20000;
	public const OPTS_AUTO_ANONYMOUS_LOGIN = false;

	/** @var resource */
	private $smbResource;
	/** @var string */
	private $host;
	/** @var string */
	private $share;
	/** @var resource */
	private $streamContext;
	/** @var array */
	private $getRawResourceCodes = [];

	/**
	 * @param string $host
	 * @param string $share
	 * @param string $workgroup
	 * @param string $user
	 * @param string $password
	 * @param array $opts the options to be set for the connection. See smbclient_option_set
	 * in the libsmbclient-php for the whole list.
	 * $opts = [
	 *   SMBCLIENT_OPT_OPEN_SHAREMODE => SMBCLIENT_SHAREMODE_DENY_DOS,
	 *   SMBCLIENT_OPT_AUTO_ANONYMOUS_LOGIN => false,
	 *   SMBCLIENT_OPT_TIMEOUT => 30000
	 * ]
	 */
	public function __construct(
		string $host,
		string $share,
		string $workgroup,
		string $user,
		string $password,
		array $opts = []
	) {
		$this->host = $host;
		$this->share = $share;
		$this->smbResource = \smbclient_state_new();

		$defaultOpts = [
			SMBCLIENT_OPT_AUTO_ANONYMOUS_LOGIN => self::OPTS_AUTO_ANONYMOUS_LOGIN,
			SMBCLIENT_OPT_TIMEOUT => self::OPTS_TIMEOUT,
		];

		$realOpts = $opts + $defaultOpts;
		foreach ($realOpts as $key => $value) {
			if (!\smbclient_option_set($this->smbResource, $key, $value)) {
				throw new SmbclientWrapperException(\smbclient_state_errno($this->smbResource), 'option_set', [$key, $value]);
			}
		}
		\smbclient_state_init($this->smbResource, $workgroup, $user, $password);
		$this->streamContext = \stream_context_create([
			'smb' => [
				'workgroup' => $workgroup,
				'username' => $user,
				'password' => $password,
			],
		]);
	}

	/**
	 * @return string the host being accessed by this smbclient wrapper
	 */
	public function getHost() {
		return $this->host;
	}

	/**
	 * @return string the share being accessed by this smbclient wrapper
	 */
	public function getShare() {
		return $this->share;
	}

	/**
	 * Get the raw smb resource that is being wrapped by this class.
	 * This is for internal use only and nobody should use it out of the smbwrapper package
	 * @internal
	 * @param int $code a pre-generated integer that the caller should know. This is mainly
	 * to try to prevent unwanted access to the raw resource because it could be potentially
	 * dangerous
	 * @return resource the smbclient resource used by this instance
	 * @throws \InvalidArgumentException if the code is wrong.
	 */
	public function getRawResouce(int $code) {
		if (!isset($this->getRawResourceCodes[$code])) {
			throw new \InvalidArgumentException();
		}
		return $this->smbResource;
	}

	/**
	 * Remove the raw resource code from the known codes of this instance
	 * This is for internal use only and nobody should use it out of the smbwrapper package
	 * @internal
	 * @param int $code a pre-generated integer that the caller should know.
	 */
	public function removeRawResourceCode(int $code) {
		unset($this->getRawResourceCodes[$code]);
	}

	/**
	 * Get an option already set for the connection.
	 * See libsmbclient-php's smbclient_option_get function for a list of available options
	 * @param int $option any SMBCLIENT_OPT_* constant (defined in the libsmbclient-php library)
	 * @return mixed the option value previously set, or null if the option isn't available
	 */
	public function getOption(int $option) {
		return \smbclient_option_get($this->smbResource, $option);
	}

	/**
	 * Try to set the minimum and maximum protocol versions. Use any of the PROTO_* constants
	 * You should use this method just after creating the smbclientWrapper instance.
	 * Note that using this method requires at least version 1.0.4 of the libsmbclient-php
	 * library. Using it with earlier versions will always return false.
	 * @param string $min minimum protocol version to be negotiated
	 * @param string $max maximum protocol version to be negotiated
	 * @return bool true if the protocols are set, false otherwise. If the underlying function
	 * isn't available, this method will always return false.
	 */
	public function setProtocol(string $min = null, string $max = null): bool {
		// function was introduced with 1.0.4 of libsmbclient-php. We're requiring 0.8.0 so
		// it might not be available
		if (!\function_exists('smbclient_client_protocols')) {
			return false;
		}
		return \smbclient_client_protocols($this->smbResource, $min, $max);
	}

	/**
	 * @param string $path the paths within the share that will be changed
	 * @param SecurityDescriptor $descriptor the SecurityDescriptor that will be set in the path
	 * @throws SmbclientWrapperException if the SecurityDescriptor can't be set. The error code of the
	 * exception can be used to know the cause.
	 */
	public function setSecurityDescriptor(string $path, SecurityDescriptor $descriptor) {
		$path = \trim($path, '/');
		$smbPath = "smb://{$this->host}/{$this->share}/{$path}";
		$secDesc = $descriptor->toString();
		$result = @\smbclient_setxattr($this->smbResource, $smbPath, 'system.nt_sec_desc.*+', $secDesc);
		if ($result === false) {
			throw new SmbclientWrapperException(\smbclient_state_errno($this->smbResource), 'setxattr', [$smbPath, 'system.nt_sec_desc.*+', $secDesc]);
		}
	}

	/**
	 * @param string $path the path within the share to get the information from
	 * @return SecurityDescriptor|false the SecurityDescriptor for the path or false if
	 * there are error parsing the data
	 * @throws SmbclientWrapperException if we can't get the security descriptor from the server
	 * (probably connection or permission problems). The error code of the exception can be used
	 * to know the cause
	 */
	public function getSecurityDescriptor(string $path) {
		$path = \trim($path, '/');
		$smbPath = "smb://{$this->host}/{$this->share}/{$path}";
		$data = @\smbclient_getxattr($this->smbResource, $smbPath, 'system.nt_sec_desc.*+');
		if ($data === false) {
			throw new SmbclientWrapperException(\smbclient_state_errno($this->smbResource), 'getxattr', [$smbPath, 'system.nt_sec_desc.*+']);
		}
		return SecurityDescriptor::fromString($data);
	}

	/**
	 * Get the DOS attrs as string. Expected string is something like:
	 * "MODE:0x20,SIZE:1049,CREATE_TIME:1614344434,ACCESS_TIME:1614344434,WRITE_TIME:1614691318,CHANGE_TIME:1614763858,INODE:2251799813757535"
	 *
	 * This isn't inteded to be used directly. Use "SMBStatInfo::parseDosAttrs($smbWrapper, $path)"
	 * instead. Note that the inode won't be parsed.
	 *
	 * @param string $path the path to get the info from
	 * @return string the DOS attrs as string
	 * @throws SmbclientWrapperException if there is any error
	 */
	public function getDosAttr(string $path): string {
		$path = \trim($path, '/');
		$smbPath = "smb://{$this->host}/{$this->share}/{$path}";
		$data = @\smbclient_getxattr($this->smbResource, $smbPath, 'system.dos_attr.*');
		if ($data === false) {
			throw new SmbclientWrapperException(\smbclient_state_errno($this->smbResource), 'getxattr', [$smbPath, 'system.dos_attr.*']);
		}
		return $data;
	}

	/**
	 * Get the DOS mode as string. This is an hex string such as "0x10"
	 * @param string $path the path to get the info from
	 * @return string the DOS mode as string
	 * @throws SmbclientWrapperException if any error happens
	 */
	public function getRawDosMode(string $path): string {
		$path = \trim($path, '/');
		$smbPath = "smb://{$this->host}/{$this->share}/{$path}";
		$data = @\smbclient_getxattr($this->smbResource, $smbPath, 'system.dos_attr.mode');
		if ($data === false) {
			throw new SmbclientWrapperException(\smbclient_state_errno($this->smbResource), 'getxattr', [$smbPath, 'system.dos_attr.mode']);
		}
		return $data;
	}

	/**
	 * Rename the path from $src to $dst
	 * @param string $src the path to be renamed
	 * @param string $dst the destination of the rename
	 * @throws SmbclientWrapperException if the rename fails
	 */
	public function renamePath(string $src, string $dst) {
		$srcPath = \trim($src, '/');
		$srcPath = "smb://{$this->host}/{$this->share}/{$srcPath}";
		$dstPath = \trim($dst, '/');
		$dstPath = "smb://{$this->host}/{$this->share}/{$dstPath}";
		$result = @\smbclient_rename($this->smbResource, $srcPath, $this->smbResource, $dstPath);
		if ($result === false) {
			throw new SmbclientWrapperException(\smbclient_state_errno($this->smbResource), 'rename', [$srcPath, $dstPath]);
		}
	}

	/**
	 * Get the information of the given path.
	 * You can use "SMBStatInfo::parseStat($smbWrapper, $path)" to parse the info.
	 * However, it will cause additional requests for directories
	 * @param string $path the target path
	 * @return array check native PHP's stat for the information returned. Basic info includes
	 * "size" and "mtime"
	 * @throws SmbclientWrapperException if the file can't be stat'ed
	 */
	public function statPath(string $path): array {
		$path = \trim($path, '/');
		$smbPath = "smb://{$this->host}/{$this->share}/{$path}";
		$result = @\smbclient_stat($this->smbResource, $smbPath);
		if ($result === false) {
			throw new SmbclientWrapperException(\smbclient_state_errno($this->smbResource), 'stat', [$smbPath]);
		}
		return $result;
	}

	/**
	 * Rename the target file
	 * @param string $path the path to the file
	 * @throws SmbclientWrapperException if the file can't be removed
	 */
	public function removeFile(string $path) {
		$path = \trim($path, '/');
		$smbPath = "smb://{$this->host}/{$this->share}/{$path}";
		$result = @\smbclient_unlink($this->smbResource, $smbPath);
		if ($result === false) {
			throw new SmbclientWrapperException(\smbclient_state_errno($this->smbResource), 'unlink', [$smbPath]);
		}
	}

	/**
	 * Create a new folder
	 * @param string $path the path to create the folder
	 * @param int $mask the permission mask to be applied. Note that the support for mask may
	 * be absent, and the SMB server may ignore it.
	 * @throws SmbclientWrapperException if the folder can't be created
	 */
	public function createFolder(string $path, int $mask = 0777) {
		$path = \trim($path, '/');
		$smbPath = "smb://{$this->host}/{$this->share}/{$path}";
		$result = @\smbclient_mkdir($this->smbResource, $smbPath, $mask);
		if ($result === false) {
			throw new SmbclientWrapperException(\smbclient_state_errno($this->smbResource), 'mkdir', [$smbPath, $mask]);
		}
	}

	/**
	 * Remove the folder. The folder must be empty, otherwise the call will fail and an exception
	 * will be thrown.
	 * @param string $path the path to remove the folder
	 * @throws SmbclientWrapperException if the folder can't be removed
	 */
	public function removeFolder(string $path) {
		$path = \trim($path, '/');
		$smbPath = "smb://{$this->host}/{$this->share}/{$path}";
		$result = @\smbclient_rmdir($this->smbResource, $smbPath);
		if ($result === false) {
			throw new SmbclientWrapperException(\smbclient_state_errno($this->smbResource), 'rmdir', [$smbPath]);
		}
	}

	/**
	 * Open the directory.
	 * Note that it isn't possible to rewind the list
	 * The supported options are:
	 * - 'logger' => callback. A callback to log possible errors. The callback must support
	 * the signature "($message, $context): void", where the message is the string to be logged
	 * and the context is an array with additional information (normally the app name).
	 * ownCloud's default logger can be used easily with $opts = ['logger' => [$logger, 'error']]
	 * - 'readFilter' => callback. A callback to be able to skip entries. You can skip all
	 * entries starting with "a", or all directories, or all files marked as hidden. The callback
	 * must have signature "($smbclientWrapper, $entry): bool". The smbclientWrapper instance
	 * is expected to be this one (The directory iterator shouldn't change it), and the entry
	 * will be the one coming from the smbclient_readdir function. The callback must return
	 * true to keep the entry (so the iterator will return it) or false to skip it. Note that
	 * all entries will be returned if a readFilter isn't provided
	 * @param string $path the directory to be opened
	 * @param array $opts options for the directory wrapper to be returned
	 * @return resource|false a native resource as if the native "opendir" function had been
	 * called. The resource can be used with native "readdir" and "closedir" functions
	 */
	public function openDirectory($path, array $opts = []) {
		$path = \trim($path, '/');
		$smbPath = "smb://{$this->host}/{$this->share}/{$path}";
		$code = \rand();
		$this->getRawResourceCodes[$code] = true;
		return SMBDirectoryIterator::createDirectoryWrapper($this, $smbPath, $code, $opts);
	}

	/**
	 * Open the file
	 * Some operations might not be available.
	 * The supported options are:
	 * - 'logger' => callback. A callback to log possible errors. The callback must support
	 * the signature "($message, $context): void", where the message is the string to be logged
	 * and the context is an array with additional information (normally the app name).
	 * ownCloud's default logger can be used easily with $opts = ['logger' => [$logger, 'error']]
	 * - 'bufferSize' => int. The length of the buffer to be used. Setting a wrong size will
	 * cause undefined behaviour. Minimum recommended size is 8192, although by default a buffer
	 * if 1MB will be used if this option isn't provided. Regardless of the buffer size, note
	 * that the native "fread" function will read up to 8192 bytes per call.
	 * - 'closeCallbacks' => [$callback1, $callback2, ...]. A list of callbacks that will be
	 * called after the stream is closed. The callbacks mustn't use parameters. The return value
	 * will be ignored.
	 * @param string $path the file to be opened
	 * @param string $mode open mode ("r", "w", "r+", "w+", etc)
	 * @param array $opts options for the stream wrapper to be returned
	 * @return resource|false a native resource as if the native "fopen" function had been
	 * called. The resource can be used with native "fread", "fwrite", "fclose", "fflush",
	 * "fseek" functions
	 */
	public function openFile(string $path, string $mode, array $opts = []) {
		$path = \trim($path, '/');
		$smbPath = "smb://{$this->host}/{$this->share}/{$path}";
		$code = \rand();
		$this->getRawResourceCodes[$code] = true;
		return SMBStream::createStream($this, $smbPath, $mode, $code, $opts);
	}

	public function __destruct() {
		if (\is_resource($this->smbResource)) {
			\smbclient_state_free($this->smbResource);
		}
	}
}
