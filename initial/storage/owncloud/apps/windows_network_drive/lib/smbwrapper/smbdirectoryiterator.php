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

use OCA\windows_network_drive\lib\smbwrapper\SmbclientWrapperException;

class SMBDirectoryIterator {
	/**
	 * @var resource
	 * Required to be public according to the stream wrapper's docs
	 */
	public $context;
	/** @var SmbclientWrapper */
	private $smbWrapper;
	/** @var int */
	private $accessCode;
	/** @var string */
	private $smbPath;
	/**
	 * @var array
	 * $opts = [
	 *   'logger' => $logger  -> a logger callback, "($message, $context)". ownCloud's logger fits here.
	 *   'readFilter' => $filter  -> a callback to filter results from the readdir operations. Callback must return true to accept it, or false to skip it. "($smbclientWrapper, $entryData): bool"
	 * ]
	 */
	private $opts;

	/**
	 * Create a directory wrapper so it can be used with native "readdir" and "closedir"
	 * functions.
	 * This method isn't intended to be used publicly.
	 * @see SmbclientWrapper::openDirectory
	 * @param SmbclientWrapper $smbWrapper the smbclient wrapper to be used for all the calls.
	 * The wrapper must have been initialized and ready to be used. This class WON'T free
	 * the underlying resource by itself.
	 * @param string $path the path to be opened such as "smb://server/share/path".
	 * It's assumed the path refers to a directory. There is no check, so an error is expected
	 * if this assumption is wrong.
	 * @param int $code the random code to be used to access to the underlying resource in
	 * the SmbclientWrapper.
	 * @param array $opts additional options to control the directory wrapper. Some of them are:
	 * - 'logger' => a logger callback to log errors that could happen while traversing the
	 * directory
	 * - 'readFilter' => a callback to filter out directories.
	 */
	public static function createDirectoryWrapper(SmbclientWrapper $smbWrapper, string $path, int $code, array $opts = []) {
		$defaultOpts = [
			'logger' => null,
			'readFilter' => null,
		];
		$realOpts = $opts + $defaultOpts;

		$context = [
			'wrapper' => $smbWrapper,
			'rawResourceCode' => $code,
			'opts' => $realOpts,
		];
		$directoryContext = \stream_context_create(['wnd' => $context]);
		try {
			\stream_wrapper_register('wnd', self::class);
			return @\opendir("wnd://$path", $directoryContext);
		} finally {
			\stream_wrapper_unregister('wnd');
		}
	}

	private function logErrorMessage($rawResource, $funcName, $path) {
		if (isset($this->opts['logger'])) {
			$errno = 0;
			if (\is_resource($rawResource)) {
				$errno = \smbclient_state_errno($rawResource);
			}
			if (isset(SmbclientWrapperException::$errorMap[$errno])) {
				$error = SmbclientWrapperException::$errorMap[$errno];
			} else {
				$error = 'Unknown error';
			}
			$this->opts['logger']("Error calling {$funcName} for {$path} : [{$errno}] {$error}", ['app' => 'wnd']);
		}
	}

	public function dir_opendir($path, $context) {
		$wrapperOpts = \stream_context_get_options($this->context);

		$this->smbWrapper = $wrapperOpts['wnd']['wrapper'];
		$this->accessCode = $wrapperOpts['wnd']['rawResourceCode'];
		$this->opts = $wrapperOpts['wnd']['opts'];
		$this->smbPath = \substr($path, 6);  // remove the "wnd://" protocol from the expected "wnd://smb://path" string
		$rawResource = $this->smbWrapper->getRawResouce($this->accessCode);

		$this->dirResource = @\smbclient_opendir($rawResource, $this->smbPath);
		if ($this->dirResource === false) {
			$this->logErrorMessage($rawResource, __FUNCTION__, $this->smbPath);
			return false;
		}
		return true;
	}

	public function dir_readdir() {
		$rawResource = $this->smbWrapper->getRawResouce($this->accessCode);
		$keepIt = true;
		do {
			$entry = @\smbclient_readdir($rawResource, $this->dirResource);
			if ($entry === false) {
				// it will return false if there are no more entries, so check errno before logging
				if (\smbclient_state_errno($rawResource) !== 0) {
					$this->logErrorMessage($rawResource, __FUNCTION__, $this->smbPath);
				}
				return false;
			}

			if (isset($this->opts['readFilter'])) {
				try {
					$keepIt = $this->opts['readFilter']($this->smbWrapper, $entry);
				} catch (\Exception $e) {
					if (isset($this->opts['logger'])) {
						$this->opts['logger']("WND read filter crashed while reading directory {$this->smbPath}, entry {$entry['name']}: [{$e->getCode()}] {$e->getMessage()}", ['app' => 'wnd']);
					}
					$keepIt = false;
				}
			}
		} while (!$keepIt);
		return $entry['name'];
	}

	public function dir_closedir() {
		$rawResource = $this->smbWrapper->getRawResouce($this->accessCode);
		$result = @\smbclient_closedir($rawResource, $this->dirResource);
		if ($result === false) {
			$this->logErrorMessage($rawResource, __FUNCTION__, $this->smbPath);
			return false;
		}
		$this->smbWrapper->removeRawResourceCode($this->accessCode);
		return true;
	}
}
