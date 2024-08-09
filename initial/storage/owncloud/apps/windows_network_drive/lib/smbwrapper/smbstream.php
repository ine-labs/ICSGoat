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

class SMBStream {
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
	 *   'bufferSize' => int  -> the size of the buffer to be used. If not provided, a 1MB buffer will be used.
	 *   'logger' => $logger  -> a logger callback, "($message, $context)". ownCloud's logger fits here.
	 *   'closeCallbacks' => [$callback1, $callback2, ...]  -> the list of callbacks (with no parameters) to be called when the file is closed
	 * ]
	 */
	private $opts;

	private $buffer = '';
	private $bufferPointer = 0;
	private $streamState = '';
	private $eof = false;
	private $seekPointer = 0;

	/**
	 * Create a stream wrapper so it can be used with native "fread", "fwrite"... operations.
	 * This method isn't intended to be used publicly.
	 * @see SmbclientWrapper::openFile
	 * @param SmbclientWrapper $smbWrapper the smbclient wrapper to be used for all the calls.
	 * The wrapper must have been initialized and ready to be used. This class WON'T free
	 * the underlying resource by itself.
	 * @param string $path the path to be opened such as "smb://server/share/file". It's assumed
	 * the path refers to a file. There is no check, so an error is expected if this assumption
	 * is wrong.
	 * @param string $mode the open mode. Binary modes might fail
	 * @param int $code the random code to be used to access to the underlying resource in
	 * the SmbclientWrapper.
	 * @param array $opts additional options to control the directory wrapper. Some of them are:
	 * - 'logger' => a logger callback to log errors that could happen while traversing the
	 * directory
	 * - 'bufferSize' => the size of the buffer. 1MB by default
	 * - 'closeCallbacks' => a list of callbacks to be called after the file is closed
	 */
	public static function createStream(SmbclientWrapper $smbWrapper, string $path, string $mode, int $code, array $opts = []) {
		$defaultOpts = [
			'bufferSize' => 1024*1024,
			'logger' => null,
			'closeCallbacks' => null,
		];
		$realOpts = $opts + $defaultOpts;

		$context = [
			'wrapper' => $smbWrapper,
			'rawResourceCode' => $code,
			'opts' => $realOpts,
		];
		$fileContext = \stream_context_create(['wnd' => $context]);
		try {
			\stream_wrapper_register('wnd', self::class);
			return @\fopen("wnd://$path", $mode, false, $fileContext);
		} finally {
			\stream_wrapper_unregister('wnd');
		}
	}

	private function logErrorMessage($rawResource, $funcName, $path) {
		if (isset($this->opts['logger'])) {
			$errno = \smbclient_state_errno($rawResource);
			if (isset(SmbclientWrapperException::$errorMap[$errno])) {
				$error = SmbclientWrapperException::$errorMap[$errno];
			} else {
				$error = 'Unknown error';
			}
			$this->opts['logger']("Error calling {$funcName} for {$path} : [{$errno}] {$error}", ['app' => 'wnd']);
		}
	}

	public function stream_open($path, $mode, $options, &$opened_path) {
		$wrapperOpts = \stream_context_get_options($this->context);

		$this->smbWrapper = $wrapperOpts['wnd']['wrapper'];
		$this->accessCode = $wrapperOpts['wnd']['rawResourceCode'];
		$this->opts = $wrapperOpts['wnd']['opts'];
		$this->smbPath = \substr($path, 6);  // remove the "wnd://" protocol from the expected "wnd://smb://path" string
		$rawResource = $this->smbWrapper->getRawResouce($this->accessCode);

		$this->fileResource = @\smbclient_open($rawResource, $this->smbPath, $mode);
		if ($this->fileResource === false) {
			$this->logErrorMessage($rawResource, __FUNCTION__, $this->smbPath);
			return false;
		}
		return true;
	}

	public function stream_read(int $count) {
		// flush first if needed
		if ($this->streamState !== 'reading' && !$this->stream_flush()) {
			// if not flushed correctly, return false to prevent further problems
			return false;
		}

		$this->streamState = 'reading';
		if (\strlen($this->buffer) - $this->bufferPointer >= $count) {
			$result = \substr($this->buffer, $this->bufferPointer, $count);
			$this->bufferPointer += $count;
			$this->seekPointer += $count;
			return $result;
		}

		$rawResource = $this->smbWrapper->getRawResouce($this->accessCode);

		$result = \substr($this->buffer, $this->bufferPointer);
		$remainingCharsInBuffer = \strlen($this->buffer) - $this->bufferPointer;
		$remainingCharsToBeRead = $count - $remainingCharsInBuffer;

		do {
			$data = @\smbclient_read($rawResource, $this->fileResource, $this->opts['bufferSize']);
			if ($data === '') {
				$this->eof = true;
			} elseif ($data === false || \strlen($data) < 0) {
				// strlen = -1 might happen due to broken buffer when the connection
				// is cut. No other way to detect this issue
				$this->logErrorMessage($rawResource, __FUNCTION__, $this->smbPath);
				return false;
			}

			$result .= \substr($data, 0, $remainingCharsToBeRead);
			$this->buffer = $data;
			$this->bufferPointer = $remainingCharsToBeRead;
			$dataLength = \strlen($data);
			$remainingCharsToBeRead -= $dataLength;
		} while (!$this->eof && $remainingCharsToBeRead > 0);
		$this->seekPointer += $dataLength;
		return $result;
	}

	public function stream_write(string $data) {
		if ($this->streamState !== 'writing' && !$this->stream_flush()) {
			return false;
		}

		$this->streamState = 'writing';
		$freeBufferSize = $this->opts['bufferSize'] - \strlen($this->buffer);
		$dataLength = \strlen($data);

		if ($dataLength <= $freeBufferSize) {
			$this->buffer .= $data;
			$this->seekPointer += $dataLength;
			return $dataLength;
		}

		$rawResource = $this->smbWrapper->getRawResouce($this->accessCode);

		// fill buffer and send it
		$this->buffer .= \substr($data, 0, $freeBufferSize);
		$bytesWritten = @\smbclient_write($rawResource, $this->fileResource, $this->buffer);
		if ($bytesWritten === false) {
			$this->logErrorMessage($rawResource, __FUNCTION__, $this->smbPath);
			return false;
		}

		// split the remaining string
		foreach (\str_split(\substr($data, $freeBufferSize), $this->opts['bufferSize']) as $dataPiece) {
			if (\strlen($dataPiece) >= $this->opts['bufferSize']) {
				$bytesWritten = @\smbclient_write($rawResource, $this->fileResource, $dataPiece);
				if ($bytesWritten === false) {
					$this->logErrorMessage($rawResource, __FUNCTION__, $this->smbPath);
					return false;
				}
			} else {
				$this->buffer = $dataPiece;
			}
		}
		$this->seekPointer += $dataLength;
		return $dataLength;
	}

	public function stream_flush() {
		$rawResource = $this->smbWrapper->getRawResouce($this->accessCode);
		switch ($this->streamState) {
			case '':
				return true;
			case 'reading':
				$seek = @\smbclient_lseek($rawResource, $this->fileResource, - (\strlen($this->buffer) - $this->bufferPointer), SEEK_CUR);
				if ($seek === false) {
					$this->logErrorMessage($rawResource, __FUNCTION__, $this->smbPath);
					return false;
				}
				$this->buffer = '';
				$this->bufferPointer = 0;
				return true;
			case 'writing':
				if ($this->buffer === '') {
					// nothing to write
					return true;
				}
				$bytesWritten = @\smbclient_write($rawResource, $this->fileResource, $this->buffer);
				if ($bytesWritten === false) {
					$this->logErrorMessage($rawResource, __FUNCTION__, $this->smbPath);
					return false;
				}
				$this->buffer = '';
				$this->bufferPointer = 0;
				return true;
			default:
				return false;
		}
	}

	public function stream_seek(int $offset, int $whence) {
		if (!$this->stream_flush()) {
			return false;
		}

		$rawResource = $this->smbWrapper->getRawResouce($this->accessCode);
		$result = @\smbclient_lseek($rawResource, $this->fileResource, $offset, $whence);
		if ($result === false) {
			$this->logErrorMessage($rawResource, __FUNCTION__, $this->smbPath);
			return false;
		}
		$this->buffer = '';
		$this->bufferPointer = 0;
		// reset eof. Seeking the end of file to read is a weird case, so let them read an
		// empty string in that case.
		$this->eof = false;
		$this->seekPointer = $result;
		return true;
	}

	public function stream_eof() {
		return $this->eof;
	}

	public function stream_tell() {
		return $this->seekPointer;
	}

	public function stream_stat() {
		// use the raw resource for consistency
		$rawResource = $this->smbWrapper->getRawResouce($this->accessCode);
		$result = @\smbclient_fstat($rawResource, $this->fileResource);
		if ($result === false) {
			$this->logErrorMessage($rawResource, __FUNCTION__, $this->smbPath);
			return false;
		}
		return $result;
	}

	public function stream_close() {
		// flush first
		if (!$this->stream_flush()) {
			return false;
		}

		$rawResource = $this->smbWrapper->getRawResouce($this->accessCode);
		$result = @\smbclient_close($rawResource, $this->fileResource);
		if ($result === false) {
			$this->logErrorMessage($rawResource, __FUNCTION__, $this->smbPath);
			return false;
		}
		$this->smbWrapper->removeRawResourceCode($this->accessCode);

		if (isset($this->opts['closeCallbacks'])) {
			foreach ($this->opts['closeCallbacks'] as $callback) {
				$callback();
			}
		}
		return true;
	}
}
